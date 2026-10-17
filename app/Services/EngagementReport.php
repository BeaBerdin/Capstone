<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * User-engagement figures for the system reports (Manuscript FR (o):
 * reports on user engagement), based on lesson activity in lesson_progress.
 *
 * A lesson is "started" when a student first opens it (started_at) and
 * "completed" when it is marked complete (completed_at). Time spent is not
 * reported: the platform does not record it (time_spent_minutes stays 0).
 */
class EngagementReport
{
    /**
     * @param  CarbonInterface|null  $since  Start of the report period; null for all time.
     * @return array<string, mixed>
     */
    public function build(?CarbonInterface $since = null): array
    {
        // Column names are qualified because the course ranking below joins
        // lessons and courses, which have columns with the same names.
        $started = LessonProgress::query()
            ->whereNotNull('lesson_progress.started_at')
            ->when($since, fn ($query) => $query->where('lesson_progress.started_at', '>=', $since));

        $completed = LessonProgress::query()
            ->where('lesson_progress.status', 'completed')
            ->whereNotNull('lesson_progress.completed_at')
            ->when($since, fn ($query) => $query->where('lesson_progress.completed_at', '>=', $since));

        $lessonsStarted = (clone $started)->count();
        $lessonsCompleted = (clone $completed)->count();

        // Students with any lesson activity (opened or completed) in the period.
        $activeLearnerIds = (clone $started)->pluck('student_id')
            ->merge((clone $completed)->pluck('student_id'))
            ->unique()
            ->values();

        $activeLearners = $activeLearnerIds->count();

        // Students with an active enrollment but no lesson activity in the period.
        $enrolledStudentIds = Enrollment::where('status', 'active')
            ->distinct()
            ->pluck('student_id');

        $inactiveLearners = $enrolledStudentIds->diff($activeLearnerIds)->count();

        // Courses with the most lessons completed in the period.
        $topCourses = (clone $completed)
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->join('courses', 'courses.id', '=', 'lessons.course_id')
            ->groupBy('courses.id', 'courses.title')
            ->selectRaw('courses.id, courses.title, COUNT(*) as lessons_completed, COUNT(DISTINCT lesson_progress.student_id) as learners')
            ->orderByDesc('lessons_completed')
            ->orderBy('courses.title')
            ->limit(5)
            ->get();

        return [
            'lessonsStarted' => $lessonsStarted,
            'lessonsCompleted' => $lessonsCompleted,
            'activeLearners' => $activeLearners,
            'inactiveLearners' => $inactiveLearners,
            'averageCompletedPerLearner' => $activeLearners > 0
                ? round($lessonsCompleted / $activeLearners, 1)
                : 0,
            'completionRatio' => $lessonsStarted > 0
                ? round(($lessonsCompleted / $lessonsStarted) * 100, 1)
                : 0,
            'topCourses' => $topCourses,
            'weeklyCompletions' => $this->weeklyCompletions(),
        ];
    }

    /**
     * Lessons completed per week for the last 8 weeks (oldest first),
     * independent of the report period.
     *
     * @return array<int, array{week: string, completed: int}>
     */
    public function weeklyCompletions(int $weeks = 8): array
    {
        $firstWeek = now()->startOfWeek()->subWeeks($weeks - 1);

        $counts = LessonProgress::where('status', 'completed')
            ->where('completed_at', '>=', $firstWeek)
            ->pluck('completed_at')
            ->countBy(fn ($completedAt) => Carbon::parse($completedAt)->startOfWeek()->toDateString());

        return collect(range(0, $weeks - 1))
            ->map(function (int $offset) use ($firstWeek, $counts) {
                $week = $firstWeek->copy()->addWeeks($offset);

                return [
                    'week' => $week->format('M d'),
                    'completed' => (int) ($counts[$week->toDateString()] ?? 0),
                ];
            })
            ->all();
    }
}
