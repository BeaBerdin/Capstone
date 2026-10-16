<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by GeminiEvaluationService when an AI evaluation cannot be
 * produced. Its message is safe to show to the teacher.
 */
class AiEvaluationException extends RuntimeException
{
}
