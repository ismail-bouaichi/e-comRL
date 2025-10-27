<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'name' => ucwords($name),
            'description' => $this->faker->paragraphs(2, true),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
        ];
    }

    // Add states for common scenarios
    public function outOfStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock_quantity' => 0,
            ];
        });
    }

    public function lowStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock_quantity' => $this->faker->numberBetween(1, 5),
            ];
        });
    }
}