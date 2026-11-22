<?php

namespace App\Support;

use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Study Load files and application rows.
 *
 * Files are kept on the private disk (storage/app/private) under
 * study-loads/{user id}/ with random names. They have no public URL and are
 * served only to the Super Admin (EDP) by StudentApplicationController.
 * Files are never overwritten or deleted after a submission is saved, so
 * every earlier submission stays available for audit.
 */
class StudyLoads
{
    public const DISK = PrivateFiles::DISK;

    /**
     * Store the file and create the application row. Must be called inside
     * a database transaction; $storedPath receives the file path so the
     * caller can delete the file if the transaction fails.
     */
    public static function submit(User $user, UploadedFile $file, int $submissionNo, ?string &$storedPath): StudentApplication
    {
        $path = $file->store('study-loads/'.$user->id, self::DISK);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The Study Load could not be saved.');
        }

        $storedPath = $path;

        return StudentApplication::create([
            'user_id' => $user->id,
            'submission_no' => $submissionNo,
            'study_load_path' => $path,
            'study_load_original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'study_load_mime' => (string) $file->getMimeType(),
            'study_load_size' => (int) $file->getSize(),
            'status' => StudentApplication::PENDING,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Remove a file whose submission was not saved (rolled back).
     */
    public static function discard(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $e) {
            Log::warning('PATHWISE could not remove an unsaved Study Load file.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * In-app notification (and email when mail is configured) to every
     * active Super Admin. A failed email never undoes the submission.
     */
    public static function notifyEdp(?StudentApplication $application, bool $resubmission): void
    {
        if (! $application) {
            return;
        }

        $superAdmins = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->get();

        // In-app notification for every Super Admin. Their login email is
        // used only when no EDP mailbox is configured and it can receive mail
        // (see StudentApplicationSubmitted::via()).
        foreach ($superAdmins as $superAdmin) {
            try {
                $superAdmin->notify(new StudentApplicationSubmitted($application, $resubmission));
            } catch (Throwable $e) {
                Log::warning('PATHWISE could not notify the EDP about a student application.', [
                    'application_id' => $application->id,
                    'super_admin_id' => $superAdmin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // The email goes to the configured EDP mailbox
        // (PATHWISE_EDP_NOTIFICATION_EMAIL). No attachment: the Study Load
        // stays on the private disk and is opened only through the app.
        $mailboxes = EdpNotificationRecipients::configured();

        if ($mailboxes !== []) {
            try {
                Notification::route('mail', $mailboxes)
                    ->notify(new StudentApplicationSubmitted($application, $resubmission));
            } catch (Throwable $e) {
                Log::warning('PATHWISE could not email the EDP mailbox about a student application.', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return;
        }

        if (MailSettings::isConfigured() && ! $superAdmins->contains(fn (User $user) => EdpNotificationRecipients::isDeliverable($user->email))) {
            Log::warning('PATHWISE has no deliverable EDP email address: set PATHWISE_EDP_NOTIFICATION_EMAIL. The application is visible in the app.', [
                'application_id' => $application->id,
            ]);
        }
    }
}
