<?php

namespace App\Notifications;

use App\Models\StudentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Rejection email after the Super Admin (EDP) rejects a Study Load. It
 * gives the actual reason and tells the student how to resubmit with the
 * same account. The controller sends it and warns the EDP if it fails.
 */
class StudentApplicationRejected extends Notification
{
    use Queueable;

    public function __construct(public StudentApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->application->isTeacherApplication()) {
            return (new MailMessage)
                ->subject('PathWise - Your teacher registration was not approved')
                ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                    'heading' => 'Your registration needs another look',
                    'preheader' => 'The EDP office could not approve your teacher registration yet.',
                    'name' => $notifiable->name ?? null,
                    'paragraphs' => [
                        'The EDP office reviewed your PathWise teacher registration and could not approve it yet.',
                    ],
                    'highlight' => [
                        'label' => 'Reason',
                        'text' => (string) $this->application->rejection_reason,
                    ],
                    'buttonText' => 'Log in to PathWise',
                    'buttonUrl' => route('login'),
                    'note' => 'Log in with the same email and password to see your registration status. When the issue is resolved, you can ask the EDP office to review it again. You do not need a new account.',
                ]);
        }

        return (new MailMessage)
            ->subject('PathWise - Your student application needs a corrected Study Load')
            ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                'heading' => 'Your application needs another look',
                'preheader' => 'Please upload a corrected Study Load.',
                'name' => $notifiable->name ?? null,
                'paragraphs' => [
                    'The EDP office reviewed the Study Load you submitted to PathWise and could not approve it yet.',
                ],
                'highlight' => [
                    'label' => 'Reason',
                    'text' => (string) $this->application->rejection_reason,
                ],
                'buttonText' => 'Log in and resubmit',
                'buttonUrl' => route('login'),
                'note' => 'Log in with the same email and password, upload a corrected Study Load on your application page, and it will be reviewed again. You do not need a new account.',
            ]);
    }
}
