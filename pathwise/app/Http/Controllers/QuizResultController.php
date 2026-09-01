<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Http\Request;

class QuizResultController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = auth()->id();

        $query = QuizResult::with(['student', 'quiz.course'])
            ->whereHas('quiz.course', fn ($q) => $q->where('teacher_id', $teacherId));

        if ($request->filled('course') && $request->course !== 'all') {
            $query->whereHas('quiz', fn ($q) => $q->where('course_id', $request->course));
        }

        if ($request->filled('remarks') && $request->remarks !== 'all') {
            $query->where('remarks', $request->remarks);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('quiz', fn ($quiz) => $quiz->where('title', 'like', "%{$search}%"));
            });
        }

        $results = $query->latest()->get();
        $courses = Course::where('teacher_id', $teacherId)->orderBy('title')->get();

        return view('quiz-results.index', compact('results', 'courses'));
    }

    public function create()
    {
        $teacherId = auth()->id();
        $quizzes = Quiz::whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
            ->orderBy('title')
            ->get();

        $studentIds = Enrollment::whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
            ->pluck('student_id')
            ->unique();

        $students = User::whereIn('id', $studentIds)->orderBy('name')->get();

        return view('quiz-results.create', compact('students', 'quizzes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'total_items' => 'required|integer|min:1',
            'attempt_number' => 'required|integer|min:1',
        ]);

        $quiz = Quiz::with('course')->findOrFail($request->quiz_id);
        $this->ensureTeacherOwnsQuiz($quiz);
        $this->ensureStudentBelongsToCourse($request->student_id, $quiz->course_id);

        $percentage = min(100, ($request->score / $request->total_items) * 100);
        $remarks = $percentage >= ($quiz->passing_score ?? 75) ? 'passed' : 'failed';

        QuizResult::create([
            'student_id' => $request->student_id,
            'quiz_id' => $request->quiz_id,
            'score' => $request->score,
            'total_items' => $request->total_items,
            'percentage' => $percentage,
            'remarks' => $remarks,
            'attempt_number' => $request->attempt_number,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('teacher.quiz-results.index')
            ->with('success', 'Quiz result recorded successfully.');
    }

    public function edit(QuizResult $quiz_result)
    {
        $quiz_result->load('quiz.course');
        $this->ensureTeacherOwnsQuiz($quiz_result->quiz);

        $teacherId = auth()->id();
        $quizzes = Quiz::whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
            ->orderBy('title')
            ->get();

        $studentIds = Enrollment::whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
            ->pluck('student_id')
            ->unique();

        $students = User::whereIn('id', $studentIds)->orderBy('name')->get();

        return view('quiz-results.edit', compact('quiz_result', 'students', 'quizzes'));
    }

    public function update(Request $request, QuizResult $quiz_result)
    {
        $quiz_result->load('quiz.course');
        $this->ensureTeacherOwnsQuiz($quiz_result->quiz);

        $request->validate([
            'student_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'total_items' => 'required|integer|min:1',
            'attempt_number' => 'required|integer|min:1',
        ]);

        $quiz = Quiz::with('course')->findOrFail($request->quiz_id);
        $this->ensureTeacherOwnsQuiz($quiz);
        $this->ensureStudentBelongsToCourse($request->student_id, $quiz->course_id);

        $percentage = min(100, ($request->score / $request->total_items) * 100);
        $remarks = $percentage >= ($quiz->passing_score ?? 75) ? 'passed' : 'failed';

        $quiz_result->update([
            'student_id' => $request->student_id,
            'quiz_id' => $request->quiz_id,
            'score' => $request->score,
            'total_items' => $request->total_items,
            'percentage' => $percentage,
            'remarks' => $remarks,
            'attempt_number' => $request->attempt_number,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('teacher.quiz-results.index')
            ->with('success', 'Quiz result updated successfully.');
    }

    public function destroy(QuizResult $quiz_result)
    {
        $quiz_result->load('quiz.course');
        $this->ensureTeacherOwnsQuiz($quiz_result->quiz);
        $quiz_result->delete();

        return redirect()
            ->route('teacher.quiz-results.index')
            ->with('success', 'Quiz result deleted successfully.');
    }

    private function ensureTeacherOwnsQuiz(Quiz $quiz): void
    {
        if (! $quiz->course || $quiz->course->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureStudentBelongsToCourse(int $studentId, int $courseId): void
    {
        if (! Enrollment::where('student_id', $studentId)->where('course_id', $courseId)->exists()) {
            abort(422, 'The selected student is not enrolled in this course.');
        }
    }
}
