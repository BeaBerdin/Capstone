<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Shown to teachers and students when an AI feature cannot run right
     * now (PathWise's own request limits, Gemini's quota or rate limit, or
     * Gemini being unreachable). The technical reason is only logged.
     */
    public const AI_UNAVAILABLE_MESSAGE = 'AI features are currently unavailable. Please try again later.';
}
