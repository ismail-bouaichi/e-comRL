<?php

namespace Database\Factories;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {  $name = $this->faker->unique()->words(2, true);
        $iconName = 'category-' . $this->faker->unique()->numberBetween(1, 1000) . '.jpg';
        
        // Generate and save the image
        $this->faker->image(
            storage_path('app/public/icons'),
            400,
            400,
            null,
            false
        );
    
        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'icon' => 'icons/' . $iconName,
        ];
    }
}
