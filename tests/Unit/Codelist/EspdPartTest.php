<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Codelist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\EspdPart;

final class EspdPartTest extends TestCase
{
    #[Test]
    public function exactlySixCasesExist(): void
    {
        self::assertCount(6, EspdPart::cases());
    }

    #[Test]
    public function stringValuesAreRomanNumerals(): void
    {
        self::assertSame('I', EspdPart::I->value);
        self::assertSame('II', EspdPart::II->value);
        self::assertSame('III', EspdPart::III->value);
        self::assertSame('IV', EspdPart::IV->value);
        self::assertSame('V', EspdPart::V->value);
        self::assertSame('VI', EspdPart::VI->value);
    }

    #[Test]
    public function tryFromResolvesValidValues(): void
    {
        self::assertSame(EspdPart::I, EspdPart::tryFrom('I'));
        self::assertSame(EspdPart::II, EspdPart::tryFrom('II'));
        self::assertSame(EspdPart::III, EspdPart::tryFrom('III'));
        self::assertSame(EspdPart::IV, EspdPart::tryFrom('IV'));
        self::assertSame(EspdPart::V, EspdPart::tryFrom('V'));
        self::assertSame(EspdPart::VI, EspdPart::tryFrom('VI'));
    }

    #[Test]
    public function tryFromReturnsNullForInvalidValues(): void
    {
        self::assertNull(EspdPart::tryFrom('VII'));
        self::assertNull(EspdPart::tryFrom(''));
        self::assertNull(EspdPart::tryFrom('1'));
    }

    #[Test]
    public function labelReturnsCorrectDescriptions(): void
    {
        self::assertSame(
            'Information concerning the procurement procedure and the contracting authority or contracting entity',
            EspdPart::I->label(),
        );
        self::assertSame(
            'Information concerning the economic operator',
            EspdPart::II->label(),
        );
        self::assertSame(
            'Exclusion grounds',
            EspdPart::III->label(),
        );
        self::assertSame(
            'Selection criteria',
            EspdPart::IV->label(),
        );
        self::assertSame(
            'Reduction of the number of qualified candidates',
            EspdPart::V->label(),
        );
        self::assertSame(
            'Concluding statements',
            EspdPart::VI->label(),
        );
    }
}
