<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions')->orderBy('id', 'desc');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $roles = $query->paginate(5)->withQueryString();
        $allPermissions = Permission::all();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return back()->with('success', '✅ Role created successfully');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return back()->with('success', '✅ Role updated successfully');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return back()->with('error', '❌ Cannot delete Super Admin');
        }
        $role->delete();
        return back()->with('success', '🗑️ Role deleted successfully');
    }
}
