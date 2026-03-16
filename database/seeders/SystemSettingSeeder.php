<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'app_name' => 'Task Master Pro',
            'company_name' => 'Enterprise Solutions',
            'contact_email' => 'support@example.com',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'primary_color' => '#4f46e5',
            'office_lat' => '28.6139',
            'office_lng' => '77.2090',
            'office_radius' => '200',
            'default_probation_months' => '3',
            'footer_text' => '© 2026 Task Management System. All Rights Reserved.',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
