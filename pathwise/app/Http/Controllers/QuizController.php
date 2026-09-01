<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::with(['course', 'lesson'])->latest()->get();

        return view('quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $courses = Course::orderBy('title')->get();
        $lessons = Lesson::orderBy('title')->get();

        return view('quizzes.create', compact('courses', 'lessons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|max:255',
            'description' => 'nullable',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'is_published' => 'nullable',
        ]);

        Quiz::create([
            'course_id' => $request->course_id,
            'lesson_id' => $request->lesson_id,
            'title' => $request->title,
            'description' => $request->description,
            'passing_score' => $request->passing_score,
            'time_limit_minutes' => $request->time_limit_minutes,
            'is_published' => $request->has('is_published'),
        ]);

        return redirect()
            ->route('quizzes.index')
            ->with('success', 'Quiz created successfully.');
    }

    public function edit(Quiz $quiz)
    {
        $courses = Course::orderBy('title')->get();
        $lessons = Lesson::orderBy('title')->get();

        return view('quizzes.edit', compact('quiz', 'courses', 'lessons'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|max:255',
            'description' => 'nullable',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'is_published' => 'nullable',
        ]);

        $quiz->update([
            'course_id' => $request->course_id,
            'lesson_id' => $request->lesson_id,
            'title' => $request->title,
            'description' => $request->description,
            'passing_score' => $request->passing_score,
            'time_limit_minutes' => $request->time_limit_minutes,
            'is_published' => $request->has('is_published'),
        ]);

        return redirect()
            ->route('quizzes.index')
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()
            ->route('quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }

    public function take(Quiz $quiz)
    {
        $quiz->load(['course', 'questions']);

        return view('student.take-quiz', compact('quiz'));
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $quiz->load(['questions', 'course.category', 'course.lessons']);

        $score = 0;
        $totalItems = $quiz->questions->count();

        foreach ($quiz->questions as $question) {
            $answer = $request->input('question_' . $question->id);

            if ($answer === $question->correct_answer) {
                $score++;
            }
        }

        $percentage = $totalItems > 0
            ? round(($score / $totalItems) * 100, 2)
            : 0;

        $remarks = $percentage >= $quiz->passing_score
            ? 'passed'
            : 'failed';

        QuizResult::create([
            'student_id' => auth()->id(),
            'quiz_id' => $quiz->id,
            'score' => $score,
            'total_items' => $totalItems,
            'percentage' => $percentage,
            'remarks' => $remarks,
            'attempt_number' => $this->getNextAttemptNumber($quiz),
            'completed_at' => now(),
        ]);

        app(RecommendationService::class)->generate(
            auth()->id(),
            $quiz,
            $percentage
        );

        if ($remarks === 'failed') {
            $this->markCourseAsActiveAfterFailedQuiz($quiz);
        }

        if ($remarks === 'passed') {
            $this->generateCertificateIfEligible($quiz);
        }

        return redirect()
            ->route('student.learn.course', $quiz->course)
            ->with(
                'success',
                'Quiz submitted. Your score is ' . $percentage . '%.'
            );
    }

    private function getNextAttemptNumber(Quiz $quiz): int
    {
        return QuizResult::where('student_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->count() + 1;
    }

    private function markCourseAsActiveAfterFailedQuiz(Quiz $quiz): void
    {
        $studentId = auth()->id();
        $course = $quiz->course;

        if (!$course) {
            return;
        }

        Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->update([
                'status' => 'active',
                'progress_percentage' => 100,
            ]);
    }

    private function generateCertificateIfEligible(Quiz $quiz): void
    {
        $studentId = auth()->id();
        $course = $quiz->course;

        if (!$course || !$course->certificate_available) {
            return;
        }

        $totalLessons = $course->lessons()->count();

        $completedLessons = LessonProgress::where('student_id', $studentId)
            ->whereHas('lesson', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->where('status', 'completed')
            ->count();

        if ($totalLessons > 0 && $completedLessons < $totalLessons) {
            return;
        }

        Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->update([
                'status' => 'completed',
                'progress_percentage' => 100,
            ]);

        Certificate::firstOrCreate(
            [
                'student_id' => $studentId,
                'course_id' => $course->id,
            ],
            [
                'certificate_number' => $this->generateCertificateNumber(),
                'issued_date' => now()->toDateString(),
                'status' => 'issued',
            ]
        );
    }

    private function generateCertificateNumber(): string
    {
        $count = Certificate::count() + 1;

        return 'PATH-' . now()->format('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}