<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use Illuminate\Support\Collection;

/**
 * Previous / next navigation for students, in the course's published lesson
 * order (the order the course page uses: lesson_order, then id). Activities,
 * quizzes, readings and videos are all lessons, so an activity or a lesson
 * quiz follows its lesson's position. A standalone final quiz (a published
 * quiz without a lesson) comes last and stays locked until every published
 * lesson is completed, as on the course page.
 *
 * Navigation only: it never unlocks anything on the server.
 */
class LessonNavigation
{
    /**
     * @param  Lesson|null  $lesson  The current lesson (null for a standalone
     *                               assignment, or for the final quiz).
     * @param  Quiz|null  $finalQuiz  Set when the current page is the final quiz.
     * @return array{previous: ?array, next: ?array, courseUrl: string, isLast: bool, certificate: ?array}
     */
    public static function build(Course $course, int $studentId, ?Lesson $lesson = null, ?Quiz $finalQuiz = null): array
    {
        $items = self::items($course, $studentId);
        $courseUrl = route('student.learn.course', $course);

        $index = match (true) {
            $lesson !== null => $items->search(fn (array $item) => $item['key'] === 'lesson:'.$lesson->id),
            $finalQuiz !== null => $items->search(fn (array $item) => $item['key'] === 'quiz:'.$finalQuiz->id),
            default => false,
        };

        // Not part of the sequence (e.g. a standalone assignment): course link only.
        if ($index === false) {
            return ['previous' => null, 'next' => null, 'courseUrl' => $courseUrl, 'isLast' => false, 'certificate' => null];
        }

        $previous = $index > 0 ? $items[$index - 1] : null;
        $next = $items[$index + 1] ?? null;

        return [
            'previous' => $previous,
            'next' => $next,
            'courseUrl' => $courseUrl,
            'isLast' => $next === null,
            'certificate' => $next === null ? self::certificateLink($course, $studentId) : null,
        ];
    }

    /**
     * @return Collection<int, array{key: string, title: string, url: string, locked: bool, reason: ?string}>
     */
    private static function items(Course $course, int $studentId): Collection
    {
        $lessons = $course->lessons()
            ->where('is_published', true)
            ->orderBy('lesson_order')
            ->orderBy('id')
            ->get(['id', 'title', 'lesson_type', 'lesson_order']);

        // A quiz lesson links straight to its published quiz; every other
        // lesson (and a quiz lesson without one) uses the lesson route, which
        // sends activities to their own page.
        $lessonQuizzes = Quiz::query()
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->where('is_published', true)
            ->orderBy('id')
            ->get(['id', 'lesson_id'])
            ->unique('lesson_id')
            ->keyBy('lesson_id');

        $items = $lessons->map(fn (Lesson $lesson) => [
            'key' => 'lesson:'.$lesson->id,
            'title' => $lesson->title,
            'url' => strtolower((string) $lesson->lesson_type) === 'quiz' && $lessonQuizzes->has($lesson->id)
                ? route('student.quiz.take', $lessonQuizzes->get($lesson->id))
                : route('student.lesson.view', $lesson),
            'locked' => false,
            'reason' => null,
        ])->values();

        $finalQuiz = Quiz::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->whereNull('lesson_id')
            ->first();

        if ($finalQuiz) {
            $completed = LessonProgress::query()
                ->where('student_id', $studentId)
                ->where('status', 'completed')
                ->whereIn('lesson_id', $lessons->pluck('id'))
                ->count();

            $locked = $completed < $lessons->count();

            $items->push([
                'key' => 'quiz:'.$finalQuiz->id,
                'title' => $finalQuiz->title,
                'url' => route('student.quiz.take', $finalQuiz),
                'locked' => $locked,
                'reason' => $locked ? 'Complete all lessons first' : null,
            ]);
        }

        return $items;
    }

    /**
     * On the last lesson: a link to the certificate when it is issued, or to
     * the course page's certificate status when the course is complete.
     *
     * @return array{label: string, url: string}|null
     */
    private static function certificateLink(Course $course, int $studentId): ?array
    {
        if (! $course->certificate_available) {
            return null;
        }

        $issued = Certificate::query()
            ->where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'issued')
            ->exists();

        if ($issued) {
            return ['label' => 'View certificate', 'url' => route('student.certificates')];
        }

        if (CertificateEligibility::lessonsAndQuizComplete($course, $studentId)) {
            return ['label' => 'Certificate status', 'url' => route('student.learn.course', $course)];
        }

        return null;
    }
}
