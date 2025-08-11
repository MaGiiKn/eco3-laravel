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
}
