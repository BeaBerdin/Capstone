<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizResultController extends Controller
{
    public function index()
    {
        $teacherId = auth()->id();

        $results = QuizResult::with([
                'student',
                'quiz.course',
            ])
            ->whereHas('quiz.course', function (Builder $query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->latest('completed_at')
            ->get();

        return view(
            'quiz-results.index',
            compact('results')
        );
    }

    public function create()
    {
        $teacherId = auth()->id();

        $courseIds = Course::where(
                'teacher_id',
                $teacherId
            )
            ->pluck('id');

        $quizzes = Quiz::whereIn(
                'course_id',
                $courseIds
            )
            ->orderBy('title')
            ->get();

        $studentIds = Enrollment::whereIn(
                'course_id',
                $courseIds
            )
            ->pluck('student_id')
            ->unique();

        $students = User::whereIn(
                'id',
                $studentIds
            )
            ->orderBy('name')
            ->get();

        return view(
            'quiz-results.create',
            compact(
                'students',
                'quizzes'
            )
        );
    }

    public function store(Request $request)
    {
        $teacherId = auth()->id();

        $teacherQuizIds = Quiz::whereHas(
                'course',
                function (Builder $query) use ($teacherId) {
                    $query->where(
                        'teacher_id',
                        $teacherId
                    );
                }
            )
            ->pluck('id');

        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:users,id',
            ],
            'quiz_id' => [
                'required',
                Rule::in(
                    $teacherQuizIds->all()
                ),
            ],
            'score' => [
                'required',
                'integer',
                'min:0',
            ],
            'total_items' => [
                'required',
                'integer',
                'min:1',
            ],
            'attempt_number' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        if (
            (int) $validated['score']
            >
            (int) $validated['total_items']
        ) {
            return back()
                ->withErrors([
                    'score' =>
                        'The score cannot be greater than the total number of items.',
                ])
                ->withInput();
        }

        $quiz = Quiz::with('course')
            ->whereKey(
                $validated['quiz_id']
            )
            ->whereHas(
                'course',
                function (Builder $query) use ($teacherId) {
                    $query->where(
                        'teacher_id',
                        $teacherId
                    );
                }
            )
            ->firstOrFail();

        $studentIsEnrolled = Enrollment::where(
                'student_id',
                $validated['student_id']
            )
            ->where(
                'course_id',
                $quiz->course_id
            )
            ->exists();

        if (!$studentIsEnrolled) {
            return back()
                ->withErrors([
                    'student_id' =>
                        'The selected student is not enrolled in this course.',
                ])
                ->withInput();
        }

        $percentage = round(
            (
                (int) $validated['score']
                /
                (int) $validated['total_items']
            )
            * 100,
            2
        );

        $passingScore =
            (float) ($quiz->passing_score ?? 75);

        $remarks =
            $percentage >= $passingScore
                ? 'passed'
                : 'failed';

        QuizResult::create([
            'student_id' =>
                $validated['student_id'],
            'quiz_id' =>
                $quiz->id,
            'score' =>
                $validated['score'],
            'total_items' =>
                $validated['total_items'],
            'percentage' =>
                $percentage,
            'remarks' =>
                $remarks,
            'attempt_number' =>
                $validated['attempt_number'],
            'completed_at' =>
                now(),
        ]);

        return redirect()
            ->route(
                'teacher.quiz-results.index'
            )
            ->with(
                'success',
                'Quiz result recorded successfully.'
            );
    }

    public function edit(
        QuizResult $quiz_result
    ) {
        $this->ensureResultBelongsToTeacher(
            $quiz_result
        );

        $teacherId = auth()->id();

        $courseIds = Course::where(
                'teacher_id',
                $teacherId
            )
            ->pluck('id');

        $quizzes = Quiz::whereIn(
                'course_id',
                $courseIds
            )
            ->orderBy('title')
            ->get();

        $studentIds = Enrollment::whereIn(
                'course_id',
                $courseIds
            )
            ->pluck('student_id')
            ->unique();

        $students = User::whereIn(
                'id',
                $studentIds
            )
            ->orderBy('name')
            ->get();

        return view(
            'quiz-results.edit',
            compact(
                'quiz_result',
                'students',
                'quizzes'
            )
        );
    }

    public function update(
        Request $request,
        QuizResult $quiz_result
    ) {
        $this->ensureResultBelongsToTeacher(
            $quiz_result
        );

        $teacherId = auth()->id();

        $teacherQuizIds = Quiz::whereHas(
                'course',
                function (Builder $query) use ($teacherId) {
                    $query->where(
                        'teacher_id',
                        $teacherId
                    );
                }
            )
            ->pluck('id');

        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:users,id',
            ],
            'quiz_id' => [
                'required',
                Rule::in(
                    $teacherQuizIds->all()
                ),
            ],
            'score' => [
                'required',
                'integer',
                'min:0',
            ],
            'total_items' => [
                'required',
                'integer',
                'min:1',
            ],
            'attempt_number' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        if (
            (int) $validated['score']
            >
            (int) $validated['total_items']
        ) {
            return back()
                ->withErrors([
                    'score' =>
                        'The score cannot be greater than the total number of items.',
                ])
                ->withInput();
        }

        $quiz = Quiz::with('course')
            ->whereKey(
                $validated['quiz_id']
            )
            ->whereHas(
                'course',
                function (Builder $query) use ($teacherId) {
                    $query->where(
                        'teacher_id',
                        $teacherId
                    );
                }
            )
            ->firstOrFail();

        $studentIsEnrolled = Enrollment::where(
                'student_id',
                $validated['student_id']
            )
            ->where(
                'course_id',
                $quiz->course_id
            )
            ->exists();

        if (!$studentIsEnrolled) {
            return back()
                ->withErrors([
                    'student_id' =>
                        'The selected student is not enrolled in this course.',
                ])
                ->withInput();
        }

        $percentage = round(
            (
                (int) $validated['score']
                /
                (int) $validated['total_items']
            )
            * 100,
            2
        );

        $passingScore =
            (float) ($quiz->passing_score ?? 75);

        $remarks =
            $percentage >= $passingScore
                ? 'passed'
                : 'failed';

        $quiz_result->update([
            'student_id' =>
                $validated['student_id'],
            'quiz_id' =>
                $quiz->id,
            'score' =>
                $validated['score'],
            'total_items' =>
                $validated['total_items'],
            'percentage' =>
                $percentage,
            'remarks' =>
                $remarks,
            'attempt_number' =>
                $validated['attempt_number'],
            'completed_at' =>
                now(),
        ]);

        return redirect()
            ->route(
                'teacher.quiz-results.index'
            )
            ->with(
                'success',
                'Quiz result updated successfully.'
            );
    }

    public function destroy(
        QuizResult $quiz_result
    ) {
        $this->ensureResultBelongsToTeacher(
            $quiz_result
        );

        $quiz_result->delete();

        return redirect()
            ->route(
                'teacher.quiz-results.index'
            )
            ->with(
                'success',
                'Quiz result deleted successfully.'
            );
    }

    private function ensureResultBelongsToTeacher(
        QuizResult $quizResult
    ): void {
        $quizResult->loadMissing(
            'quiz.course'
        );

        if (
            !$quizResult->quiz
            ||
            !$quizResult->quiz->course
            ||
            (int) $quizResult->quiz->course->teacher_id
                !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }
    }
}
