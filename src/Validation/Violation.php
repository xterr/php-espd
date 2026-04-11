<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

final readonly class Violation
{
    public function __construct(
        public string $ruleId,
        public Severity $severity,
        public string $message,
        public string $location,
        public string $test,
        public string $pattern = '',
    ) {
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s: %s (at %s)', $this->severity->value, $this->ruleId, $this->message, $this->location);
    }
}
