<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\Exception\ValidationException;

final class ValidationExceptionTest extends TestCase
{
    #[Test]
    public function messageIsPreserved(): void
    {
        $exception = new ValidationException('Something went wrong');

        self::assertSame('Something went wrong', $exception->getMessage());
    }

    #[Test]
    public function previousExceptionChaining(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new ValidationException('wrapper', $previous);

        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('wrapper', $exception->getMessage());
    }
}
