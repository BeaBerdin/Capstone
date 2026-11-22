<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Who receives the EDP's "new / resubmitted student application" email.
 *
 * - PATHWISE_EDP_NOTIFICATION_EMAIL (config services.pathwise.edp_notification_email)
 *   set: the email goes only to that mailbox (several may be given, comma
 *   separated). Super Admin accounts still get the in-app notification.
 * - Not set: each active Super Admin's account email is used, except
 *   addresses that can never receive mail (reserved test domains such as
 *   superadmin@pathwise.test), which would only bounce.
 *
 * The Super Admin's login email is never changed by this.
 */
class EdpNotificationRecipients
{
    /** Top-level domains reserved for testing/examples (RFC 2606, RFC 6761). */
    private const RESERVED_TLDS = ['test', 'example', 'invalid', 'localhost', 'local'];

    /** Second-level domains reserved for documentation (RFC 2606). */
    private const RESERVED_DOMAINS = ['example.com', 'example.net', 'example.org'];

    /**
     * Valid addresses from PATHWISE_EDP_NOTIFICATION_EMAIL (may be empty).
     *
     * @return array<int, string>
     */
    public static function configured(): array
    {
        return collect(explode(',', (string) config('services.pathwise.edp_notification_email')))
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (string $email) => Str::lower($email))
            ->values()
            ->all();
    }

    public static function hasConfigured(): bool
    {
        return self::configured() !== [];
    }

    /**
     * False for addresses that can never receive mail (they only bounce).
     */
    public static function isDeliverable(?string $email): bool
    {
        if (blank($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $domain = Str::lower(Str::afterLast($email, '@'));
        $tld = Str::afterLast($domain, '.');

        if (in_array($tld, self::RESERVED_TLDS, true)) {
            return false;
        }

        foreach (self::RESERVED_DOMAINS as $reserved) {
            if ($domain === $reserved || Str::endsWith($domain, '.'.$reserved)) {
                return false;
            }
        }

        return true;
    }
}
