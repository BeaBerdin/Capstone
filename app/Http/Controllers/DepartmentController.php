<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

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
        if ($department->users()->exists()) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Cannot delete this department because users are assigned to it.');
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

        $users = User::with('roles')
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

        $user->update([
            'department_id' => $validated['department_id'],
        ]);

        return redirect()
            ->route('departments.assign')
            ->with('success', 'Department assigned successfully.');
    }
}