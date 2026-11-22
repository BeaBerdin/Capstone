<?php

namespace App\Exceptions;

use RuntimeException;

/** Expected unusable quiz output, distinct from availability failures and application bugs. */
final class AiResponseException extends RuntimeException
{
}
