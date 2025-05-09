<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rewards = [
            [
                'name' => 'Free Dessert',
                'description' => 'Get a free dessert with your next order',
                'pointsNeeded' => 100,
            ],
            [
                'name' => '10% Off Your Order',
                'description' => 'Get 10% off your entire order',
                'pointsNeeded' => 200,
            ],
            [
                'name' => 'Free Side Dish',
                'description' => 'Get a free side dish with your next order',
                'pointsNeeded' => 150,
            ],
            [
                'name' => 'Free Beverage',
                'description' => 'Get a free beverage with your next order',
                'pointsNeeded' => 75,
            ],
            [
                'name' => '20% Off Your Order',
                'description' => 'Get 20% off your entire order',
                'pointsNeeded' => 300,
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::create($reward);
        }
    }
} 