<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->latest()
            ->get();

        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        // Prevent the Super Admin from removing their own Super Admin role
        if (
            $user->id === auth()->id() &&
            $request->role !== 'super_admin'
        ) {
            return back()->with(
                'error',
                'You cannot remove your own Super Admin role.'
            );
        }

        $role = Role::where('name', $request->role)->firstOrFail();

        // Replace the user's current role with the selected role
        $user->roles()->sync([$role->id]);

        return back()->with(
            'success',
            'User role updated successfully.'
        );
    }
}