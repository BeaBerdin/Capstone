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
    public const MESSAGE = 'AI features are currently unavailable. Please try again later.';

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
        Log::warning('PATHWISE AI feature unavailable: '.$feature, array_merge($context, array_filter([
            'exception' => $exception ? get_class($exception) : null,
            'reason' => $exception?->getMessage(),
        ])));
    }
}
