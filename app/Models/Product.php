<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([TenantScope::class])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'description',
        'price',
        'points_price',
        'stock',
        'image_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'points_price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function imageUrl(): string
    {
        if (!$this->image_url) {
            return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=80';
        }
        if (str_starts_with($this->image_url, 'products/')) {
            return \Illuminate\Support\Facades\Storage::url($this->image_url);
        }
        return $this->image_url;
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
