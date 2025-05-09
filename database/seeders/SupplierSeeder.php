<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'id' => 1,
                'name' => 'ABC Corporation',
                'contactNum' => '09274648294',
                'address' => 'Mabalacat City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Mekeni',
                'contactNum' => '09123456789',
                'address' => 'Angeles City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'SteakPorter',
                'contactNum' => '09234145364',
                'address' => 'Angeles City',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'John Supermarket',
                'contactNum' => '09090099009',
                'address' => 'Manila',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('suppliers')->insert($suppliers);
    }
} 