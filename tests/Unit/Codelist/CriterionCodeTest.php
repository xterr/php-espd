<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Codelist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\CriterionCode;

final class CriterionCodeTest extends TestCase
{
    #[Test]
    public function tryFromResolvesV2LongFormCode(): void
    {
        $code = CriterionCode::tryFrom('CRITERION.EXCLUSION.CONVICTIONS.CORRUPTION');

        self::assertNotNull($code);
        self::assertSame(CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION, $code);
    }

    #[Test]
    public function tryFromResolvesV4ShortCode(): void
    {
        $code = CriterionCode::tryFrom('crime-org');

        self::assertNotNull($code);
        self::assertSame(CriterionCode::CRIME_ORG, $code);
    }

    #[Test]
    public function tryFromReturnsNullForUnknownCode(): void
    {
        self::assertNull(CriterionCode::tryFrom('UNKNOWN.CODE.VALUE'));
    }

    #[Test]
    public function isLegacyReturnsTrueForV2Codes(): void
    {
        self::assertTrue(CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION->isLegacy());
        self::assertTrue(CriterionCode::V2_CRITERION_SELECTION_ALL->isLegacy());
        self::assertTrue(CriterionCode::V2_CRITERION_OTHER_EO_DATA_SHELTERED_WORKSHOP->isLegacy());
    }

    #[Test]
    public function isLegacyReturnsFalseForV4Codes(): void
    {
        self::assertFalse(CriterionCode::CRIME_ORG->isLegacy());
        self::assertFalse(CriterionCode::CORRUPTION->isLegacy());
        self::assertFalse(CriterionCode::TAX_PAY->isLegacy());
    }

    #[Test]
    public function toV4EquivalentMapsV2ToV4(): void
    {
        $v4 = CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION->toV4Equivalent();

        self::assertNotNull($v4);
        self::assertSame(CriterionCode::CORRUPTION, $v4);
        self::assertFalse($v4->isLegacy());
    }

    #[Test]
    public function toV4EquivalentReturnsSelfForV4Codes(): void
    {
        $code = CriterionCode::CRIME_ORG;

        self::assertSame($code, $code->toV4Equivalent());
    }

    #[Test]
    public function toV4EquivalentReturnsNullForV2OnlyCodes(): void
    {
        self::assertNull(CriterionCode::V2_CRITERION_SELECTION_ALL->toV4Equivalent());
    }

    #[Test]
    public function allMappedV2CodesResolveToValidNonLegacyV4Codes(): void
    {
        $mappingFile = dirname(__DIR__, 3) . '/resources/criterion/v2-to-v4-mapping.php';
        self::assertFileExists($mappingFile);

        /** @var array<string, string> $map */
        $map = require $mappingFile;

        foreach ($map as $v2Value => $v4Value) {
            $v2Code = CriterionCode::tryFrom($v2Value);
            self::assertNotNull($v2Code, sprintf('V2 code "%s" should be a valid CriterionCode case', $v2Value));

            $v4Code = $v2Code->toV4Equivalent();
            self::assertNotNull($v4Code, sprintf('V2 code "%s" should map to a V4 equivalent', $v2Value));
            self::assertFalse($v4Code->isLegacy(), sprintf('V4 equivalent of "%s" should not be legacy', $v2Value));
            self::assertSame($v4Value, $v4Code->value, sprintf('V2 code "%s" should map to "%s"', $v2Value, $v4Value));
        }
    }

    #[Test]
    public function exactlysixtySixV2CodesExist(): void
    {
        $v2Cases = array_filter(
            CriterionCode::cases(),
            static fn (CriterionCode $c) => str_starts_with($c->name, 'V2_'),
        );

        self::assertCount(66, $v2Cases, 'CriterionCode should have exactly 66 V2_ prefixed cases');
    }

    #[Test]
    public function exactlySixtyFourV4CodesExist(): void
    {
        $v4Cases = array_filter(
            CriterionCode::cases(),
            static fn (CriterionCode $c) => !str_starts_with($c->name, 'V2_'),
        );

        self::assertCount(64, $v4Cases, 'CriterionCode should have exactly 64 non-V2 (v4) cases');
    }
}
