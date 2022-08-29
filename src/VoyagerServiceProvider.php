<?php

namespace Voyager\Admin;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\{Event, Http, Route};
use Illuminate\Support\{Collection, Str};
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Voyager\Admin\Classes\{Action, Bread, MenuItem};
use Voyager\Admin\Commands\{DevCommand, InstallCommand, ModelCommand, PluginsCommand};
use Voyager\Admin\Exceptions\Handler as ExceptionHandler;
use Voyager\Admin\Facades\Voyager as VoyagerFacade;
use Voyager\Admin\Http\Middleware\VoyagerAdminMiddleware;
use Voyager\Admin\Manager\{Breads as BreadManager, Menu as MenuManager, Plugins as PluginManager, Settings as SettingManager};
use Voyager\Admin\Contracts\Plugins\FormfieldPlugin;
use Voyager\Admin\Policies\BasePolicy;

class VoyagerServiceProvider extends ServiceProvider
{
    /**
     * @var array<string>
     */
    protected $policies = [];

    /**
     * @var PluginManager
     */
    protected $pluginmanager;

    /**
     * @var BreadManager
     */
    protected $breadmanager;

    /**
     * @var MenuManager
     */
    protected $menumanager;

    /**
     * @var SettingManager
     */
    protected $settingmanager;

    /**
     * @var bool
     */
    protected $dataLoaded = false;

    /**
     * Bootstrap the application services.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('voyager.admin', VoyagerAdminMiddleware::class);

        $this->registerResources();

        // Register permissions
        app(Gate::class)->before(static function ($user, $ability, $arguments = []) {
            return VoyagerFacade::authorize($user, $ability, $arguments);
        });

        // A Voyager page was requested. Dispatched in middleware
        Event::listen('voyager.page', function () {
            if (!$this->dataLoaded) {
                $this->loadPluginFormfields();

                $breads = $this->breadmanager->getBreads();

                // Register menu-items
                $this->registerMenuItems();
                $this->registerBreadBuilderMenuItems($breads);
                $this->registerBreadMenuItems($breads);

                // Register BREAD policies
                $this->registerBreadPolicies($breads);
                $this->registerPolicies();

                // Register actions
                $this->registerActions();
                $this->registerBulkActions();

                if ($this->settingmanager->setting('admin.dev-server', false) === true) {
                    $url = 'http://localhost:8081/';
                    view()->share('devServerUrl', $url);
                    view()->share('devServerWanted', true);
                    try {
                        Http::timeout(1)->get($url)->ok();
                        view()->share('devServerAvailable', true);
                    } catch (\Exception $e) {
                        view()->share('devServerAvailable', false);
                    }
                } else {
                    view()->share('devServerAvailable', false);
                    view()->share('devServerWanted', false);
                    view()->share('devServerUrl', null);
                }
        
                view()->share('voyagerVersion', VoyagerFacade::getVersion());
        
                Inertia::setRootView('voyager::app');
                // Override ExceptionHandler only when on a Voyager page
                app()->singleton(
                    \Illuminate\Contracts\Debug\ExceptionHandler::class,
                    ExceptionHandler::class
                );
                $this->dataLoaded = true;
            }
        });
    }

    /**
     * Register the Voyager resources.
     *
     * @return void
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(realpath(__DIR__.'/../resources/views'), 'voyager'); // @phpstan-ignore-line
        $this->loadTranslationsFrom(realpath(__DIR__.'/../resources/lang'), 'voyager'); // @phpstan-ignore-line
    }

    /**
     * Register the Voyager routes.
     *
     * @param Collection $breads A collection of the Voyager apps current BREADs.
     *
     * @return void
     */
    protected function registerRoutes(Collection $breads)
    {
        Route::group(['as' => 'voyager.', 'prefix' => Voyager::$routePath, 'namespace' => 'Voyager\Admin\Http\Controllers'], function () use ($breads) {
            Route::group(['middleware' => config('auth.defaults.guard', 'web')], function () use ($breads) {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
                $this->pluginmanager->launchPlugins(false);
                // Protected routes
                Route::group(['middleware' => 'voyager.admin'], function () use ($breads) {
                    $this->registerBreadRoutes($breads);
                    $this->pluginmanager->launchPlugins(true);
                });

                // Catch all other routes
                Route::any('{all}', function () {
                    Event::dispatch('voyager.page');
                    throw new NotFoundHttpException();
                })->where('all', '.*');
            });

            // Make sure all registered routes by plugins exist
            app(Router::class)->getRoutes()->refreshNameLookups();
            $this->pluginmanager->launchPlugins();
        });
    }

    /**
     * Register all the dynamic BREAD type routes.
     *
     * @param Collection $breads A collection of the Voyager apps current BREADs.
     */
    private function registerBreadRoutes(Collection $breads): void
    {
        $breads->each(static function (Bread $bread) {
            $controller = 'BreadController';
            if (is_string($bread->controller)) {
                $controller = Str::start($bread->controller, '\\');
            }
            Route::group([
                'as'         => $bread->slug.'.',
                'prefix'     => $bread->slug,
            ], static function () use ($bread, $controller) {
                // Browse
                Route::get('/', ['uses'=> $controller.'@browse', 'as' => 'browse', 'bread' => $bread]);
                Route::post('/data', ['uses'=> $controller.'@data', 'as' => 'data', 'bread' => $bread]);

                // Edit
                Route::get('/edit/{id}', ['uses' => $controller.'@edit', 'as' => 'edit', 'bread' => $bread]);
                Route::put('/{id}', ['uses' => $controller.'@update', 'as' => 'update', 'bread' => $bread]);

                // Add
                Route::get('/add', ['uses' => $controller.'@add', 'as' => 'add', 'bread' => $bread]);
                Route::post('/', ['uses' => $controller.'@store', 'as' => 'store', 'bread' => $bread]);

                // Delete
                Route::delete('/', ['uses' => $controller.'@delete', 'as' => 'delete', 'bread' => $bread]);
                Route::patch('/', ['uses' => $controller.'@restore', 'as' => 'restore', 'bread' => $bread]);

                // Read
                Route::get('/{id}', ['uses' => $controller.'@read', 'as' => 'read', 'bread' => $bread]);

                // Order
                Route::post('/order', ['uses' => $controller.'@order', 'as' => 'order', 'bread' => $bread]);

                // Relationship
                Route::post('/relationship', ['uses' => $controller.'@relationship', 'as' => 'relationship', 'bread' => $bread]);
            });
        });
    }

    /**
     * Register the default BREAD actions (single entry).
     *
     * @return void
     */
    public function registerActions()
    {
        $breadmanager = $this->breadmanager;

        $read_action = (new Action('voyager::generic.read', 'book-open', null, 'accent'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.read';
        })->permission('read')
        ->displayOnBread(function ($bread) use ($breadmanager) {
            return $breadmanager->getLayoutsForAction($bread, 'read')->count() > 0;
        });

        $edit_action = (new Action('voyager::generic.edit', 'pencil', null, 'yellow'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.edit';
        })->permission('edit')
        ->displayOnBread(function ($bread) use ($breadmanager) {
            return $breadmanager->getLayoutsForAction($bread, 'edit')->count() > 0;
        });

        $delete_action = (new Action('voyager::generic.delete', 'trash', null, 'red'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.delete';
        })
        ->method('delete')
        ->confirm('voyager::bread.delete_type_confirm', null, 'red')
        ->success('voyager::bread.delete_type_success', null, 'green')
        ->displayDeletable()
        ->reloadAfter()
        ->permission('delete');

        $restore_action = (new Action('voyager::generic.restore', 'trash', null, 'yellow'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.restore';
        })
        ->method('patch')
        ->confirm('voyager::bread.restore_type_confirm', null, 'yellow')
        ->success('voyager::bread.restore_type_success', null, 'green')
        ->displayRestorable()
        ->reloadAfter()
        ->permission('restore');

        $this->breadmanager->addAction($read_action);
        $this->breadmanager->addAction($edit_action);
        $this->breadmanager->addAction($delete_action);
        $this->breadmanager->addAction($restore_action);
    }

    /**
     * Register the default BREAD actions (multiple entries).
     *
     * @return void
     */
    public function registerBulkActions()
    {
        $breadmanager = $this->breadmanager;

        $add_action = (new Action('voyager::generic.add_type', 'plus', null, 'green'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.add';
        })
        ->bulk()
        ->displayOnBread(function ($bread) use ($breadmanager) {
            return $breadmanager->getLayoutsForAction($bread, 'add')->count() > 0;
        });

        $delete_action = (new Action('voyager::bread.delete_type', 'trash', null, 'red'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.delete';
        })
        ->method('delete')
        ->confirm('voyager::bread.delete_type_confirm', null, 'red')
        ->success('voyager::bread.delete_type_success', null, 'green')
        ->bulk()
        ->displayDeletable()
        ->reloadAfter();

        $restore_action = (new Action('voyager::bread.restore_type', 'trash', null, 'yellow'))
        ->route(function ($bread) {
            return 'voyager.'.$bread->slug.'.restore';
        })
        ->method('patch')
        ->confirm('voyager::bread.restore_type_confirm', null, 'yellow')
        ->success('voyager::bread.restore_type_success', null, 'green')
        ->bulk()
        ->displayRestorable()
        ->reloadAfter();

        $this->breadmanager->addAction($add_action);
        $this->breadmanager->addAction($delete_action);
        $this->breadmanager->addAction($restore_action);
    }

    /**
     * Fetch enabled formfield plugins and register them with the BREAD manager.
     */
    public function loadPluginFormfields(): void
    {
        $this->pluginmanager->getAllPlugins()->filter(function ($plugin) {
            return $plugin instanceof FormfieldPlugin;
        })->each(function ($formfield) {
            $this->breadmanager->addFormfield($formfield->getFormfield());
        });
    }

    /**
     * Register all policies from the BREADs.
     *
     * @param Collection $breads A collection of the Voyager apps current BREADs.
     */
    public function registerBreadPolicies(Collection $breads): void
    {
        $breads->each(function ($bread) {
            $policy = BasePolicy::class;

            if (!empty($bread->policy) && class_exists($bread->policy)) {
                $policy = $bread->policy;
            }

            $this->policies[$bread->model.'::class'] = $policy;
        });
    }

    /**
     * Register the menu items for each BREAD builder.
     *
     * @param Collection $breads A collection of the Voyager apps current BREADs.
     */
    public function registerBreadBuilderMenuItems(Collection $breads): void
    {
        $bread_builder_item = (new MenuItem(__('voyager::generic.bread'), 'bread', true))
                                ->permission('browse', [new BREAD('')])
                                ->route('voyager.bread.index');

        $this->menumanager->addItems($bread_builder_item);

        $breads->each(static function ($bread) use ($bread_builder_item) {
            $bread_builder_item->addChildren(
                (new MenuItem($bread->name_plural, $bread->icon, true))->permission('edit', [$bread])
                    ->route('voyager.bread.edit', ['table' => $bread->table])
            );
        });
    }

    /**
     * Register BREAD-browse menu items for all BREADs.
     * 
     * @param Collection $breads A collection of the Voyager apps current BREADs.
     */
    public function registerBreadMenuItems(Collection $breads): void
    {
        if ($breads->count() > 0) {
            $this->menumanager->addItems(
                (new MenuItem('', '', true))->divider()
            );

            $breads->each(function ($bread) {
                $this->menumanager->addItems(
                    (new MenuItem($bread->name_plural, $bread->icon, true))->permission('browse', [$bread->model, $bread])
                        ->route('voyager.'.$bread->slug.'.browse')
                        ->badge(
                            $bread->badge_color,
                            !is_null($bread->badge_color) ? $bread->getModel()->count() : null
                        )
                );
            });
        }
    }

    /**
     * Register generic menu items.
     */
    private function registerMenuItems(): void
    {
        $this->menumanager->addItems(
            (new MenuItem(__('voyager::generic.dashboard'), 'home', true))->permission('browse', ['voyager'])->route('voyager.dashboard')->exact()
        );
        $this->menumanager->addItems(
            (new MenuItem(__('voyager::generic.media'), 'photo', true))->permission('browse', ['media'])->route('voyager.media')
        );

        if ($this->settingmanager->setting('admin.ui-components', true)) {
            $this->menumanager->addItems(
                (new MenuItem(__('voyager::generic.ui_components'), 'rectangle-group', true))->permission('browse', ['ui'])->route('voyager.ui')
            );
        }

        $this->menumanager->addItems(
            (new MenuItem(__('voyager::generic.settings'), 'cog', true))->permission('browse', ['settings'])->route('voyager.settings.index'),
            (new MenuItem(__('voyager::plugins.plugins'), 'puzzle-piece', true))->permission('browse', ['plugins'])->route('voyager.plugins.index')
        );
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        app()->register(\Inertia\ServiceProvider::class);

        $loader = AliasLoader::getInstance();
        $loader->alias('Voyager', VoyagerFacade::class);

        $this->menumanager = new MenuManager();
        app()->singleton(MenuManager::class, function () {
            return $this->menumanager;
        });

        $this->settingmanager = new SettingManager();
        app()->singleton(SettingManager::class, function () {
            return $this->settingmanager;
        });

        $this->pluginmanager = new PluginManager($this->menumanager, $this->settingmanager);
        app()->singleton(PluginManager::class, function () {
            return $this->pluginmanager;
        });

        $this->breadmanager = new BreadManager($this->pluginmanager);
        app()->singleton(BreadManager::class, function () {
            return $this->breadmanager;
        });

        app()->singleton('voyager', function () {
            return new Voyager($this->breadmanager, $this->menumanager, $this->pluginmanager, $this->settingmanager);
        });

        $this->settingmanager->load();

        $this->commands(DevCommand::class);
        $this->commands(InstallCommand::class);
        $this->commands(ModelCommand::class);
        $this->commands(PluginsCommand::class);

        $this->registerFormfields();

        app()->booted(function () {
            $this->registerRoutes($this->breadmanager->getBreads());
        });
    }

    /**
     * Register all core formfields.
     */
    private function registerFormfields(): void
    {
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Checkbox::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\DateTime::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\DynamicInput::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\MediaPicker::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Number::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Password::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Radio::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Relationship::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Repeater::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Select::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\SimpleArray::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Slider::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Slug::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Tags::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Text::class);
        $this->breadmanager->addFormfield(\Voyager\Admin\Formfields\Toggle::class);
    }
}
