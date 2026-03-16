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

        // Create granular permissions for each module
        $modules = [
            'projects', 'tasks', 'attendance', 'leads', 'leaves', 
            'users', 'roles', 'announcements', 'settings', 'holidays', 'payroll', 'analytics'
        ];
        
        $actions = ['view', 'create', 'edit', 'delete'];
        $extraPermissions = ['approve leaves', 'download reports', 'manage system'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$action $module"]);
            }
        }

        foreach ($extraPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Employee: Basic access
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions([
            'view tasks', 'view attendance', 'view leads', 'view leaves', 
            'create leaves', 'edit leaves', 'view announcements'
        ]);

        // team lead (lowercase as seen in DB)
        $teamLeadRole = Role::firstOrCreate(['name' => 'team lead']);
        $teamLeadRole->syncPermissions([
            'view tasks', 'create tasks', 'edit tasks', 'view attendance',
            'view leaves', 'view projects', 'view announcements'
        ]);

        // manager (lowercase as seen in DB)
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->syncPermissions([
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks',
            'view attendance', 'manage attendance',
            'view leads', 'create leads', 'edit leads',
            'view leaves', 'approve leaves',
            'view projects', 'create projects', 'edit projects', 'view announcements'
        ]);

        // HR
        $hrRole = Role::firstOrCreate(['name' => 'HR']);
        $hrRole->syncPermissions([
            'view attendance', 'manage attendance', 'view leaves', 
            'approve leaves', 'download reports', 'view users', 'view announcements'
        ]);

        // Admin: Almost everything
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $allPermissions = Permission::all()->pluck('name')->toArray();
        $adminRole->syncPermissions($allPermissions);

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
