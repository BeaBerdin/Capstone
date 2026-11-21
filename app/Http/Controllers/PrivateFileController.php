<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Storage;

/**
 * Serves files on the private disk after checking who may open them
 * (App\Support\PrivateFiles). Guests are sent to the login page by the
 * auth middleware; signed-in users without access get 403.
 */
class PrivateFileController extends Controller
{
    /**
     * Payment receipt: the student who paid and the Super Admin.
     */
    public function receipt(Transaction $transaction)
    {
        $user = auth()->user();

        abort_unless(
            (int) $transaction->student_id === (int) $user->id
                || $user->hasRole('super_admin'),
            403
        );

        return $this->serve($transaction->payment_proof);
    }

    /**
     * Assignment submission: the student who submitted it, the course's
     * teacher and administrators.
     */
    public function submission(Submission $submission)
    {
        $user = auth()->user();

        $submission->loadMissing('assignment.course');
        $assignment = $submission->assignment;

        abort_unless(
            ($user->hasRole('student') && (int) $submission->student_id === (int) $user->id)
                || ($user->hasRole('teacher')
                    && $assignment?->course
                    && (int) $assignment->course->teacher_id === (int) $user->id
                    && $assignment->hasConsistentLesson())
                || $submission->assignment()->manageableBy($user)->exists(),
            403
        );

        return $this->serve($submission->file_path);
    }

    /**
     * Lesson file: the course's teacher, administrators, and students with
     * an active or completed enrollment when the course and the lesson are
     * published. Preview lessons are not open to anyone else.
     */
    public function lesson(Lesson $lesson)
    {
        $user = auth()->user();

        abort_unless($this->canOpenLessonContent($lesson, $user), 403);

        return $this->serve($lesson->file_path);
    }

    /** Serve a Reading lesson image inline or learning file as a download. */
    public function lessonSection(LessonSection $lessonSection)
    {
        $lessonSection->loadMissing('lesson.course');
        $lesson = $lessonSection->lesson;

        abort_unless($lesson !== null && $this->canOpenLessonContent($lesson, auth()->user()), 403);
        abort_unless($lesson->lesson_type === 'text' && in_array($lessonSection->type, ['image', 'file'], true), 404);

        return $this->serve($lessonSection->file_path, $lessonSection);
    }

    private function canOpenLessonContent(Lesson $lesson, User $user): bool
    {
        $lesson->loadMissing('course');
        $course = $lesson->course;

        if (! $course) {
            return false;
        }

        return ($user->hasRole('teacher') && (int) $course->teacher_id === (int) $user->id)
            || $course->isManageableBy($user)
            || (
                $user->hasRole('student')
                && strtolower(trim((string) $course->status)) === 'published'
                && (bool) $lesson->is_published
                && Enrollment::where('student_id', $user->id)
                    ->where('course_id', $course->id)
                    ->withAccess()
                    ->exists()
            );
    }

    /**
     * PDFs and images open in the browser; other files are downloaded.
     */
    private function serve(?string $path, ?LessonSection $section = null)
    {
        $disk = $section ? Storage::disk(PrivateFiles::DISK) : PrivateFiles::diskFor($path);

        abort_unless($path && $disk && (! $section || $disk->exists($path)), 404);

        $name = $section?->original_name ?: basename($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $inlineType = PrivateFiles::INLINE_TYPES[$extension] ?? null;

        return $disk->response(
            $path,
            $name,
            [
                'Content-Type' => $inlineType ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
            $inlineType && $section?->type !== 'file' ? 'inline' : 'attachment'
        );
    }
}
