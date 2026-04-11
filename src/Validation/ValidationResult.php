<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

final readonly class ValidationResult implements \Countable
{
    /** @param list<Violation> $violations */
    public function __construct(
        public array $violations,
    ) {
    }

    public function isValid(): bool
    {
        foreach ($this->violations as $violation) {
            if ($violation->severity === Severity::Fatal || $violation->severity === Severity::Error) {
                return false;
            }
        }

        return true;
    }

    public function hasWarnings(): bool
    {
        foreach ($this->violations as $violation) {
            if ($violation->severity === Severity::Warning) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Violation> */
    public function getFatals(): array
    {
        return $this->filterBySeverity(Severity::Fatal);
    }

    /** @return list<Violation> Returns violations with Error severity only */
    public function getErrors(): array
    {
        return $this->filterBySeverity(Severity::Error);
    }

    /** @return list<Violation> Returns all fatal and error violations */
    public function getFailures(): array
    {
        return $this->filterBySeverity(Severity::Fatal, Severity::Error);
    }

    /** @return list<Violation> */
    public function getWarnings(): array
    {
        return $this->filterBySeverity(Severity::Warning);
    }

    public function count(): int
    {
        return count($this->violations);
    }

    /** @return list<Violation> */
    private function filterBySeverity(Severity ...$severities): array
    {
        return array_values(array_filter(
            $this->violations,
            static fn (Violation $v): bool => in_array($v->severity, $severities, true),
        ));
    }
}
