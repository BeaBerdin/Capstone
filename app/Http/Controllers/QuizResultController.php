<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizResult;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quiz results are a record of what students actually scored. They are
 * created only when a student submits a quiz (QuizController::submit).
 * Teachers and administrators can view them, but cannot add, edit or
 * delete them.
 */
class QuizResultController extends Controller
{
    public function index()
    {
        $teacherId = auth()->id();

        // The Department Head (admin) reviews every quiz attempt; a
        // teacher only sees attempts on their own courses.
        $isAdmin = auth()->user()->hasRole('admin');

        $results = QuizResult::with([
                'student',
                'quiz.course',
            ])
            ->when(! $isAdmin, function (Builder $query) use ($teacherId) {
                $query->whereHas('quiz.course', function (Builder $query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                });
            })
            ->latest('completed_at')
            ->get();

        return view(
            'quiz-results.index',
            compact('results')
        );
    }
}
