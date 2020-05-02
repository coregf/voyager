<?php

namespace Voyager\Admin\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BreadController extends Controller
{
    public function data(Request $request)
    {
        $start = microtime(true);
        $bread = $this->getBread($request);
        $layout = $this->getLayoutForAction($bread, 'browse');

        $uses_translatable_trait = $bread->usesTranslatableTrait();

        list(
            'page'        => $page,
            'perpage'     => $perpage,
            'query'       => $global,
            'filters'     => $filters,
            'order'       => $order,
            'direction'   => $direction,
            'softdeleted' => $softdeleted,
            'locale'      => $locale
        ) = $request->all();

        $query = $bread->getModel()->select('*');

        // Soft-deletes
        $uses_soft_deletes = $bread->usesSoftDeletes();
        if (!isset($layout->options->soft_deletes) || !$layout->options->soft_deletes) {
            $uses_soft_deletes = false;
        }
        if ($uses_soft_deletes) {
            if ($softdeleted == 'show') {
                $query = $query->withTrashed();
            } elseif ($softdeleted == 'only') {
                $query = $query->onlyTrashed();
            }
        }

        $total = $query->count();

        // Global search ($global)
        if (!empty($global)) {
            $query->where(function ($query) use ($global, $layout, $locale) {
                $layout->searchableFormfields()->each(function ($formfield) use (&$query, $global, $locale) {
                    if ($formfield->column->type == 'column') {
                        $query->orWhere(DB::raw('lower('.$formfield->column->column.')'), 'LIKE', '%'.strtolower($global).'%');
                    } elseif ($formfield->translatable ?? false) {
                        $query->orWhere(DB::raw('lower('.$formfield->column->column.'->"$.'.$locale.'")'), 'LIKE', '%'.strtolower($global).'%');
                    }
                });
            });
        }

        // Column search ($filters)
        foreach (array_filter($filters) as $column => $filter) {
            if ($layout->getFormfieldByColumn($column)->column->type == 'column') {
                $query->where(DB::raw('lower('.$column.')'), 'LIKE', '%'.strtolower($filter).'%');
            } elseif ($formfield->translatable ?? false) {
                $query->where(DB::raw('lower('.$column.'->"$.'.$locale.'")'), 'LIKE', '%'.strtolower($filter).'%');
            }
        }

        // Ordering ($order and $direction)
        if (!empty($direction) && !empty($order)) {
            if ($layout->getFormfieldByColumn($order)->column->type == 'column') {
                $query = $query->orderBy($order, $direction);
            } elseif ($formfield->translatable ?? false) {
                $query = $query->orderBy(DB::raw('lower('.$order.'->"$.'.$locale.'")'), $direction);
            }
        }

        $query = $query->get();
        $filtered = $query->count();

        // Pagination ($page and $perpage)
        $query = $query->slice(($page - 1) * $perpage)->take($perpage);

        // Load accessors
        $accessors = $layout->getFormfieldsByColumnType('computed')->pluck('column.column')->toArray();
        $query = $query->each(function ($item) use ($accessors) {
            $item->append($accessors);
        });

        // Transform results
        $query = $query->transform(function ($item) use ($uses_soft_deletes, $uses_translatable_trait, $layout) {
            if ($uses_translatable_trait) {
                $item->dontTranslate();
            }
            // Add soft-deleted property
            $item->is_soft_deleted = false;
            if ($uses_soft_deletes && !empty($item->deleted_at)) {
                $item->is_soft_deleted = $item->trashed();
            }

            $layout->formfields->each(function ($formfield) use (&$item) {
                if ($formfield->column->type == 'relationship') {
                    $relationship = Str::before($formfield->column->column, '.');
                    $property = Str::after($formfield->column->column, '.');
                    if (Str::contains($property, 'pivot.')) {
                        // Pivot data
                        $property = Str::after($property, 'pivot.');
                        $pivot = [];
                        $item->{$relationship}->each(function ($related) use (&$pivot, $formfield, $property) {
                            if (isset($related->pivot) && isset($related->pivot->{$property})) {
                                $pivot[] = $formfield->browse($related->pivot->{$property});
                            }
                        });
                        $item->{$formfield->column->column} = $pivot;
                    } elseif ($item->{$relationship} instanceof Collection) {
                        // X-Many relationship
                        $item->{$formfield->column->column} = $item->{$relationship}->pluck($property)->transform(function ($value) use ($formfield) {
                            return $formfield->browse($value);
                        });
                    } elseif (!empty($item->{$relationship})) {
                        // Normal property/X-One relationship
                        $item->{$formfield->column->column} = $formfield->browse($item->{$relationship}->{$property});
                    }
                } else {
                    $item->{$formfield->column->column} = $formfield->browse($item->{$formfield->column->column});
                }
            });

            return $item;
        });

        return [
            'results'           => $query->values(),
            'filtered'          => $filtered,
            'total'             => $total,
            'layout'            => $layout,
            'execution'         => number_format(((microtime(true) - $start) * 1000), 0, '.', ''),
            'uses_soft_deletes' => $uses_soft_deletes,
            'primary'           => $query->get(0) ? $query->get(0)->getKeyName() : 'id',
            'translatable'      => $layout->hasTranslatableFormfields(),
        ];
    }

    public function add(Request $request)
    {
        $bread = $this->getBread($request);
        $layout = $this->getLayoutForAction($bread, 'add');
        $new = true;
        $data = collect();

        $layout->formfields->each(function ($formfield) use (&$data) {
            $data->put($formfield->column->column, $formfield->add());
        });

        return view('voyager::bread.edit-add', compact('bread', 'layout', 'new', 'data'));
    }

    public function store(Request $request)
    {
        $bread = $this->getBread($request);
        $layout = $this->getLayoutForAction($bread, 'add');

        $model = new $bread->model();
        $data = $request->get('data', []);

        if ($bread->usesTranslatableTrait()) {
            $model->dontTranslate();
        }

        // Validate Data
        $validation_errors = $this->validateData($layout->formfields, $data);
        if (count($validation_errors) > 0) {
            return response()->json($validation_errors, 422);
        }

        $layout->formfields->each(function ($formfield) use ($data, &$model) {
            $value = $data[$formfield->column->column] ?? '';

            if ($formfield->translatable ?? false) {
                $translations = [];
                foreach ($value as $locale => $translated) {
                    $translations[$locale] = $formfield->store($translated);
                }
                $value = json_encode($translations);
            } else {
                $value = $formfield->store($value);
            }

            if ($formfield->column->type == 'column') {
                $model->{$formfield->column->column} = $value;
            } elseif ($formfield->column->type == 'computed') {
                if (method_exists($model, 'set'.Str::camel($formfield->column->column).'Attribute')) {
                    $model->{$formfield->column->column} = $value;
                }
            } elseif ($formfield->column->type == 'relationship') {
                //
            }
        });

        if ($model->save()) {
            return response($model->getKey(), 200);
        } else {
            return response($model->getKey(), 500);
        }
    }

    public function read(Request $request, $id)
    {
        $bread = $this->getBread($request);
        $layout = $this->getLayoutForAction($bread, 'read');
        $data = $bread->getModel()->findOrFail($id);

        $layout->formfields->each(function ($formfield) use (&$data) {
            $value = $data->{$formfield->column->column};
            $data->{$formfield->column->column} = $formfield->read($value);
        });

        return view('voyager::bread.read', compact('bread', 'data', 'layout'));
    }

    public function edit(Request $request, $id)
    {
        $bread = $this->getBread($request);
        $layout = $this->getLayoutForAction($bread, 'add');
        $new = false;

        $data = $bread->getModel()->findOrFail($id);

        if ($bread->usesTranslatableTrait()) {
            $data->dontTranslate();
        }

        $layout->formfields->each(function ($formfield) use (&$data) {
            $value = $data->{$formfield->column->column};

            if ($formfield->translatable ?? false) {
                $translations = [];
                $value = json_decode($value) ?? [];
                foreach ($value as $locale => $translated) {
                    $translations[$locale] = $formfield->edit($translated);
                }
                $data->{$formfield->column->column} = json_encode($translations);
            } else {
                $data->{$formfield->column->column} = $formfield->edit($value);
            }
        });

        return view('voyager::bread.edit-add', compact('bread', 'layout', 'new', 'data'));
    }

    public function update(Request $request, $id)
    {
        $bread = $this->getBread($request);
        $layout = $this->getLayoutForAction($bread, 'add');

        $model = $bread->getModel()->findOrFail($id);
        $data = $request->get('data', []);

        if ($bread->usesTranslatableTrait()) {
            $model->dontTranslate();
        }

        // Validate Data
        $validation_errors = $this->validateData($layout->formfields, $data);
        if (count($validation_errors) > 0) {
            return response()->json($validation_errors, 422);
        }

        $layout->formfields->each(function ($formfield) use ($data, &$model, $request) {
            $value = $data[$formfield->column->column] ?? '';

            if ($formfield->translatable ?? false) {
                $translations = [];
                $old = $model->{$formfield->column->column};
                if (!is_object($old)) {
                    $old = @json_decode($old);
                }
                foreach ($value as $locale => $translated) {
                    $translations[$locale] = $formfield->update($translated, (isset($old->{$locale}) ? $old->{$locale} : ''));
                }
                $value = json_encode($translations);
            } else {
                $value = $formfield->update($value, $model->{$formfield->column->column});
            }

            if ($formfield->column->type == 'column') {
                $model->{$formfield->column->column} = $value;
            } elseif ($formfield->column->type == 'computed') {
                if (method_exists($model, 'set'.Str::camel($formfield->column->column).'Attribute')) {
                    $model->{$formfield->column->column} = $value;
                }
            } elseif ($formfield->column->type == 'relationship') {
                //
            }
        });

        if ($model->save()) {
            return response($model->getKey(), 200);
        } else {
            return response($model->getKey(), 500);
        }
    }

    public function delete(Request $request)
    {
        $bread = $this->getBread($request);
        $model = $bread->getModel();
        if ($bread->usesSoftDeletes()) {
            $model = $model->withTrashed();
        }

        $deleted = 0;

        $force = $request->get('force', 'false');
        if ($request->has('ids')) {
            $ids = $request->get('ids');
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $model->find($ids)->each(function ($entry) use ($bread, $force, &$deleted) {
                if ($force == 'true' && $bread->usesSoftDeletes()) {
                    // TODO: Check if layout allows usage of soft-deletes
                    if ($entry->trashed()) {
                        $this->authorize('force-delete', $entry);
                        $entry->forceDelete();
                        $deleted++;
                    }
                } else {
                    $this->authorize('delete', $entry);
                    $entry->delete();
                    $deleted++;
                }
            });
        }

        return $deleted;
    }

    public function restore(Request $request)
    {
        // TODO: Check if layout allows usage of soft-deletes
        $bread = $this->getBread($request);
        if (!$bread->usesSoftDeletes()) {
            return;
        }

        $restored = 0;

        $model = $bread->getModel()->withTrashed();

        if ($request->has('ids')) {
            $ids = $request->get('ids');
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            $model->find($ids)->each(function ($entry) use ($bread, &$restored) {
                if ($entry->trashed()) {
                    $this->authorize('restore', $entry);
                    $entry->restore();
                    $restored++;
                }
            });
        }

        return $restored;
    }
}
