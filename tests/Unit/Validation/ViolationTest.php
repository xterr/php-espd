<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\Severity;
use Xterr\Espd\Validation\Violation;

final class ViolationTest extends TestCase
{
    #[Test]
    public function constructionWithAllProperties(): void
    {
        $violation = new Violation(
            ruleId: 'BR-OTH-04-01',
            severity: Severity::Fatal,
            message: 'UBLVersionID is mandatory',
            location: '/*',
            test: '(cbc:UBLVersionID)',
            pattern: 'BR-OTH-CARD',
        );

        self::assertSame('BR-OTH-04-01', $violation->ruleId);
        self::assertSame(Severity::Fatal, $violation->severity);
        self::assertSame('UBLVersionID is mandatory', $violation->message);
        self::assertSame('/*', $violation->location);
        self::assertSame('(cbc:UBLVersionID)', $violation->test);
        self::assertSame('BR-OTH-CARD', $violation->pattern);
    }

    #[Test]
    public function toStringFormat(): void
    {
        $violation = new Violation(
            ruleId: 'BR-OTH-04-01',
            severity: Severity::Fatal,
            message: 'UBLVersionID is mandatory',
            location: '/*',
            test: '(cbc:UBLVersionID)',
        );

        self::assertSame(
            '[fatal] BR-OTH-04-01: UBLVersionID is mandatory (at /*)',
            (string) $violation,
        );
    }

    #[Test]
    public function defaultPatternIsEmptyString(): void
    {
        $violation = new Violation(
            ruleId: 'BR-OTH-04-01',
            severity: Severity::Error,
            message: 'test',
            location: '/*',
            test: 'test',
        );

        self::assertSame('', $violation->pattern);
    }
}
