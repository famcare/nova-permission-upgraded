<?php

namespace Vyuldashev\NovaPermission;

use Auth;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class RoleSelect extends Select
{
    public function __construct($name, $attribute = null, callable $resolveCallback = null, $labelAttribute = null)
    {
        parent::__construct(
            $name,
            $attribute,
            $resolveCallback ?? static function (?Collection $roles) {
                return optional(($roles ?? collect())->first())->name;
            }
        );

        $roleClass = app(PermissionRegistrar::class)->getRoleClass();

        $options = $roleClass::all()->filter(function ($role) {
            return Auth::user()->can('view', $role);
        })->pluck($labelAttribute ?? 'name', 'name');

        $this->options($options);
    }

    /**
     * @param NovaRequest $request
     * @param mixed $requestAttribute
     * @param mixed $model
     * @param mixed $attribute
     *
     * @return void
     */
    protected function fillAttributeFromRequest(NovaRequest $request, mixed $requestAttribute, mixed $model, mixed $attribute): void
    {
        if (!in_array(HasRoles::class, class_uses_recursive($model))) {
            throw new \InvalidArgumentException('The $model parameter of type ' . $model::class . ' must implement ' . HasRoles::class);
        }

        if (!$request->exists($requestAttribute)) {
            return;
        }

        $model->syncRoles([]);

        if (!is_null($request[$requestAttribute])) {
            $roleClass = app(PermissionRegistrar::class)->getRoleClass();
            $role = $roleClass::where('name', $request[$requestAttribute])->first();
            $model->assignRole($role);
        }
    }

    /**
     * Display values using their corresponding specified labels.
     *
     * @return $this
     */
    public function displayUsingLabels(): RoleSelect
    {
        return $this->displayUsing(function ($value) {
            return collect($this->meta['options'])
                ->where('value', optional($value->first())->name)
                ->first()['label'] ?? optional($value->first())->name;
        });
    }
}
