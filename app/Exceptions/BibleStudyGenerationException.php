<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class BibleStudyGenerationException extends RuntimeException
{
    public const CONFIGURATION = 'configuration';
    public const PROVIDER = 'provider';
    public const TIMEOUT = 'timeout';
    public const VALIDATION = 'validation';
    public const CONFLICT = 'conflict';

    public function __construct(
        string $message,
        public readonly int $httpStatus = 503,
        ?Throwable $previous = null,
        public readonly string $category = self::PROVIDER,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
