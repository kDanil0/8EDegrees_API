<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Customer1',
                'contactNum' => '09123456789',
                'points' => 0,
                'eligibleForRewards' => false,
            ],
            [
                'name' => 'Customer2',
                'contactNum' => '09091234567',
                'points' => 0,
                'eligibleForRewards' => false,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
} 