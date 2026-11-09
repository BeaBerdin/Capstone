<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Http\Request;

class CourseCategoryController extends Controller
{
    public function index()
    {
        $categories = CourseCategory::latest()->get();

        return view('course-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('course-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255|unique:course_categories,name',
            'description' => 'nullable'
        ]);

        CourseCategory::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()
            ->route('course-categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(CourseCategory $courseCategory)
    {
        return view('course-categories.edit', compact('courseCategory'));
    }

    public function update(Request $request, CourseCategory $courseCategory)
    {
        $request->validate([
            // Same rule as store(): names stay unique; the category's own
            // current name is allowed.
            'name' => 'required|max:255|unique:course_categories,name,'.$courseCategory->id,
            'description' => 'nullable'
        ]);

        $courseCategory->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()
            ->route('course-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(CourseCategory $courseCategory)
    {
        // courses.category_id cascades on delete, so removing a category that
        // still has courses would also remove those courses and everything
        // linked to them. Refuse while any course (of any status) uses it.
        $courseCount = $courseCategory->courses()->count();

        if ($courseCount > 0) {
            return redirect()
                ->route('course-categories.index')
                ->with('error', sprintf(
                    '"%s" cannot be deleted because it still has %d %s. Move %s to another category or delete %s first.',
                    $courseCategory->name,
                    $courseCount,
                    $courseCount === 1 ? 'course' : 'courses',
                    $courseCount === 1 ? 'that course' : 'those courses',
                    $courseCount === 1 ? 'it' : 'them',
                ));
        }

        $courseCategory->delete();

        return redirect()
            ->route('course-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}