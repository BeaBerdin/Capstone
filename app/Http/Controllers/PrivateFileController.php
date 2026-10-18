<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\Transaction;
use App\Support\PrivateFiles;

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

        abort_unless(
            (int) $submission->student_id === (int) $user->id
                || (int) $submission->assignment?->course?->teacher_id === (int) $user->id
                || $user->hasRole('admin'),
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

        $lesson->loadMissing('course');

        $course = $lesson->course;

        abort_unless($course, 403);

        $canOpen = (int) $course->teacher_id === (int) $user->id
            || $user->hasRole('admin')
            || (
                strtolower(trim((string) $course->status)) === 'published'
                && (bool) $lesson->is_published
                && Enrollment::where('student_id', $user->id)
                    ->where('course_id', $course->id)
                    ->withAccess()
                    ->exists()
            );

        abort_unless($canOpen, 403);

        return $this->serve($lesson->file_path);
    }

    /**
     * PDFs and images open in the browser; other files are downloaded.
     */
    private function serve(?string $path)
    {
        $disk = PrivateFiles::diskFor($path);

        abort_unless($disk, 404);

        $name = basename($path);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $inlineType = PrivateFiles::INLINE_TYPES[$extension] ?? null;

        return $disk->response(
            $path,
            $name,
            [
                'Content-Type' => $inlineType ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
            $inlineType ? 'inline' : 'attachment'
        );
    }
}
