<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            [
                'name' => 'Senior Citizen',
                'percentage' => 20.00,
                'description' => 'Senior citizen discount 20%',
                'is_active' => true,
            ],
            [
                'name' => 'PWD Discount',
                'percentage' => 20.00,
                'description' => 'Persons with disability discount 20%',
                'is_active' => true,
            ],
            [
                'name' => 'Employee Discount',
                'percentage' => 10.00,
                'description' => 'Employee discount 10%',
                'is_active' => true,
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::create($discount);
        }
    }
}
