<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([TenantScope::class])]
class Ingredient extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function options()
    {
        return $this->hasMany(ProductAttributeOption::class, 'ingredient_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query)
    {
        return $query->orderBy('category')->orderBy('position');
    }
}
