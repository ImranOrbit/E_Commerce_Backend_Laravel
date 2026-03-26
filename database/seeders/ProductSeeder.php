<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::create([
            'name' => 'Vitamin C Face Serum',
            'price' => 29,
            'category' => 'Skincare',
            'image' => 'https://images.unsplash.com/photo-1611930022073-b7a4ba5fcccd'
        ]);
    }
}
