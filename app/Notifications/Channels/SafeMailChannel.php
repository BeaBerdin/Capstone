<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Laravel's mail channel, but a failed send (wrong SMTP settings, mail
 * server down) is logged instead of thrown, so an enrollment, payment or
 * certificate is never interrupted by an email problem.
 */
class SafeMailChannel extends MailChannel
{
    public function send($notifiable, Notification $notification)
    {
        try {
            return parent::send($notifiable, $notification);
        } catch (Throwable $e) {
            Log::warning('PATHWISE email could not be sent; the action itself was completed.', [
                'notification' => $notification::class,
                // On-demand recipients (an email address only) have no key.
                'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
