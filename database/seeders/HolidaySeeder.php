<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            ['name' => 'New Year Day', 'date' => '2026-01-01'],
            ['name' => 'Republic Day', 'date' => '2026-01-26'],
            ['name' => 'Holi Festival', 'date' => '2026-03-14'],
            ['name' => 'Independence Day', 'date' => '2026-08-15'],
            ['name' => 'Gandhi Jayanti', 'date' => '2026-10-02'],
            ['name' => 'Diwali Eve', 'date' => '2026-10-18'],
            ['name' => 'Diwali', 'date' => '2026-10-19'],
            ['name' => 'Bhai Dooj', 'date' => '2026-10-21'],
            ['name' => 'Christmas Eve', 'date' => '2026-12-24'],
            ['name' => 'Christmas Day', 'date' => '2026-12-25'],
        ];

        foreach ($holidays as $h) {
            Holiday::updateOrCreate(['date' => $h['date']], $h);
        }
    }
}
