<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation\Exception;

final class ValidationException extends \RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
