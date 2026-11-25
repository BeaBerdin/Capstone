<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Notifications\DepartmentAdministratorAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display all departments.
     */
    public function index()
    {
        $departments = Department::withCount('users')
            ->orderBy('name')
            ->get();

        return view('departments.index', compact('departments'));
    }

    /**
     * Show create department form.
     */
    public function create()
    {
        return view('departments.create');
    }

    /**
     * Store a new department.
     */
    public function store(Request $request)
    {
        // Short code: optional, stored in upper case ("ccs" -> "CCS").
        $request->merge(['code' => filled($request->input('code')) ? strtoupper(trim((string) $request->input('code'))) : null]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'code' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
        ], [
            'code.regex' => 'The short code may only contain letters and numbers, e.g. CCS.',
            'code.max' => 'The short code may not be longer than 10 characters.',
            'code.unique' => 'Another department already uses this short code.',
        ]);

        Department::create($validated);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Show edit department form.
     */
    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    /**
     * Update department.
     */
    public function update(Request $request, Department $department)
    {
        // Short code: optional, stored in upper case ("ccs" -> "CCS").
        $request->merge(['code' => filled($request->input('code')) ? strtoupper(trim((string) $request->input('code'))) : null]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name,' . $department->id,
            ],
            'code' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', 'unique:departments,code,' . $department->id],
            'description' => ['nullable', 'string'],
        ], [
            'code.regex' => 'The short code may only contain letters and numbers, e.g. CCS.',
            'code.max' => 'The short code may not be longer than 10 characters.',
            'code.unique' => 'Another department already uses this short code.',
        ]);

        $department->update($validated);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Delete department.
     */
    public function destroy(Department $department)
    {
        // Manuscript Case-053: a department with users is kept. Its users are
        // removed one by one from View users first; then Case-052 applies.
        $assigned = $department->users()->count();

        if ($assigned > 0) {
            return redirect()
                ->route('departments.index')
                ->with('error', "Cannot delete this department because {$assigned} user(s) are assigned to it. Remove them first using View users.");
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    /**
     * Show department assignment page.
     */
    public function assign()
    {
        $departments = Department::orderBy('name')->get();

        // Users already in a department stay listed but cannot be chosen
        // ("Already assigned to ..."): a user belongs to one department.
        $users = User::with(['roles', 'department'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'teacher']);
            })
            ->orderBy('name')
            ->get();

        return view('departments.assign', compact('departments', 'users'));
    }

    /**
     * Assign a department to an admin or teacher.
     */
    public function assignStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $user = User::findOrFail((int) $validated['user_id']);

        $isAllowed = $user->roles()
            ->whereIn('name', ['admin', 'teacher'])
            ->exists();

        if (!$isAllowed) {
            return back()
                ->with('error', 'Only admins and teachers can be assigned to a department.');
        }

        // One department per user: to move a user, remove them first. The
        // conditional update also stops two assignments made at the same time.
        $assigned = User::whereKey($user->id)
            ->whereNull('department_id')
            ->update(['department_id' => $validated['department_id']]);

        if ($assigned === 0) {
            $current = $user->fresh()->department;

            return back()
                ->withInput()
                ->with('error', 'Already assigned to '.($current?->name ?? 'a department').'. To move '.$user->name.', remove them from that department first using View users.');
        }

        // An informational email for Administrators only (not teachers).
        // Only the request that made the assignment gets here, so one email.
        if ($user->hasRole('admin')) {
            $user->notify(new DepartmentAdministratorAssigned(
                Department::findOrFail((int) $validated['department_id'])->name
            ));
        }

        return redirect()
            ->route('departments.assign')
            ->with('success', 'Department assigned successfully.');
    }

    /**
     * The users of one department (View users).
     */
    public function users(Department $department)
    {
        $users = $department->users()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $adminCount = $users->filter(fn (User $user) => $user->hasRole('admin'))->count();

        return view('departments.users', compact('department', 'users', 'adminCount'));
    }

    /**
     * Remove a user from a department. Only the link is cleared: the user's
     * courses, enrollments, progress, quiz results and certificates stay.
     */
    public function removeUser(Department $department, User $user)
    {
        $result = DB::transaction(function () use ($department, $user) {
            $current = User::lockForUpdate()->findOrFail($user->id);

            if ((int) $current->department_id !== (int) $department->id) {
                return null;
            }

            $wasOnlyAdmin = $current->hasRole('admin')
                && ! $department->users()
                    ->whereKeyNot($current->id)
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))
                    ->exists();

            $current->forceFill(['department_id' => null])->save();

            AuditLog::record(AuditLog::DEPARTMENT_REMOVED, $current, $department->name, 'No department');

            return ['name' => $current->name, 'wasOnlyAdmin' => $wasOnlyAdmin];
        });

        if ($result === null) {
            return redirect()
                ->route('departments.users', $department)
                ->with('error', $user->name.' is not assigned to '.$department->name.'.');
        }

        $redirect = redirect()
            ->route('departments.users', $department)
            ->with('success', $result['name'].' was removed from '.$department->name.'. They are now listed under Unassigned Users.');

        if ($result['wasOnlyAdmin']) {
            $redirect->with('warning', $department->name.' now has no Administrator, so no one can review its courses until another Administrator is assigned.');
        }

        return $redirect;
    }
}