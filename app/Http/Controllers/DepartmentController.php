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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string'],
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
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name,' . $department->id,
            ],
            'description' => ['nullable', 'string'],
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
                $query->whereIn('name', ['admin', 'teacher', 'instructor']);
            })
            ->orderBy('name')
            ->get();

        return view('departments.assign', compact('departments', 'users'));
    }

    /**
     * Assign a department to an admin/teacher/instructor.
     */
    public function assignStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $isAllowed = $user->roles()
            ->whereIn('name', ['admin', 'teacher', 'instructor'])
            ->exists();

        if (!$isAllowed) {
            return back()
                ->with('error', 'Only admins, teachers, and instructors can be assigned to a department.');
        }

        $user->update([
            'department_id' => $validated['department_id'],
        ]);

        return redirect()
            ->route('departments.assign')
            ->with('success', 'Department assigned successfully.');
    }
}