<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $user = Auth::check() ? Auth::user() : null;
            if ($user && $user->tenant_id && (! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin())) {
                $model->tenant_id = $user->tenant_id;
            }
        });

        static::saving(function (Model $model): void {
            $user = Auth::check() ? Auth::user() : null;
            if ($user && $user->tenant_id && (! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin())) {
                $currentTenantId = $user->tenant_id;
                if ($model->isDirty('tenant_id') && $model->getOriginal('tenant_id') !== null) {
                    throw new \RuntimeException('Cannot change tenant_id of an existing resource.');
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
