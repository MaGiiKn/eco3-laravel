<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'is_active',
        'sku',
        'slug',
        'image',
        'is_featured',
        'stock',
        'views',
        'sales',
        'rating',
        'rating_count',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Accesor para precio formateado sin decimales y con separador de miles
    public function getPriceFormattedAttribute(): string
    {
        $amount = (int) round($this->price ?? 0);
        // Formato: miles con punto, sin decimales
        return number_format($amount, 0, ',', '.');
    }
}
