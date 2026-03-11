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

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::all();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only('search')
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'allowed_ip' => 'nullable|string|max:45',
        ]);

        $user->update($validated);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return back()->with('success', '✅ User updated successfully');
    }

    public function syncRoles(Request $request, User $user)
    {
        $request->validate(['roles' => 'array']);
        $user->syncRoles($request->roles);
        return back()->with('success', '✅ User roles updated successfully');
    }
}
