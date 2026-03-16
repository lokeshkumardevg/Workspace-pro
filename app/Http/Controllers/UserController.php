<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')->orderBy('id', 'desc');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users   = $query->paginate(15)->withQueryString();
        $roles   = Role::all();
        $authUser = $request->user();

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'roles'   => $roles,
            'filters' => $request->only('search'),
            'isSuperAdmin' => $authUser->hasRole('Super Admin'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'allowed_ip'       => 'nullable|string|max:45',
            'joining_date'     => 'nullable|date',
            'probation_months' => 'nullable|integer|min:0',
            'designation'      => 'nullable|string|max:255',
            'employee_id'      => 'nullable|string|max:50',
            'base_salary'      => 'nullable|numeric|min:0',
            'department'       => 'nullable|string|max:255',
            'attendance_bypass' => 'nullable|boolean',
        ]);

        $user->update($validated);

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return back()->with('success', '✅ User updated successfully');
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself or a Super Admin
        if (auth()->id() === $user->id) {
            return back()->with('error', '❌ You cannot delete your own account.');
        }

        if ($user->hasRole('Super Admin')) {
            return back()->with('error', '❌ Cannot delete Super Admin account.');
        }

        $user->delete();
        return back()->with('success', '🗑️ User account deleted successfully.');
    }

    public function syncRoles(Request $request, User $user)
    {
        $request->validate(['roles' => 'array']);
        $user->syncRoles($request->roles);
        return back()->with('success', '✅ User roles updated successfully');
    }
}
