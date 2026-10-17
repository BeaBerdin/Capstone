<?php

namespace App\Support;

/**
 * Whether PATHWISE can really send email.
 *
 * The "log" and "array" mailers only write messages to the log or to
 * memory (local development and tests), so they count as not configured.
 * Used by the email notifications and the Super Admin "System Status" card.
 */
class MailSettings
{
    public const NOT_SENDING = ['log', 'array'];

    public static function isConfigured(): bool
    {
        $mailer = config('mail.default');

        return is_string($mailer)
            && $mailer !== ''
            && ! in_array($mailer, self::NOT_SENDING, true);
    }
}
