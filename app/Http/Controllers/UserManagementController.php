<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Department;
use App\Models\Role;
use App\Models\StudentApplication;
use App\Models\User;
use App\Support\StudyLoads;
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
     * Create Account form (Manuscript Figure 31: "Add User").
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('users.create', compact('departments'));
    }

    /**
     * Create a new account (for example, an Administrator / Department Head).
     *
     * Teachers enter the EDP queue and confirm their email after review.
     * Other EDP-created accounts keep the existing trusted setup.
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

        $review = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'department_id' => $validated['department_id'] ?? null,
            ]);

            $user->forceFill([
                'email_verified_at' => $validated['role'] === 'teacher' ? null : now(),
                'is_active' => true,
            ])->save();

            $role = Role::where('name', $validated['role'])->firstOrFail();

            $user->roles()->sync([$role->id]);

            return $role->name === 'teacher'
                ? StudentApplication::submitForTeacher($user, 'edp_created')
                : null;
        });

        return redirect()
            ->route('users.index')
            ->with('success', $review
                ? 'Teacher account created. Review the registration in Applications before the teacher verifies their email.'
                : 'Account for ' . $validated['name'] . ' created successfully.');
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

        $review = DB::transaction(function () use ($user, $role) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $enteringTeacherRole = $role->name === 'teacher' && ! $user->hasRole('teacher');
            $user->roles()->sync([$role->id]);

            if (! $enteringTeacherRole) {
                return null;
            }

            $user->forceFill(['email_verified_at' => null])->save();

            return StudentApplication::submitForTeacher($user, 'role_change');
        });

        return back()->with(
            'success',
            $review
                ? 'Teacher role assigned. EDP review and email confirmation are required before teacher access.'
                : 'User role updated successfully.'
        );
    }

    public function queueTeacherReview(User $user)
    {
        $review = DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($user->hasRole('teacher'), 403);

            return $user->currentTeacherApplication()
                ?? StudentApplication::submitForTeacher($user, 'edp_legacy_review');
        });

        return redirect()->route('super-admin.student-applications.show', $review);
    }

    /**
     * Mark a user's email address as verified (Manuscript FR (n): the
     * Super Admin can verify user accounts). The EDP vouches for the address,
     * for example when the user did not receive the verification email.
     */
    public function verifyEmail(User $user)
    {
        return DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->hasRole('teacher')) {
                return back()->with('error', 'Teachers confirm their own email using the link sent after EDP approval.');
            }

            if ($user->hasVerifiedEmail()) {
                return back()->with('success', $user->name.'\'s email is already verified.');
            }

            if ($user->awaitingStudentApproval()) {
                return back()->with('error', $user->name.' has a student application that is still '.$user->applicationStatus().'. Approve it in Student Applications first; the student then verifies their email.');
            }

            $user->forceFill(['email_verified_at' => now()])->save();

            return back()->with('success', $user->name.'\'s email has been marked as verified.');
        });
    }

    /**
     * Edit a user's name and email. Accounts are never deleted here.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => $this->nameRules(),
            // RFC format check only (no DNS lookup), like account creation.
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->updateAccountIdentity($validated);

        return redirect()
            ->route('users.index')
            ->with('success', $user->name.'\'s details have been updated.');
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
