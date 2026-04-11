<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\Severity;

final class SeverityTest extends TestCase
{
    #[Test]
    public function allCasesExistWithCorrectValues(): void
    {
        self::assertSame('fatal', Severity::Fatal->value);
        self::assertSame('error', Severity::Error->value);
        self::assertSame('warning', Severity::Warning->value);
        self::assertCount(3, Severity::cases());
    }

    #[Test]
    public function fromValidValues(): void
    {
        self::assertSame(Severity::Fatal, Severity::from('fatal'));
        self::assertSame(Severity::Error, Severity::from('error'));
        self::assertSame(Severity::Warning, Severity::from('warning'));
    }

    #[Test]
    public function tryFromInvalidValueReturnsNull(): void
    {
        self::assertNull(Severity::tryFrom('info'));
        self::assertNull(Severity::tryFrom(''));
        self::assertNull(Severity::tryFrom('FATAL'));
    }
}
