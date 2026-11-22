<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Concerns\PersonNameRules;
use App\Concerns\ProfileValidationRules;
use App\Models\AuditLog;
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
    use PasswordValidationRules, PersonNameRules, ProfileValidationRules;

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
        $request->merge($this->normalizePersonName($request->only(['first_name', 'middle_initial', 'last_name'])));

        $validated = $request->validate([
            ...$this->personNameRules(),
            // RFC format check only (no DNS lookup), so accounts can be
            // created even when the server has no internet access.
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ], $this->personNameMessages());

        $nameAttributes = User::nameAttributes($validated['first_name'], $validated['middle_initial'] ?? null, $validated['last_name']);
        $validated['name'] = $nameAttributes['name'];

        $review = DB::transaction(function () use ($validated, $nameAttributes) {
            $user = User::create([
                ...$nameAttributes,
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

    /**
     * Change a user's role (Super Admin only: the route is inside
     * `role:super_admin`). Every change needs a short reason and is written
     * to the audit log. Promoting someone to Super Admin also needs the
     * acting Super Admin's own password. The last active Super Admin cannot
     * be demoted, and nobody can remove their own Super Admin role.
     */
    public function updateRole(Request $request, User $user)
    {
        $promoting = $request->input('role') === 'super_admin' && ! $user->hasRole('super_admin');

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'reason' => ['required', 'string', 'max:500'],
            'current_password' => [
                Rule::requiredIf($promoting),
                'nullable',
                'string',
                'current_password',
            ],
        ], [
            'reason.required' => 'Please give a short reason for this role change.',
            'reason.max' => 'The reason may not be longer than 500 characters.',
            'current_password.required' => 'Please re-enter your password to make this user a Super Admin.',
            'current_password.current_password' => 'The password you entered is incorrect. The role was not changed.',
        ]);

        // Prevent the Super Admin from removing their own Super Admin role
        if (
            $user->id === auth()->id() &&
            $validated['role'] !== 'super_admin'
        ) {
            return back()->with(
                'error',
                'You cannot remove your own Super Admin role.'
            );
        }

        $role = Role::where('name', $validated['role'])->firstOrFail();
        $reason = $validated['reason'];

        $result = DB::transaction(function () use ($user, $role, $reason) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $oldRoles = $user->roles()->pluck('name')->sort()->values();

            if ($oldRoles->all() === [$role->name]) {
                return 'unchanged';
            }

            // The last active Super Admin must stay a Super Admin.
            if (
                $oldRoles->contains('super_admin')
                && $role->name !== 'super_admin'
                && ! $this->anotherActiveSuperAdminExists($user)
            ) {
                return 'last_super_admin';
            }

            $enteringTeacherRole = $role->name === 'teacher' && ! $user->hasRole('teacher');
            $user->roles()->sync([$role->id]);

            AuditLog::record(
                $role->name === 'super_admin' ? AuditLog::PROMOTED_SUPER_ADMIN : AuditLog::ROLE_CHANGED,
                $user,
                $oldRoles->implode(', ') ?: 'none',
                $role->name,
                $reason,
            );

            if (! $enteringTeacherRole) {
                return 'changed';
            }

            $user->forceFill(['email_verified_at' => null])->save();

            StudentApplication::submitForTeacher($user, 'role_change');

            return 'teacher_review';
        });

        return match ($result) {
            'last_super_admin' => back()->with('error', 'You cannot change the role of the last active Super Admin.'),
            'unchanged' => back()->with('success', $user->name.' already has this role.'),
            'teacher_review' => back()->with('success', 'Teacher role assigned. EDP review and email confirmation are required before teacher access.'),
            default => back()->with('success', 'User role updated successfully.'),
        };
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

            AuditLog::record(AuditLog::EMAIL_VERIFIED, $user, 'not verified', 'verified');

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
        $request->merge($this->normalizePersonName($request->only(['first_name', 'middle_initial', 'last_name'])));

        // An account from before the name was split (no parts yet) may be
        // saved with the name fields left empty: its current name is kept.
        $keepLegacyName = $user->first_name === null
            && blank($request->input('first_name'))
            && blank($request->input('last_name'))
            && blank($request->input('middle_initial'));

        $validated = $request->validate([
            ...$this->personNameRules(required: ! $keepLegacyName),
            // RFC format check only (no DNS lookup), like account creation.
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ], $this->personNameMessages());

        $attributes = ['email' => $validated['email']];

        if (! $keepLegacyName) {
            $attributes += User::nameAttributes($validated['first_name'], $validated['middle_initial'] ?? null, $validated['last_name']);
        }

        $before = ['Name' => $user->name, 'Email' => $user->email];

        $user->updateAccountIdentity($attributes);

        $after = ['Name' => $user->name, 'Email' => $user->email];
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed !== []) {
            AuditLog::record(
                AuditLog::DETAILS_UPDATED,
                $user,
                implode('; ', array_map(fn ($field) => $field.': '.$before[$field], $changed)),
                implode('; ', array_map(fn ($field) => $field.': '.$after[$field], $changed)),
            );
        }

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

        if (! $activate && $user->hasRole('super_admin') && ! $this->anotherActiveSuperAdminExists($user)) {
            return back()->with('error', 'You cannot deactivate the last active Super Admin.');
        }

        $wasActive = $user->is_active !== false;

        DB::transaction(function () use ($user, $activate, $wasActive) {
            $user->forceFill([
                'is_active' => $activate,
            ])->save();

            if ($wasActive !== $activate) {
                AuditLog::record(
                    $activate ? AuditLog::ACTIVATED : AuditLog::DEACTIVATED,
                    $user,
                    $wasActive ? 'active' : 'deactivated',
                    $activate ? 'active' : 'deactivated',
                );
            }
        });

        return back()->with(
            'success',
            $user->name . ($activate ? ' has been activated.' : ' has been deactivated.')
        );
    }

    /**
     * True when an active Super Admin other than $user exists. A missing
     * is_active value counts as active (see EnsureUserIsActive).
     */
    private function anotherActiveSuperAdminExists(User $user): bool
    {
        return User::query()
            ->whereKeyNot($user->id)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereNull('is_active'))
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->exists();
    }
}
