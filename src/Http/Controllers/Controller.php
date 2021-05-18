<?php

namespace Voyager\Admin\Http\Controllers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Voyager\Admin\Contracts\Plugins\Features\Filter\Layouts as LayoutFilter;
use Voyager\Admin\Exceptions\NoLayoutFoundException;
use Voyager\Admin\Facades\Voyager as VoyagerFacade;
use Voyager\Admin\Manager\Plugins as PluginManager;
use Voyager\Admin\Plugins\AuthenticationPlugin;
use Voyager\Admin\Plugins\AuthorizationPlugin;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    protected $pluginmanager;

    public function __construct(PluginManager $pluginmanager)
    {
        $this->pluginmanager = $pluginmanager;
        Event::dispatch('voyager.page');
    }

    protected function inertiaRender($page, $data = [], $root_view = null)
    {
        Inertia::setRootView($root_view ?? 'voyager::app');

        return Inertia::render($page, $data);
    }

    protected function validateData($formfields, $data, $all_locales = false): array
    {
        $errors = [];

        $formfields->each(function ($formfield) use (&$errors, $data, $all_locales) {
            $formfield->validation = $formfield->validation ?? [];
            $value = $data[$formfield->column->column] ?? '';
            if ($formfield->translatable && is_array($value) && !$all_locales) {
                $value = $value[VoyagerFacade::getLocale()] ?? $value[VoyagerFacade::getFallbackLocale()] ?? '';
            }
            foreach ($formfield->validation as $rule) {
                if ($rule->rule == '') {
                    continue;
                }
                if ($all_locales && $formfield->translatable && is_array($value)) {
                    $locales = VoyagerFacade::getLocales();
                    foreach ($locales as $locale) {
                        $val = $value[$locale] ?? null;
                        $result = $this->validateField($val, $rule->rule, $rule->message);
                        if (!is_null($result)) {
                            $locale = Lang::has('voyager::generic.languages.'.$locale, null, false) ? __('voyager::generic.languages.'.$locale) : strtoupper($locale);
                            $errors[$formfield->column->column][] = $locale.': '.$result;
                        }
                    }
                } else {
                    $result = $this->validateField($value, $rule->rule, $rule->message);
                    if (!is_null($result)) {
                        $errors[$formfield->column->column][] = $result;
                    }
                }
            }
        });

        return $errors;
    }

    protected function validateField($value, $rule, $message)
    {
        $ruleSet = ['col' => $rule];

        if (Str::startsWith($rule, '.')) {
            $ruleSet = ['col.*'.Str::before($rule, ':') => Str::after($rule, ':')];
            $value = [$value];
        } elseif (Str::startsWith($rule, '*.')) {
            $ruleSet = ['col.'.Str::before($rule, ':') => Str::after($rule, ':')];
            $value = $value;
        }

        $validator = Validator::make(['col' => $value], $ruleSet);

        if ($validator->fails()) {
            if (is_object($message)) {
                $message = $message->{VoyagerFacade::getLocale()} ?? $message->{VoyagerFacade::getFallbackLocale()} ?? '';
            } elseif (is_array($message)) {
                $message = $message[VoyagerFacade::getLocale()] ?? $message[VoyagerFacade::getFallbackLocale()] ?? '';
            }

            return $message;
        }

        return null;
    }

    protected function getBread(Request $request)
    {
        return $request->route()->getAction()['bread'] ?? abort(404);
    }

    protected function getLayoutForAction($bread, $action)
    {
        $layouts = $bread->layouts->whereIn('name', $bread->layout_map->{$action})->where('type', $action == 'browse' ? 'list' : 'view');

        $this->pluginmanager->getAllPlugins()->each(function ($plugin) use ($bread, $action, &$layouts) {
            if ($plugin instanceof LayoutFilter) {
                $layouts = $plugin->filterLayouts($bread, $action, $layouts);
            }
        });

        if ($layouts->count() < 1) {
            throw new NoLayoutFoundException(__('voyager::bread.no_layout_assigned', ['action' => ucfirst($action)]));
        }

        return $layouts->first();
    }
}
