<?php

namespace App\Notifications;

use App\Models\Course;
use App\Notifications\Channels\SafeMailChannel;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PathwiseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'general',
        public ?int $courseId = null,
        public ?int $assignmentId = null,
        public ?int $submissionId = null,
        public ?int $transactionId = null,
        public ?int $certificateId = null,
        public ?int $invitationId = null,
        public ?string $invitationCode = null
    ) {
    }

    /**
     * Notification types that are also emailed (enrollment confirmed and
     * certificate issued). All other types stay in-app only.
     */
    public const ENROLLMENT_EMAIL_TYPES = ['enrollment_activated', 'payment_approved'];

    public const CERTIFICATE_EMAIL_TYPES = ['certificate_issued'];

    /*
    |--------------------------------------------------------------------------
    | DELIVERY CHANNEL
    |--------------------------------------------------------------------------
    |
    | The in-app (database) notification is always saved. Email is added only
    | for enrollment and certificate notifications, only when real email is
    | configured (not the log/array mailers), and only once per enrollment
    | and per certificate. A failed email is logged by SafeMailChannel and
    | never interrupts the action.
    |
    */

    public function via(object $notifiable): array
    {
        $channels = [
            'database',
        ];

        if ($this->shouldEmail($notifiable)) {
            $channels[] = SafeMailChannel::class;
        }

        return $channels;
    }

    private function shouldEmail(object $notifiable): bool
    {
        if (! in_array($this->type, [...self::ENROLLMENT_EMAIL_TYPES, ...self::CERTIFICATE_EMAIL_TYPES], true)) {
            return false;
        }

        if (! MailSettings::isConfigured() || blank($notifiable->email ?? null)) {
            return false;
        }

        return ! $this->alreadySentFor($notifiable);
    }

    /**
     * True when this student already received the same kind of notification
     * for this course (enrollment) or certificate, e.g. when both the PayMongo
     * success page and the webhook confirm the same payment.
     */
    private function alreadySentFor(object $notifiable): bool
    {
        if (! method_exists($notifiable, 'notifications')) {
            return false;
        }

        $previous = $notifiable->notifications()
            ->where('type', self::class);

        if (in_array($this->type, self::CERTIFICATE_EMAIL_TYPES, true)) {
            return $this->certificateId !== null
                && $previous->whereIn('data->type', self::CERTIFICATE_EMAIL_TYPES)
                    ->where('data->certificate_id', $this->certificateId)
                    ->exists();
        }

        return $this->courseId !== null
            && $previous->whereIn('data->type', self::ENROLLMENT_EMAIL_TYPES)
                ->where('data->course_id', $this->courseId)
                ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    |
    | Laravel builds both an HTML and a plain-text version of this message.
    | It contains no passwords, keys or payment details.
    |
    */

    public function toMail(object $notifiable): MailMessage
    {
        $isCertificate = in_array($this->type, self::CERTIFICATE_EMAIL_TYPES, true);

        $courseTitle = $this->courseId
            ? (string) Course::whereKey($this->courseId)->value('title')
            : '';

        [$subject, $buttonText, $url] = $isCertificate
            ? [
                'Your certificate is ready'.($courseTitle !== '' ? ': '.$courseTitle : ''),
                'View certificate',
                $this->certificateId
                    ? route('student.certificate.view', $this->certificateId)
                    : route('student.certificates'),
            ]
            : [
                'Enrollment confirmed'.($courseTitle !== '' ? ': '.$courseTitle : ''),
                'Go to course',
                $this->courseId
                    ? route('student.learn.course', $this->courseId)
                    : route('student.my-courses'),
            ];

        return (new MailMessage)
            ->subject(config('app.name', 'PATHWISE').' - '.$subject)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line($this->message)
            ->action($buttonText, $url)
            ->line('Thank you for learning with '.config('app.name', 'PATHWISE').'.');
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE DATA
    |--------------------------------------------------------------------------
    */

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,

            'course_id' => $this->courseId,
            'assignment_id' => $this->assignmentId,
            'submission_id' => $this->submissionId,
            'transaction_id' => $this->transactionId,
            'certificate_id' => $this->certificateId,
            'invitation_id' => $this->invitationId,
            'invitation_code' => $this->invitationCode,
        ];
    }
}