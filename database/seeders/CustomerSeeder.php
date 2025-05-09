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
                'name' => 'John Doe',
                'contactNum' => '09123456789',
                'points' => 150,
                'eligibleForRewards' => true,
            ],
            [
                'name' => 'Jane Smith',
                'contactNum' => '09987654321',
                'points' => 75,
                'eligibleForRewards' => false,
            ],
            [
                'name' => 'Mike Johnson',
                'contactNum' => '09123123123',
                'points' => 210,
                'eligibleForRewards' => true,
            ],
            [
                'name' => 'Sarah Williams',
                'contactNum' => '09456456456',
                'points' => 300,
                'eligibleForRewards' => true,
            ],
            [
                'name' => 'Robert Brown',
                'contactNum' => '09789789789',
                'points' => 50,
                'eligibleForRewards' => false,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
} 