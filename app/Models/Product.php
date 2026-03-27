<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'category',
        'image',
        'images', // Add this for multiple images
        'description' // Add description field
    ];

    protected $casts = [
        'images' => 'array', // Cast images as array
        'price' => 'decimal:2'
    ];

    public function offer()
    {
        return $this->hasOne(Offer::class);
    }
}