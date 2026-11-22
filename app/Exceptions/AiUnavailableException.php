<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by the Gemini services when the AI cannot be used right now: no API
 * key, Gemini over its quota or rate limit (HTTP 429), or Gemini answering
 * with an HTTP error. The message is technical: it is written to the log
 * only, and users see AiAvailability::MESSAGE instead.
 */
class AiUnavailableException extends RuntimeException
{
}
