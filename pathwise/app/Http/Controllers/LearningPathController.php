<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\QuizResult;
use Illuminate\Http\Request;

class LearningPathController extends Controller
{
    public function index()
    {
        $learningPaths = LearningPath::with(['courses', 'student'])
            ->latest()
            ->get();

        return view('learning-paths.index', compact('learningPaths'));
    }

    public function create()
    {
        $courses = Course::where('is_free', false)->orderBy('title')->get();

        return view('learning-paths.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $learningPath = LearningPath::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_generated' => false,
        ]);

        $this->syncCoursesWithOrder(
            $learningPath,
            collect($request->course_ids ?? [])
        );

        return redirect()
            ->route('learning-paths.index')
            ->with('success', 'Learning path created successfully.');
    }

    public function show(LearningPath $learningPath)
    {
        $learningPath->load('courses.category', 'student');

        return view('learning-paths.show', compact('learningPath'));
    }

    public function edit(LearningPath $learningPath)
    {
        $courses = Course::where('is_free', false)->orderBy('title')->get();

        $selectedCourses = $learningPath->courses()
            ->pluck('courses.id')
            ->toArray();

        return view(
            'learning-paths.edit',
            compact('learningPath', 'courses', 'selectedCourses')
        );
    }

    public function update(Request $request, LearningPath $learningPath)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $learningPath->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $this->syncCoursesWithOrder(
            $learningPath,
            collect($request->course_ids ?? [])
        );

        return redirect()
            ->route('learning-paths.index')
            ->with('success', 'Learning path updated successfully.');
    }

    public function destroy(LearningPath $learningPath)
    {
        $learningPath->courses()->detach();

        $learningPath->delete();

        return redirect()
            ->route('learning-paths.index')
            ->with('success', 'Learning path deleted successfully.');
    }

    public function generateForStudent()
    {
        $studentId = auth()->id();

        $bestQuizResult = QuizResult::with('quiz.course.category')
            ->where('student_id', $studentId)
            ->whereHas('quiz.course', function ($query) {
                $query->where('is_free', false);
            })
            ->orderByDesc('percentage')
            ->first();

        if (!$bestQuizResult || !$bestQuizResult->quiz || !$bestQuizResult->quiz->course) {
            return redirect()
                ->route('student.learning-paths')
                ->with('error', 'No quiz results found. Complete a quiz first to generate your AI learning path.');
        }

        if ($bestQuizResult->quiz->course->is_free) {
            return redirect()
                ->route('student.learning-paths')
                ->with('info', 'Free courses are not included in AI learning paths.');
        }

        $averageScore = QuizResult::where('student_id', $studentId)
            ->whereHas('quiz.course', function ($query) {
                $query->where('is_free', false);
            })
            ->avg('percentage') ?? 0;

        if ($averageScore >= 85) {
            $difficulty = 'advanced';
        } elseif ($averageScore >= 70) {
            $difficulty = 'intermediate';
        } else {
            $difficulty = 'beginner';
        }

        $category = $bestQuizResult->quiz->course->category;

        $pathName = $category
            ? $category->name . ' Learning Path'
            : 'Personalized Learning Path';

        $description =
            'Generated based on your learning performance. ' .
            'Strongest Category: ' . ($category->name ?? 'General') .
            '. Average Quiz Score: ' . round($averageScore, 2) . '%' .
            '. Recommended courses match your current skill level (' .
            ucfirst($difficulty) . ').';

        $existingPath = LearningPath::where('student_id', $studentId)
            ->where('is_generated', true)
            ->first();

        if ($existingPath) {
            $existingPath->courses()->detach();

            $existingPath->update([
                'name' => $pathName,
                'description' => $description,
                'difficulty_level' => $difficulty,
                'is_generated' => true,
            ]);

            $learningPath = $existingPath;
        } else {
            $learningPath = LearningPath::create([
                'student_id' => $studentId,
                'name' => $pathName,
                'description' => $description,
                'difficulty_level' => $difficulty,
                'is_generated' => true,
            ]);
        }

        $courses = Course::where('category_id', $bestQuizResult->quiz->course->category_id)
            ->where('status', 'published')
            ->where('is_free', false)
            ->has('lessons')
            ->has('quizzes')
            ->orderByRaw("
                CASE difficulty_level
                    WHEN 'beginner' THEN 1
                    WHEN 'intermediate' THEN 2
                    WHEN 'advanced' THEN 3
                    ELSE 4
                END
            ")
            ->get();

        if ($courses->count() < 3) {
            $additionalCourses = Course::where('status', 'published')
                ->where('is_free', false)
                ->whereNotIn('id', $courses->pluck('id'))
                ->has('lessons')
                ->has('quizzes')
                ->orderByRaw("
                    CASE difficulty_level
                        WHEN 'beginner' THEN 1
                        WHEN 'intermediate' THEN 2
                        WHEN 'advanced' THEN 3
                        ELSE 4
                    END
                ")
                ->take(3 - $courses->count())
                ->get();

            $courses = $courses->merge($additionalCourses);
        }

        if ($courses->isEmpty()) {
            return redirect()
                ->route('student.learning-paths')
                ->with('error', 'No complete published courses available for learning path generation.');
        }

        $this->syncCoursesWithOrder($learningPath, $courses);

        return redirect()
            ->route('student.learning-paths.show', $learningPath)
            ->with('success', 'AI learning path generated successfully.');
    }

    public function studentIndex()
    {
        $learningPaths = LearningPath::with(['courses' => function ($query) {
            $query->where('is_free', false);
        }])
            ->where(function ($query) {
                $query->whereNull('student_id')
                    ->orWhere('student_id', auth()->id());
            })
            ->latest()
            ->get();

        return view('student.learning-paths.index', compact('learningPaths'));
    }

    public function studentShow(LearningPath $learningPath)
    {
        if ($learningPath->student_id !== null && $learningPath->student_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $learningPath->load([
            'courses' => function ($query) {
                $query->where('is_free', false);
            },
            'courses.category'
        ]);

        return view('student.learning-paths.show', compact('learningPath'));
    }

    private function syncCoursesWithOrder(LearningPath $learningPath, $courses): void
    {
        $syncData = [];

        foreach ($courses->values() as $index => $course) {
            $courseId = is_object($course) ? $course->id : $course;

            $syncData[$courseId] = [
                'course_order' => $index + 1,
            ];
        }

        $learningPath->courses()->sync($syncData);
    }
}