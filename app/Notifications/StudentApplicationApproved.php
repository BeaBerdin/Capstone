<?php

namespace App\Notifications;

use App\Models\StudentApplication;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Acceptance email after the Super Admin (EDP) approves a student
 * application. It carries the email verification link, valid for 72 hours
 * (ordinary verification links keep Laravel's 60 minutes).
 *
 * The link is the same signed `verification.verify` route as every other
 * verification link, so expiry, signature and hash are checked as usual.
 */
class StudentApplicationApproved extends VerifyEmail
{
    public const EXPIRES_HOURS = 72;

    public function __construct(private ?StudentApplication $teacherApplication = null) {}

    /**
     * Same signed route as Laravel's, with the longer expiry.
     *
     * @param  mixed  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        $parameters = [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ];

        if ($this->teacherApplication) {
            $parameters['teacher_application'] = $this->teacherApplication->getKey();
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(self::EXPIRES_HOURS),
            $parameters,
        );
    }

    /**
     * Its own message (not the shared VerifyEmail::toMailUsing design).
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        if ($this->teacherApplication) {
            return (new MailMessage)
                ->subject('PathWise - Your teacher registration is approved')
                ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                    'heading' => 'Your registration is approved!',
                    'preheader' => 'Verify your email address to start using PathWise.',
                    'name' => $notifiable->name ?? null,
                    'paragraphs' => [
                        'Good news! The EDP office has approved your PathWise teacher account.',
                        'One last step: please verify your email address by clicking the button below. After that you can log in and use your Teacher Dashboard.',
                    ],
                    'buttonText' => 'Verify Email Address',
                    'buttonUrl' => $this->verificationUrl($notifiable),
                    'note' => 'This link expires in '.self::EXPIRES_HOURS.' hours. If it expires, log in to PathWise and choose "Resend verification email".',
                ]);
        }

        return (new MailMessage)
            ->subject('PathWise - Your student application is approved')
            ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                'heading' => 'Your application is approved!',
                'preheader' => 'Verify your email address to start using PathWise.',
                'name' => $notifiable->name ?? null,
                'paragraphs' => [
                    'Good news! The EDP office has reviewed your Study Load and approved your PathWise student account.',
                    'One last step: please verify your email address by clicking the button below. After that you can log in and use your Student Dashboard.',
                ],
                'buttonText' => 'Verify Email Address',
                'buttonUrl' => $this->verificationUrl($notifiable),
                'note' => 'This link expires in '.self::EXPIRES_HOURS.' hours. If it expires, log in to PathWise and choose "Resend verification email".',
            ]);
    }
}
