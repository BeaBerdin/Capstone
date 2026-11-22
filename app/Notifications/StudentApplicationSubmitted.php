<?php

namespace App\Notifications;

use App\Models\StudentApplication;
use App\Notifications\Channels\SafeMailChannel;
use App\Support\EdpNotificationRecipients;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the Super Admin (EDP): a student submitted (or resubmitted) a Study
 * Load for review. Saved in-app; emailed when mail is configured. A failed
 * email is logged by SafeMailChannel and never undoes the submission.
 */
class StudentApplicationSubmitted extends Notification
{
    use Queueable;

    public const TYPE = 'student_application_submitted';

    public function __construct(
        public StudentApplication $application,
        public bool $resubmission = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        // The configured EDP mailbox (PATHWISE_EDP_NOTIFICATION_EMAIL),
        // notified on demand: email only.
        if ($notifiable instanceof AnonymousNotifiable) {
            return MailSettings::isConfigured() ? [SafeMailChannel::class] : [];
        }

        // A Super Admin account: always the in-app notification. Its login
        // email is used only when no EDP mailbox is configured, and never
        // when it cannot receive mail (e.g. superadmin@pathwise.test).
        $channels = ['database'];

        if (
            MailSettings::isConfigured()
            && ! EdpNotificationRecipients::hasConfigured()
            && EdpNotificationRecipients::isDeliverable($notifiable->email ?? null)
        ) {
            $channels[] = SafeMailChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->application->user;
        $heading = $this->resubmission ? 'Student application resubmitted' : 'New student application';

        return (new MailMessage)
            ->subject('PathWise - '.$heading.': '.($student?->name ?? 'Student'))
            ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                'heading' => $heading,
                'preheader' => ($student?->name ?? 'A student').' is waiting for Study Load verification.',
                'name' => $notifiable->name ?? null,
                'paragraphs' => [
                    $this->resubmission
                        ? ($student?->name ?? 'A student').' uploaded a corrected Study Load (submission #'.$this->application->submission_no.') after the previous one was rejected.'
                        : ($student?->name ?? 'A student').' registered as a student and uploaded a Study Load for verification.',
                    'Email: '.($student?->email ?? '-'),
                    'Please review the Study Load and approve or reject the application. The student cannot use PathWise until it is approved.',
                ],
                'buttonText' => 'Review application',
                'buttonUrl' => route('super-admin.student-applications.show', $this->application),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->application->user;

        return [
            'title' => $this->resubmission ? 'Student application resubmitted' : 'New student application',
            'message' => ($student?->name ?? 'A student')
                .($this->resubmission ? ' uploaded a corrected Study Load for review.' : ' is waiting for Study Load verification.'),
            'type' => self::TYPE,
            'application_id' => $this->application->id,
        ];
    }
}
