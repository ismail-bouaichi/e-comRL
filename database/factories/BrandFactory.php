<?php

namespace Database\Factories;
use App\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */

 class BrandFactory extends Factory
 {
     protected $model = Brand::class;
 
     public function definition(): array
     {
        $name = $this->faker->unique()->company();
        $logoName = 'brand-' . $this->faker->unique()->numberBetween(1, 1000) . '.jpg';
        
        // Generate and save the image
        $this->faker->image(
            storage_path('app/public/logos'),
            400,
            400,
            null,
            false
        );
    
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_path' => 'logos/' . $logoName,
        ];
     }
 }

