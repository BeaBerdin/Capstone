<?php

namespace App\Http\Controllers;

use App\Models\Department;
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

        return view('super-admin.departments.index', compact('departments'));
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
            ->route('super-admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Update an existing department.
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
            ->route('super-admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Delete a department.
     */
    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()
            ->route('super-admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}