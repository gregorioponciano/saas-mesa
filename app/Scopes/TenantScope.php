<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->resolveTenantId();

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    private function resolveTenantId(): ?int
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            $user = Auth::user();

            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return null;
            }

            return $user->tenant_id;
        }

        $request = request();
        if ($request && $request->has('current_tenant')) {
            return $request->get('current_tenant')->id;
        }

        return null;
    }
}
