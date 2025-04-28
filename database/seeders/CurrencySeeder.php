<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            'usd',
            'eur',
            'gbp',
        ];

        $now = now();

        $currencies = array_map(fn($iso): array => [
            'iso'        => $iso,
            'created_at' => $now,
            'updated_at' => $now
        ], $currencies);

        Currency::insert($currencies);
    }
}
