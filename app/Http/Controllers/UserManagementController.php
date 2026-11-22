<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\StudentApplication;
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

            AuditLog::record(AuditLog::ACCOUNT_CREATED, $user, null, $validated['role']);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Account for ' . $validated['name'] . ' created successfully.');
    }

    /**
     * Change a user's role (Super Admin only; the route is inside
     * `role:super_admin`).
     *
     *  - Nobody can change their own role.
     *  - Teacher: a short reason is required; the Super Admin's change
     *    counts as approval of a pending or rejected application.
     *  - Super Admin: the acting Super Admin re-enters their password.
     *  - The last active Super Admin cannot be demoted.
     * Every change is written to the audit log.
     */
    public function updateRole(Request $request, User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot change your own role. Ask another Super Admin.');
        }

        $promotingToSuperAdmin = $request->input('role') === 'super_admin' && ! $user->hasRole('super_admin');

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'reason' => [
                Rule::requiredIf(fn () => $request->input('role') === 'teacher'),
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
            'current_password' => [
                Rule::requiredIf($promotingToSuperAdmin),
                'nullable',
                'string',
                'current_password',
            ],
        ], [
            'reason.required' => 'Please give a short reason for making this user a Teacher.',
            'reason.min' => 'The reason must be at least 5 characters.',
            'current_password.required' => 'Please re-enter your password to make this user a Super Admin.',
            'current_password.current_password' => 'The password you entered is incorrect.',
        ]);

        $newRole = $validated['role'];
        $oldRoles = $user->roles()->pluck('name')->sort()->values();
        $oldValue = $oldRoles->implode(', ') ?: 'none';

        if ($oldRoles->all() === [$newRole]) {
            return back()->with('success', $user->name.' already has the '.$this->roleLabel($newRole).' role.');
        }

        if ($oldRoles->contains('super_admin') && $newRole !== 'super_admin' && ! $this->anotherActiveSuperAdminExists($user)) {
            return back()->with('error', 'You cannot change the role of the last active Super Admin.');
        }

        $role = Role::where('name', $newRole)->firstOrFail();
        $reason = $validated['reason'] ?? null;
        $approvedApplication = false;

        DB::transaction(function () use ($user, $role, $newRole, $oldValue, $reason, &$approvedApplication) {
            // Replace the user's current role with the selected role.
            $user->roles()->sync([$role->id]);

            AuditLog::record(AuditLog::ROLE_CHANGED, $user, $oldValue, $newRole, $reason);

            // Making someone a Teacher is the Super Admin's approval: a
            // pending or rejected application must not keep them locked out.
            if ($newRole === 'teacher') {
                $latest = $user->latestStudentApplication()->first();

                if ($latest && in_array($latest->status, StudentApplication::AWAITING, true)) {
                    $previousStatus = $latest->status;

                    $approvedApplication = StudentApplication::query()
                        ->whereKey($latest->id)
                        ->where('status', $previousStatus)
                        ->update([
                            'status' => StudentApplication::APPROVED,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_reason' => null,
                            'updated_at' => now(),
                        ]) === 1;

                    if ($approvedApplication) {
                        AuditLog::record(
                            AuditLog::APPLICATION_APPROVED,
                            $user,
                            $previousStatus,
                            StudentApplication::APPROVED,
                            'Approved by changing the role to Teacher: '.$reason,
                        );
                    }
                }
            }
        });

        return back()->with(
            'success',
            $user->name.' is now '.$this->roleLabel($newRole).'.'
                .($approvedApplication ? ' Their pending application was approved by this change.' : '')
        );
    }

    /**
     * Mark a user's email address as verified (Manuscript FR (n): the
     * Super Admin can verify user accounts). The EDP vouches for the address,
     * for example when the user did not receive the verification email.
     * A short reason is required and logged. It never approves a pending
     * student or teacher application; that stays an EDP review action.
     */
    public function verifyEmail(Request $request, User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot mark your own email as verified.');
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', $user->name.'\'s email is already verified.');
        }

        // A student applicant is approved first (Student Applications); the
        // approval sends the verification link.
        if ($user->awaitingTeacherApproval()) {
            return back()->with('error', $user->name.' has a teacher registration that is still '.$user->applicationStatus().'. Approve it in Applications first; the teacher then verifies their email.');
        }

        if ($user->awaitingStudentApproval()) {
            return back()->with('error', $user->name.' has a student application that is still '.$user->applicationStatus().'. Approve it in Student Applications first; the student then verifies their email.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Please give a short reason for marking this email as verified.',
            'reason.min' => 'The reason must be at least 5 characters.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->forceFill(['email_verified_at' => now()])->save();

            AuditLog::record(AuditLog::EMAIL_VERIFIED, $user, 'not verified', 'verified', $validated['reason']);
        });

        return back()->with('success', $user->name.'\'s email has been marked as verified.');
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

        $emailChanged = strcasecmp($validated['email'], $user->email) !== 0;
        $oldEmail = $user->email;

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            // A new address entered by the EDP is treated as verified, like
            // accounts the EDP creates, so the user is not locked out.
            'email_verified_at' => $emailChanged ? now() : $user->email_verified_at,
        ])->save();

        if ($emailChanged) {
            AuditLog::record(AuditLog::EMAIL_CHANGED, $user, $oldEmail, $user->email, 'Changed by the Super Admin; the new address is treated as verified.');
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
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $activate = (bool) $validated['is_active'];

        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                $activate ? 'You cannot change your own account status.' : 'You cannot deactivate your own account.'
            );
        }

        if (! $activate && $user->hasRole('super_admin') && ! $this->anotherActiveSuperAdminExists($user)) {
            return back()->with('error', 'You cannot deactivate the last active Super Admin.');
        }

        $wasActive = $user->is_active !== false;

        if ($wasActive === $activate) {
            return back()->with('success', $user->name.' is already '.($activate ? 'active.' : 'deactivated.'));
        }

        DB::transaction(function () use ($user, $activate, $validated) {
            $user->forceFill([
                'is_active' => $activate,
            ])->save();

            AuditLog::record(
                $activate ? AuditLog::ACTIVATED : AuditLog::DEACTIVATED,
                $user,
                $activate ? 'deactivated' : 'active',
                $activate ? 'active' : 'deactivated',
                $validated['reason'] ?? null,
            );
        });

        return back()->with(
            'success',
            $user->name . ($activate ? ' has been activated.' : ' has been deactivated.')
        );
    }

    /**
     * True when an active Super Admin other than $user exists.
     */
    private function anotherActiveSuperAdminExists(User $user): bool
    {
        return User::query()
            ->whereKeyNot($user->id)
            // A missing value counts as active (see EnsureUserIsActive).
            ->where(fn ($query) => $query->where('is_active', true)->orWhereNull('is_active'))
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->exists();
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'teacher' => 'Teacher',
            'student' => 'Student',
            default => ucfirst($role),
        };
    }
}
