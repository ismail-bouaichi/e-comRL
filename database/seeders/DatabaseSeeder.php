<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Image;
use App\Models\ShippingZone;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
     
        $categories = \App\Models\Category::factory(10)->create();
        
        // Create brands
        $brands = \App\Models\Brand::factory(10)->create();
        
        // Create products with existing categories and brands
        $products = \App\Models\Product::factory(50)
            ->recycle($categories)
            ->recycle($brands)
            ->create();

        // Add images to products
        $products->each(function ($product) {
            \App\Models\Image::factory(rand(1, 4))
                ->create(['product_id' => $product->id]);
        });




    }
}
