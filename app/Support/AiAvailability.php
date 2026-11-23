<?php

namespace App\Support;

use App\Exceptions\AiUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single message users see when an AI feature cannot run right now
 * (PathWise's own AI request limits, Gemini's quota or rate limit, Gemini
 * unreachable or answering with a server error). The technical reason is
 * written to the log with Log::warning and never shown.
 */
class AiAvailability
{
    public const MESSAGE = 'AI features are unavailable at the moment. Please try again later.';

    /** PathWise's hourly AI generation limit was reached. */
    public const HOURLY_LIMIT_MESSAGE = 'AI features are unavailable at the moment. Please try again in about an hour.';

    /**
     * True when the failure means "the AI cannot be used right now" rather
     * than "the AI answered with unusable content".
     */
    public static function isUnavailable(Throwable $exception): bool
    {
        return $exception instanceof AiUnavailableException
            || $exception instanceof ConnectionException;
    }

    /**
     * Log why the AI was unavailable (never shown to the user).
     *
     * @param  array<string, mixed>  $context
     */
    public static function logUnavailable(string $feature, ?Throwable $exception = null, array $context = []): void
    {
        // Provider messages, response bodies and connection exception text can contain secrets.
        $safe = [];
        foreach (['teacher_id', 'quiz_id', 'submission_id', 'retry_after_seconds'] as $key) {
            if (isset($context[$key]) && is_int($context[$key]) && $context[$key] >= 0) {
                $safe[$key] = $context[$key];
            }
        }
        $limits = ['PathWise per-minute AI generation limit', 'PathWise hourly AI generation limit', 'PathWise per-minute AI evaluation limit'];
        if (in_array($context['limit'] ?? null, $limits, true)) $safe['limit'] = $context['limit'];
        $code = $exception?->getCode();
        $status = is_int($code) && $code >= 100 && $code <= 599 ? $code : null;
        $safe['http_status'] = $status;
        $safe['reason'] = $exception instanceof ConnectionException ? 'Connection failure'
            : ($status ? 'HTTP '.$status : 'AI service or response failure');
        if ($exception) $safe['exception'] = get_class($exception);
        $feature = in_array($feature, ['quiz generation', 'AI evaluation'], true) ? $feature : 'AI';
        Log::warning('PATHWISE AI feature unavailable: '.$feature, $safe);
    }
}
