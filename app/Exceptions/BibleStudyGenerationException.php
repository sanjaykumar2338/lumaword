<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class BibleStudyGenerationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 503,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
