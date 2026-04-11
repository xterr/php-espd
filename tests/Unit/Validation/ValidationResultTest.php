<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\Severity;
use Xterr\Espd\Validation\ValidationResult;
use Xterr\Espd\Validation\Violation;

final class ValidationResultTest extends TestCase
{
    #[Test]
    public function isValidReturnsTrueWhenEmpty(): void
    {
        $result = new ValidationResult([]);

        self::assertTrue($result->isValid());
    }

    #[Test]
    public function isValidReturnsTrueWithOnlyWarnings(): void
    {
        $result = new ValidationResult([
            $this->createViolation(Severity::Warning),
        ]);

        self::assertTrue($result->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWithFatalViolation(): void
    {
        $result = new ValidationResult([
            $this->createViolation(Severity::Fatal),
        ]);

        self::assertFalse($result->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWithErrorViolation(): void
    {
        $result = new ValidationResult([
            $this->createViolation(Severity::Error),
        ]);

        self::assertFalse($result->isValid());
    }

    #[Test]
    public function getFatalsFiltersCorrectly(): void
    {
        $fatal = $this->createViolation(Severity::Fatal, 'FATAL-01');
        $error = $this->createViolation(Severity::Error, 'ERR-01');
        $warning = $this->createViolation(Severity::Warning, 'WARN-01');

        $result = new ValidationResult([$fatal, $error, $warning]);

        $fatals = $result->getFatals();
        self::assertCount(1, $fatals);
        self::assertSame('FATAL-01', $fatals[0]->ruleId);
    }

    #[Test]
    public function getErrorsFiltersCorrectly(): void
    {
        $fatal = $this->createViolation(Severity::Fatal, 'FATAL-01');
        $error = $this->createViolation(Severity::Error, 'ERR-01');
        $warning = $this->createViolation(Severity::Warning, 'WARN-01');

        $result = new ValidationResult([$fatal, $error, $warning]);

        $errors = $result->getErrors();
        self::assertCount(1, $errors);
        self::assertSame('ERR-01', $errors[0]->ruleId);
    }

    #[Test]
    public function getWarningsFiltersCorrectly(): void
    {
        $fatal = $this->createViolation(Severity::Fatal, 'FATAL-01');
        $warning = $this->createViolation(Severity::Warning, 'WARN-01');

        $result = new ValidationResult([$fatal, $warning]);

        $warnings = $result->getWarnings();
        self::assertCount(1, $warnings);
        self::assertSame('WARN-01', $warnings[0]->ruleId);
    }

    #[Test]
    public function getFailuresReturnsFatalsAndErrors(): void
    {
        $fatal = $this->createViolation(Severity::Fatal, 'FATAL-01');
        $error = $this->createViolation(Severity::Error, 'ERR-01');
        $warning = $this->createViolation(Severity::Warning, 'WARN-01');

        $result = new ValidationResult([$fatal, $error, $warning]);

        $failures = $result->getFailures();
        self::assertCount(2, $failures);
        self::assertSame('FATAL-01', $failures[0]->ruleId);
        self::assertSame('ERR-01', $failures[1]->ruleId);
    }

    #[Test]
    public function countReturnsViolationCount(): void
    {
        $result = new ValidationResult([
            $this->createViolation(Severity::Fatal),
            $this->createViolation(Severity::Error),
            $this->createViolation(Severity::Warning),
        ]);

        self::assertCount(3, $result);
    }

    #[Test]
    public function hasWarningsReturnsTrueWhenWarningsExist(): void
    {
        $result = new ValidationResult([
            $this->createViolation(Severity::Warning),
        ]);

        self::assertTrue($result->hasWarnings());
    }

    #[Test]
    public function hasWarningsReturnsFalseWhenNoWarnings(): void
    {
        $result = new ValidationResult([
            $this->createViolation(Severity::Fatal),
        ]);

        self::assertFalse($result->hasWarnings());
    }

    private function createViolation(Severity $severity, string $ruleId = 'TEST-01'): Violation
    {
        return new Violation(
            ruleId: $ruleId,
            severity: $severity,
            message: 'test message',
            location: '/*',
            test: 'test()',
        );
    }
}
