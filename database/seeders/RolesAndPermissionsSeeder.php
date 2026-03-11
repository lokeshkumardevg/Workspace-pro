<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage projects',
            'manage tasks',
            'view tasks',
            'manage attendance',
            'view attendance',
            'manage leads',
            'view leads',
            'manage users',
            'manage roles',
            'manage leaves',
            'view leaves',
            'approve leaves',
            'download reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Employee: view only, submit leaves
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions(['view tasks', 'view attendance', 'view leads', 'view leaves', 'manage leaves']);

        // Manager: approve/reject leaves
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->syncPermissions(['view tasks', 'manage tasks', 'view attendance', 'view leads', 'view leaves', 'approve leaves']);

        // HR: full attendance + leave reports download
        $hrRole = Role::firstOrCreate(['name' => 'HR']);
        $hrRole->syncPermissions(['view attendance', 'manage attendance', 'view leaves', 'manage leaves', 'approve leaves', 'download reports']);

        // Admin
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions([
            'manage projects',
            'manage tasks',
            'view tasks',
            'view attendance',
            'manage leads',
            'view leads',
            'manage users',
            'view leaves',
            'manage leaves',
            'approve leaves',
            'download reports'
        ]);

        // Super Admin gets everything via Gate::before
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Create demo users
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            ['name' => 'Super Admin User', 'password' => bcrypt('password')]
        );
        $superadmin->assignRole($superAdminRole);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );
        $admin->assignRole($adminRole);

        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Manager User', 'password' => bcrypt('password')]
        );
        $manager->assignRole($managerRole);

        $hr = User::firstOrCreate(
            ['email' => 'hr@example.com'],
            ['name' => 'HR User', 'password' => bcrypt('password')]
        );
        $hr->assignRole($hrRole);

        $employee = User::firstOrCreate(
            ['email' => 'employee@example.com'],
            ['name' => 'Employee User', 'password' => bcrypt('password')]
        );
        $employee->assignRole($employeeRole);
    }
}
