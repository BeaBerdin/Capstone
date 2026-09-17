<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\QuizResult;
use Illuminate\Http\Request;

class LearningPathController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - LEARNING PATHS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $learningPaths = LearningPath::with([
                'courses',
                'student',
            ])
            ->latest()
            ->get();

        return view(
            'learning-paths.index',
            compact('learningPaths')
        );
    }


    public function create()
    {
        $courses = Course::where(
                'is_free',
                false
            )
            ->orderBy('title')
            ->get();

        return view(
            'learning-paths.create',
            compact('courses')
        );
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' =>
                'required|max:255',

            'description' =>
                'nullable',

            'course_ids' =>
                'nullable|array',

            'course_ids.*' =>
                'exists:courses,id',
        ]);

        $learningPath = LearningPath::create([
            'name' =>
                $request->name,

            'description' =>
                $request->description,

            'is_generated' =>
                false,
        ]);

        $this->syncCoursesWithOrder(
            $learningPath,
            collect(
                $request->course_ids ?? []
            )
        );

        return redirect()
            ->route('learning-paths.index')
            ->with(
                'success',
                'Learning path created successfully.'
            );
    }


    public function show(
        LearningPath $learningPath
    ) {
        $learningPath->load([
            'courses.category',
            'student',
        ]);

        return view(
            'learning-paths.show',
            compact('learningPath')
        );
    }


    public function edit(
        LearningPath $learningPath
    ) {
        $courses = Course::where(
                'is_free',
                false
            )
            ->orderBy('title')
            ->get();

        $selectedCourses = $learningPath
            ->courses()
            ->pluck('courses.id')
            ->toArray();

        return view(
            'learning-paths.edit',
            compact(
                'learningPath',
                'courses',
                'selectedCourses'
            )
        );
    }


    public function update(
        Request $request,
        LearningPath $learningPath
    ) {
        $request->validate([
            'name' =>
                'required|max:255',

            'description' =>
                'nullable',

            'course_ids' =>
                'nullable|array',

            'course_ids.*' =>
                'exists:courses,id',
        ]);

        $learningPath->update([
            'name' =>
                $request->name,

            'description' =>
                $request->description,
        ]);

        $this->syncCoursesWithOrder(
            $learningPath,
            collect(
                $request->course_ids ?? []
            )
        );

        return redirect()
            ->route('learning-paths.index')
            ->with(
                'success',
                'Learning path updated successfully.'
            );
    }


    public function destroy(
        LearningPath $learningPath
    ) {
        $learningPath
            ->courses()
            ->detach();

        $learningPath->delete();

        return redirect()
            ->route('learning-paths.index')
            ->with(
                'success',
                'Learning path deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT - GENERATE PERSONALIZED LEARNING PATH
    |--------------------------------------------------------------------------
    */

    public function generateForStudent()
    {
        $studentId =
            (int) auth()->id();

        /*
         * Only use quiz results from currently published paid courses and
         * published quizzes. Stale results from draft/rejected/unpublished
         * content must not drive a student's generated path.
         */
        $bestQuizResult = QuizResult::with(
                'quiz.course.category'
            )
            ->where(
                'student_id',
                $studentId
            )
            ->whereHas(
                'quiz',
                function ($query) {
                    $query
                        ->where(
                            'is_published',
                            true
                        )
                        ->whereHas(
                            'course',
                            function ($courseQuery) {
                                $courseQuery
                                    ->where(
                                        'is_free',
                                        false
                                    )
                                    ->where(
                                        'status',
                                        'published'
                                    );
                            }
                        );
                }
            )
            ->orderByDesc('percentage')
            ->first();

        if (
            !$bestQuizResult
            ||
            !$bestQuizResult->quiz
            ||
            !$bestQuizResult->quiz->course
        ) {
            return redirect()
                ->route(
                    'student.learning-paths'
                )
                ->with(
                    'error',
                    'No eligible quiz results found. Complete a published paid-course quiz first to generate your learning path.'
                );
        }


        $averageScore = QuizResult::where(
                'student_id',
                $studentId
            )
            ->whereHas(
                'quiz',
                function ($query) {
                    $query
                        ->where(
                            'is_published',
                            true
                        )
                        ->whereHas(
                            'course',
                            function ($courseQuery) {
                                $courseQuery
                                    ->where(
                                        'is_free',
                                        false
                                    )
                                    ->where(
                                        'status',
                                        'published'
                                    );
                            }
                        );
                }
            )
            ->avg('percentage')
            ??
            0;


        if ($averageScore >= 85) {
            $difficulty =
                'advanced';
        } elseif ($averageScore >= 70) {
            $difficulty =
                'intermediate';
        } else {
            $difficulty =
                'beginner';
        }


        $sourceCourse =
            $bestQuizResult->quiz->course;

        $category =
            $sourceCourse->category;

        $pathName = $category
            ? $category->name
                . ' Learning Path'
            : 'Personalized Learning Path';

        $description =
            'Generated based on your learning performance. '
            .
            'Strongest Category: '
            .
            ($category->name ?? 'General')
            .
            '. Average Quiz Score: '
            .
            round(
                $averageScore,
                2
            )
            .
            '%'
            .
            '. Recommended courses match your current skill level ('
            .
            ucfirst($difficulty)
            .
            ').';


        /*
        |--------------------------------------------------------------------------
        | BUILD ELIGIBLE COURSE SET FIRST
        |--------------------------------------------------------------------------
        | Do not modify an existing generated path until we know there are
        | eligible courses available. This prevents an existing path from being
        | emptied when generation cannot produce a new result.
        */

        $courses = Course::where(
                'category_id',
                $sourceCourse->category_id
            )
            ->where(
                'status',
                'published'
            )
            ->where(
                'is_free',
                false
            )
            ->whereHas(
                'lessons',
                function ($query) {
                    $query->where(
                        'is_published',
                        true
                    );
                }
            )
            ->whereHas(
                'quizzes',
                function ($query) {
                    $query->where(
                        'is_published',
                        true
                    );
                }
            )
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
            $additionalCourses = Course::where(
                    'status',
                    'published'
                )
                ->where(
                    'is_free',
                    false
                )
                ->whereNotIn(
                    'id',
                    $courses->pluck('id')
                )
                ->whereHas(
                    'lessons',
                    function ($query) {
                        $query->where(
                            'is_published',
                            true
                        );
                    }
                )
                ->whereHas(
                    'quizzes',
                    function ($query) {
                        $query->where(
                            'is_published',
                            true
                        );
                    }
                )
                ->orderByRaw("
                    CASE difficulty_level
                        WHEN 'beginner' THEN 1
                        WHEN 'intermediate' THEN 2
                        WHEN 'advanced' THEN 3
                        ELSE 4
                    END
                ")
                ->take(
                    3 - $courses->count()
                )
                ->get();

            $courses =
                $courses->merge(
                    $additionalCourses
                );
        }


        if ($courses->isEmpty()) {
            return redirect()
                ->route(
                    'student.learning-paths'
                )
                ->with(
                    'error',
                    'No complete published courses are available for learning path generation.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE OR UPDATE THE CURRENT STUDENT'S GENERATED PATH
        |--------------------------------------------------------------------------
        */

        $existingPath = LearningPath::where(
                'student_id',
                $studentId
            )
            ->where(
                'is_generated',
                true
            )
            ->first();


        if ($existingPath) {
            $existingPath->update([
                'name' =>
                    $pathName,

                'description' =>
                    $description,

                'difficulty_level' =>
                    $difficulty,

                'is_generated' =>
                    true,
            ]);

            $learningPath =
                $existingPath;
        } else {
            $learningPath =
                LearningPath::create([
                    'student_id' =>
                        $studentId,

                    'name' =>
                        $pathName,

                    'description' =>
                        $description,

                    'difficulty_level' =>
                        $difficulty,

                    'is_generated' =>
                        true,
                ]);
        }


        $this->syncCoursesWithOrder(
            $learningPath,
            $courses
        );


        return redirect()
            ->route(
                'student.learning-paths.show',
                $learningPath
            )
            ->with(
                'success',
                'Learning path generated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT - LEARNING PATHS
    |--------------------------------------------------------------------------
    */

    public function studentIndex()
    {
        $learningPaths = LearningPath::with([
                'courses' =>
                    function ($query) {
                        $query
                            ->where(
                                'is_free',
                                false
                            )
                            ->where(
                                'status',
                                'published'
                            );
                    },
            ])
            ->where(
                function ($query) {
                    $query
                        ->whereNull(
                            'student_id'
                        )
                        ->orWhere(
                            'student_id',
                            auth()->id()
                        );
                }
            )
            ->latest()
            ->get();

        return view(
            'student.learning-paths.index',
            compact('learningPaths')
        );
    }


    public function studentShow(
        LearningPath $learningPath
    ) {
        $this->ensureStudentCanAccessLearningPath(
            $learningPath
        );

        $learningPath->load([
            'courses' =>
                function ($query) {
                    $query
                        ->where(
                            'is_free',
                            false
                        )
                        ->where(
                            'status',
                            'published'
                        );
                },

            'courses.category',
        ]);

        return view(
            'student.learning-paths.show',
            compact('learningPath')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SECURITY HELPERS
    |--------------------------------------------------------------------------
    */

    private function ensureStudentCanAccessLearningPath(
        LearningPath $learningPath
    ): void {
        /*
         * student_id = null means an administrator-created global learning
         * path that is intentionally visible to all students.
         */
        if ($learningPath->student_id === null) {
            return;
        }

        if (
            (int) $learningPath->student_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'You are not authorized to access this learning path.'
            );
        }
    }


    private function syncCoursesWithOrder(
        LearningPath $learningPath,
        $courses
    ): void {
        $syncData = [];

        foreach (
            $courses->values()
            as
            $index => $course
        ) {
            $courseId =
                is_object($course)
                    ? $course->id
                    : $course;

            $syncData[$courseId] = [
                'course_order' =>
                    $index + 1,
            ];
        }

        $learningPath
            ->courses()
            ->sync($syncData);
    }
}
