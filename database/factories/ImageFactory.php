<?php

namespace Database\Factories;
use App\Models\Image;
use App\Models\Product;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        $imageName = 'product-' . $this->faker->unique()->numberBetween(1, 1000) . '.jpg';
    
    // Generate and save the image
    $this->faker->image(
        storage_path('app/public/images'),
        640,
        480,
        null,
        false
    );

    return [
        'product_id' => Product::factory(),
        'file_path' => 'images/' . $imageName,
        'title' => $this->faker->words(3, true),
        'description' => $this->faker->sentence(),
    ];
    }
}

