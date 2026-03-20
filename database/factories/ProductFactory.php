<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static array $categories = [
        'Двигатель',
        'Трансмиссия',
        'Подвеска',
        'Тормозная система',
        'Электрика',
    ];

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('???-#####'),
            'price' => fake()->randomFloat(2, 100, 50000),
            'stock_quantity' => fake()->numberBetween(0, 200),
            'category' => fake()->randomElement(self::$categories),
        ];
    }
}
