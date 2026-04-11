<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\Exception\SaxonNotAvailableException;

final class SaxonNotAvailableExceptionTest extends TestCase
{
    #[Test]
    public function defaultMessage(): void
    {
        $exception = new SaxonNotAvailableException();

        self::assertStringContainsString('saxonc PHP extension is required', $exception->getMessage());
        self::assertStringContainsString('SaxonC-HE 12.x', $exception->getMessage());
    }

    #[Test]
    public function customMessage(): void
    {
        $exception = new SaxonNotAvailableException('Custom error');

        self::assertSame('Custom error', $exception->getMessage());
    }

    #[Test]
    public function previousExceptionChaining(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new SaxonNotAvailableException('wrapper', $previous);

        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('wrapper', $exception->getMessage());
    }
}
