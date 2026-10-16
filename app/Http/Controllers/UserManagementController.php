<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Super Admin (EDP) user management.
 *
 * Manuscript: Functional Requirement (n) "The Super Admin can manage,
 * verify, activate, and deactivate user accounts, as well as assign user
 * roles" and Figure 31 "Manage User Accounts and Roles".
 */
class UserManagementController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function index()
    {
        $users = User::with(['roles', 'department'])
            ->latest()
            ->get();

        $roles = Role::orderBy('name')->get();

        $departments = Department::orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'departments'));
    }

    /**
     * Create a new account (for example, an Administrator / Department Head).
     *
     * Accounts created by the EDP are marked as email-verified because the
     * Super Admin is vouching for them. The new user should change the
     * temporary password after the first login.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => $this->nameRules(),
            // RFC format check only (no DNS lookup), so accounts can be
            // created even when the server has no internet access.
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'department_id' => $validated['department_id'] ?? null,
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();

            $role = Role::where('name', $validated['role'])->firstOrFail();

            $user->roles()->sync([$role->id]);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Account for ' . $validated['name'] . ' created successfully.');
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

    /**
     * Activate or deactivate an account.
     *
     * A deactivated user cannot use the system: EnsureUserIsActive logs
     * them out on their next request. No data is deleted.
     */
    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $activate = (bool) $validated['is_active'];

        if ($user->id === auth()->id() && ! $activate) {
            return back()->with(
                'error',
                'You cannot deactivate your own account.'
            );
        }

        $user->forceFill([
            'is_active' => $activate,
        ])->save();

        return back()->with(
            'success',
            $user->name . ($activate ? ' has been activated.' : ' has been deactivated.')
        );
    }
}
