<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');

        // Default settings if empty
        if ($settings->isEmpty()) {
            $settings = [
                'app_name' => 'Antigravity Workspace',
                'company_name' => 'Antigravity Solutions',
                'contact_email' => 'admin@antigravity.com',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'primary_color' => '#6366f1',
            ];
        }

        return Inertia::render('Settings/Index', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array'
        ]);

        foreach ($data['settings'] as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()->with('success', 'System configurations deployed successfully.');
    }
}
