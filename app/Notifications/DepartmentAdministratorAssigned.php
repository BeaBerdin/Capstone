<?php

namespace App\Notifications;

use App\Notifications\Channels\SafeMailChannel;
use App\Support\EdpNotificationRecipients;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To an Administrator the Super Admin just assigned to a department: a
 * plain informational email (no buttons or links). Email only; sent when
 * mail is configured and the address can receive mail. A failed email is
 * logged by SafeMailChannel and never undoes the assignment.
 */
class DepartmentAdministratorAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public string $departmentName,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return MailSettings::isConfigured()
            && EdpNotificationRecipients::isDeliverable($notifiable->email ?? null)
            ? [SafeMailChannel::class]
            : [];
    }

    public function message(): string
    {
        return 'You have been assigned as the Administrator of '.$this->departmentName.' on PathWise.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PathWise - Department assignment: '.$this->departmentName)
            ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                'heading' => 'Department assignment',
                'preheader' => $this->message(),
                'name' => $notifiable->name ?? null,
                'paragraphs' => [$this->message()],
            ]);
    }
}
