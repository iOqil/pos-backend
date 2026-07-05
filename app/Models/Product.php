<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sku', 'barcode', 'category_id', 'brand_id', 'price', 'cost_price', 'tax_rate', 'stock_quantity', 'low_stock_alert', 'type', 'has_variants', 'image', 'images', 'is_active'];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
        ];
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
}
