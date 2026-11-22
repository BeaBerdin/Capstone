<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Quiz results are a record of what students actually scored. They are
 * created only when a student submits a quiz (QuizController::submit).
 * Teachers and administrators can view them, but cannot add, edit or
 * delete them.
 */
class QuizResultController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $adminRoute = $request->routeIs('quiz-results.index');

        $results = QuizResult::with([
                'student',
                'quiz.course',
            ])
            ->whereHas('quiz', fn (Builder $quiz) => $adminRoute
                ? $quiz->manageableBy($user)
                : $quiz->withConsistentLesson()
                    ->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)))
            ->latest('completed_at')
            ->get();

        return view(
            'quiz-results.index',
            compact('results')
        );
    }
}
