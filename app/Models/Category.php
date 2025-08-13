<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    protected $fillable = [
        'name',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucfirst(strtolower(trim($value))),
        );
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
    