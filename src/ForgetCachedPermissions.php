<?php

declare(strict_types=1);

namespace Vyuldashev\NovaPermission;

use Laravel\Nova\Nova;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Str;

class ForgetCachedPermissions
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request|mixed $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, $next)
    {
        $response = $next($request);

        if (
            $request->is('nova-api/*/detach') ||
            $request->is('nova-api/*/*/attach*/*')
        ) {
            $permissionKey = Str::plural(Str::kebab(class_basename(app(PermissionRegistrar::class)->getPermissionClass())));

            if ($request->viaRelationship === $permissionKey) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }

        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->headers->set('X-Inertia', 'true');
        }

        return $response;
    }
}
