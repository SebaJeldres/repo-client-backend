<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{

    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'price',
        'cost_price',
        'stock',
        'minimum_stock',
        'category_id',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
