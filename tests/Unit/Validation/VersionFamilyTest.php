<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\ProfileExecutionID;
use Xterr\Espd\Validation\VersionFamily;

final class VersionFamilyTest extends TestCase
{
    #[Test]
    public function allCasesExistWithCorrectValues(): void
    {
        self::assertSame('v2.1.1', VersionFamily::V2->value);
        self::assertSame('v3.3.0', VersionFamily::V3->value);
        self::assertSame('v4.0.0', VersionFamily::V4_0->value);
        self::assertSame('v4.1.0', VersionFamily::V4_1->value);
        self::assertCount(4, VersionFamily::cases());
    }

    /**
     * @return iterable<string, array{ProfileExecutionID, VersionFamily}>
     */
    public static function v2ProfileProvider(): iterable
    {
        yield 'v2.0.0-REGULATED' => [ProfileExecutionID::ESPD_EDMV2_0_0_REGULATED, VersionFamily::V2];
        yield 'v2.0.0-SELFCONTAINED' => [ProfileExecutionID::ESPD_EDMV2_0_0_SELFCONTAINED, VersionFamily::V2];
        yield 'v2.1.0-REGULATED' => [ProfileExecutionID::ESPD_EDMV2_1_0_REGULATED, VersionFamily::V2];
        yield 'v2.1.0-SELFCONTAINED' => [ProfileExecutionID::ESPD_EDMV2_1_0_SELFCONTAINED, VersionFamily::V2];
        yield 'v2.1.1-BASIC' => [ProfileExecutionID::ESPD_EDMV2_1_1_BASIC, VersionFamily::V2];
        yield 'v2.1.1-EXTENDED' => [ProfileExecutionID::ESPD_EDMV2_1_1_EXTENDED, VersionFamily::V2];
    }

    /**
     * @return iterable<string, array{ProfileExecutionID, VersionFamily}>
     */
    public static function v3ProfileProvider(): iterable
    {
        yield 'v3.0.0' => [ProfileExecutionID::ESPD_EDMV3_0_0, VersionFamily::V3];
        yield 'v3.0.1' => [ProfileExecutionID::ESPD_EDMV3_0_1, VersionFamily::V3];
        yield 'v3.1.0' => [ProfileExecutionID::ESPD_EDMV3_1_0, VersionFamily::V3];
        yield 'v3.2.0' => [ProfileExecutionID::ESPD_EDMV3_2_0, VersionFamily::V3];
        yield 'v3.3.0' => [ProfileExecutionID::ESPD_EDMV3_3_0, VersionFamily::V3];
    }

    /**
     * @return iterable<string, array{ProfileExecutionID, VersionFamily|null}>
     */
    public static function allProfileMappingProvider(): iterable
    {
        yield from self::v2ProfileProvider();
        yield from self::v3ProfileProvider();
        yield 'v4.0.0' => [ProfileExecutionID::ESPD_EDMV4_0_0, VersionFamily::V4_0];
        yield 'v4.1.0' => [ProfileExecutionID::ESPD_EDMV4_1_0, VersionFamily::V4_1];
    }

    #[Test]
    #[DataProvider('allProfileMappingProvider')]
    public function fromProfileExecutionIDMapsCorrectly(ProfileExecutionID $id, VersionFamily $expected): void
    {
        self::assertSame($expected, VersionFamily::fromProfileExecutionID($id));
    }

    #[Test]
    public function v1ReturnsNull(): void
    {
        self::assertNull(VersionFamily::fromProfileExecutionID(ProfileExecutionID::ESPD_EDMV1_0_2));
    }
}
