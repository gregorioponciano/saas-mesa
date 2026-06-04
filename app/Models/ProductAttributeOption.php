<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeOption extends Model
{
    protected $fillable = [
        'product_attribute_id',
        'ingredient_id',
        'name',
        'price_additional',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'price_additional' => 'decimal:2',
        ];
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
