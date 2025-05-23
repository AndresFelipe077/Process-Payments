<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PaymentPlatformSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(PlanSeeder::class);
    }
}
