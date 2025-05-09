<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Main category (1)
            [
                'name' => 'Ribeye Steak Meal',
                'category_id' => 1,
                'description' => 'Premium ribeye steak with sides',
                'quantity' => 50,
                'orderPoint' => 10,
                'costPrice' => 350.00,
                'sellingPrice' => 479.00,
                'supplier_id' => 1,
            ],
            [
                'name' => 'Chicken BBQ Meal',
                'category_id' => 1,
                'description' => 'Grilled BBQ chicken with sides',
                'quantity' => 75,
                'orderPoint' => 15,
                'costPrice' => 180.00,
                'sellingPrice' => 289.00,
                'supplier_id' => 1,
            ],
            [
                'name' => 'Pork Ribs Meal',
                'category_id' => 1,
                'description' => 'Slow-cooked pork ribs with sides',
                'quantity' => 60,
                'orderPoint' => 12,
                'costPrice' => 280.00,
                'sellingPrice' => 389.00,
                'supplier_id' => 1,
            ],
            
            // Side category (2)
            [
                'name' => 'Cheesy Nachos',
                'category_id' => 2,
                'description' => 'Crispy nachos with melted cheese and toppings',
                'quantity' => 100,
                'orderPoint' => 20,
                'costPrice' => 150.00,
                'sellingPrice' => 328.00,
                'supplier_id' => 2,
            ],
            [
                'name' => 'Caesar Salad',
                'category_id' => 2,
                'description' => 'Fresh salad with Caesar dressing',
                'quantity' => 80,
                'orderPoint' => 15,
                'costPrice' => 80.00,
                'sellingPrice' => 110.00,
                'supplier_id' => 2,
            ],
            [
                'name' => 'Medium Cole Slaw',
                'category_id' => 2,
                'description' => 'Creamy cole slaw side dish',
                'quantity' => 90,
                'orderPoint' => 18,
                'costPrice' => 60.00,
                'sellingPrice' => 89.00,
                'supplier_id' => 2,
            ],
            
            // Dessert category (3)
            [
                'name' => 'Chocolate Cake',
                'category_id' => 3,
                'description' => 'Rich chocolate cake slice',
                'quantity' => 40,
                'orderPoint' => 10,
                'costPrice' => 95.00,
                'sellingPrice' => 150.00,
                'supplier_id' => 3,
            ],
            [
                'name' => 'Ice Cream Sundae',
                'category_id' => 3,
                'description' => 'Vanilla ice cream with toppings',
                'quantity' => 50,
                'orderPoint' => 15,
                'costPrice' => 80.00,
                'sellingPrice' => 120.00,
                'supplier_id' => 3,
            ],
            
            // Beverage category (4)
            [
                'name' => 'Iced Tea',
                'category_id' => 4,
                'description' => 'Refreshing iced tea',
                'quantity' => 120,
                'orderPoint' => 30,
                'costPrice' => 30.00,
                'sellingPrice' => 75.00,
                'supplier_id' => 2,
            ],
            [
                'name' => 'Soft Drink',
                'category_id' => 4,
                'description' => 'Cola or other carbonated drink',
                'quantity' => 150,
                'orderPoint' => 40,
                'costPrice' => 25.00,
                'sellingPrice' => 65.00,
                'supplier_id' => 2,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
} 