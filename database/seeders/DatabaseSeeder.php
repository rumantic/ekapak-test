<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Товары по категориям
        $categories = ['Двигатель', 'Трансмиссия', 'Подвеска', 'Тормозная система', 'Электрика'];

        foreach ($categories as $category) {
            Product::factory()->count(10)->create([
                'category' => $category,
            ]);
        }

        // Клиенты
        Customer::factory()->count(5)->create();
    }
}
