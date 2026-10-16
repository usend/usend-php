<?php

declare(strict_types=1);

namespace Usend;

/**
 * An error from the usend API ({ statusCode, name, message }). Network failures have a null
 * status code and the name "application_error".
 */
class UsendException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorName = 'application_error',
        private readonly ?int $statusCode = null,
    ) {
        parent::__construct($message, $statusCode ?? 0);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /** The API's error name, e.g. "validation_error" or "rate_limit_exceeded". */
    public function getErrorName(): string
    {
        return $this->errorName;
    }
}
