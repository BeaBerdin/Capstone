<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * When a student may receive a course certificate.
 *
 * All of these must hold:
 *  - the student has an enrollment with access (active or completed);
 *  - every published lesson is completed;
 *  - the latest attempt of the course's standalone final quiz (a published
 *    quiz without a lesson), if there is one, is passed;
 *  - every published activity/assignment of the course (an activity only
 *    when its lesson is published) has a FINAL teacher grade — status
 *    "graded" with a teacher score; the AI suggested score never counts —
 *    that meets the passing score.
 *
 * Passing score: assignments have no passing-score setting, so it is 75% of
 * the assignment's max score (PASSING_RATIO). An assignment without a max
 * score passes with any final grade.
 *
 * Used by every path that issues a certificate (lesson completion, quiz
 * pass, teacher grading, administrator issue). Certificates that were
 * already issued are never changed here.
 */
class CertificateEligibility
{
    public const PASSING_RATIO = 0.75;

    /** The score needed to pass, or null when any final grade passes. */
    public static function passingScore(Assignment $assignment): ?float
    {
        $max = (int) $assignment->max_score;

        return $max > 0 ? $max * self::PASSING_RATIO : null;
    }

    public static function passes(Assignment $assignment, ?int $score): bool
    {
        if ($score === null) {
            return false;
        }

        $passing = self::passingScore($assignment);

        return $passing === null || $score >= $passing;
    }

    /**
     * A final teacher grade that is below the passing score (resubmission
     * is allowed then).
     */
    public static function failedFinalGrade(Submission $submission, Assignment $assignment): bool
    {
        return $submission->status === 'graded'
            && $submission->score !== null
            && ! self::passes($assignment, (int) $submission->score);
    }

    /**
     * Published activities/assignments that count for the certificate.
     *
     * @return Collection<int, Assignment>
     */
    public static function requiredAssignments(Course $course): Collection
    {
        return Assignment::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('lesson_id')
                ->orWhereHas('lesson', fn (Builder $lesson) => $lesson->where('is_published', true)))
            ->orderBy('id')
            ->get();
    }

    /**
     * Why the activities/assignments keep the certificate locked, one plain
     * message per assignment, e.g. "Waiting for your teacher to grade:
     * Activity 2" or "Activity 3: score 60%, passing 75% — resubmit to
     * improve". Empty when every one has a passing final grade.
     *
     * @return array<int, string>
     */
    public static function activityBlockers(Course $course, int $studentId): array
    {
        $assignments = self::requiredAssignments($course);

        if ($assignments->isEmpty()) {
            return [];
        }

        $submissions = Submission::query()
            ->where('student_id', $studentId)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('assignment_id')
            ->keyBy('assignment_id');

        $blockers = [];

        foreach ($assignments as $assignment) {
            $submission = $submissions->get($assignment->id);
            $title = $assignment->title;

            if (! $submission) {
                $blockers[] = 'Submit: '.$title;

                continue;
            }

            if ($submission->status !== 'graded' || $submission->score === null) {
                $blockers[] = 'Waiting for your teacher to grade: '.$title;

                continue;
            }

            if (! self::passes($assignment, (int) $submission->score)) {
                $max = (int) $assignment->max_score;
                $percent = $max > 0 ? (int) round(((int) $submission->score / $max) * 100) : 0;

                $blockers[] = $title.': score '.$percent.'%, passing '.(int) round(self::PASSING_RATIO * 100).'% — resubmit to improve';
            }
        }

        return $blockers;
    }

    /** All published lessons completed and the final quiz (if any) passed. */
    public static function lessonsAndQuizComplete(Course $course, int $studentId): bool
    {
        $lessonIds = $course->lessons()->where('is_published', true)->pluck('id');

        if ($lessonIds->isEmpty()) {
            return false;
        }

        $completed = LessonProgress::query()
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        if ($completed < $lessonIds->count()) {
            return false;
        }

        $finalQuiz = Quiz::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->whereNull('lesson_id')
            ->first();

        if (! $finalQuiz) {
            return true;
        }

        $latest = QuizResult::query()
            ->where('student_id', $studentId)
            ->where('quiz_id', $finalQuiz->id)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();

        return $latest !== null && strtolower(trim((string) $latest->remarks)) === 'passed';
    }

    public static function hasCourseAccess(Course $course, int $studentId): bool
    {
        return Enrollment::query()
            ->where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->withAccess()
            ->exists();
    }

    /**
     * Every reason the certificate is still locked (empty = eligible).
     *
     * @return array<int, string>
     */
    public static function blockers(Course $course, int $studentId): array
    {
        $reasons = [];

        if (! $course->certificate_available) {
            return ['This course does not offer a certificate.'];
        }

        if (! self::hasCourseAccess($course, $studentId)) {
            return ['You need an active enrollment in this course.'];
        }

        if (! self::lessonsAndQuizComplete($course, $studentId)) {
            $reasons[] = 'Complete all lessons and pass the required quizzes.';
        }

        return [...$reasons, ...self::activityBlockers($course, $studentId)];
    }

    public static function isEligible(Course $course, int $studentId): bool
    {
        return self::blockers($course, $studentId) === [];
    }

    /**
     * Issue the certificate when the student is eligible (never twice; an
     * existing certificate is returned unchanged). The student is notified
     * only for a newly issued certificate.
     */
    public static function issueIfEligible(Course $course, int $studentId): ?Certificate
    {
        if (! self::isEligible($course, $studentId)) {
            return null;
        }

        $certificate = Certificate::firstOrCreate(
            ['student_id' => $studentId, 'course_id' => $course->id],
            [
                'certificate_number' => Certificate::generateNumber(),
                'issued_date' => now()->toDateString(),
                'status' => 'issued',
            ]
        );

        if ($certificate->wasRecentlyCreated) {
            $certificate->notifyStudent();
        }

        return $certificate;
    }
}
