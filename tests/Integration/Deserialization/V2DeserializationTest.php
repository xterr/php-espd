<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Deserialization;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\CriterionCode;
use Xterr\Espd\Doc\QualificationApplicationRequest;
use Xterr\Espd\Doc\QualificationApplicationResponse;
use Xterr\UBL\Xml\XmlDeserializer;

final class V2DeserializationTest extends TestCase
{
    private static XmlDeserializer $deserializer;

    public static function setUpBeforeClass(): void
    {
        self::$deserializer = new XmlDeserializer();
    }

    private static function fixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/v2.1.1/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    #[Test]
    public function v2RequestDeserializesWithNonNullCriterionTypeCodes(): void
    {
        $xml = self::fixture('ESPD-Request.xml');
        $request = self::$deserializer->deserialize($xml, QualificationApplicationRequest::class);

        $criteria = $request->getTenderingCriterions();
        self::assertNotEmpty($criteria);

        $nonNullCount = 0;

        foreach ($criteria as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code !== null) {
                ++$nonNullCount;
                self::assertInstanceOf(CriterionCode::class, $code);
            }
        }

        self::assertGreaterThan(0, $nonNullCount, 'At least some v2 criteria should have non-null CriterionTypeCode');
    }

    #[Test]
    public function v2RequestCodesAreLegacy(): void
    {
        $xml = self::fixture('ESPD-Request.xml');
        $request = self::$deserializer->deserialize($xml, QualificationApplicationRequest::class);

        foreach ($request->getTenderingCriterions() as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code !== null) {
                self::assertTrue($code->isLegacy(), sprintf('V2 code "%s" should be legacy', $code->value));
            }
        }
    }

    #[Test]
    public function v2CodesMapToV4Equivalents(): void
    {
        $xml = self::fixture('ESPD-Request.xml');
        $request = self::$deserializer->deserialize($xml, QualificationApplicationRequest::class);

        $mappedCount = 0;

        foreach ($request->getTenderingCriterions() as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code === null) {
                continue;
            }

            $v4 = $code->toV4Equivalent();

            if ($v4 !== null) {
                ++$mappedCount;
                self::assertFalse($v4->isLegacy(), sprintf('V4 equivalent "%s" should not be legacy', $v4->value));
            }
        }

        self::assertGreaterThan(0, $mappedCount, 'At least some v2 codes should map to v4 equivalents');
    }

    #[Test]
    public function v2ResponseDeserializesWithNonNullCriterionTypeCodes(): void
    {
        $xml = self::fixture('ESPD-Response.xml');
        $response = self::$deserializer->deserialize($xml, QualificationApplicationResponse::class);

        $criteria = $response->getTenderingCriterions();
        self::assertNotEmpty($criteria);

        $nonNullCount = 0;

        foreach ($criteria as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code !== null) {
                ++$nonNullCount;
                self::assertInstanceOf(CriterionCode::class, $code);
            }
        }

        self::assertGreaterThan(0, $nonNullCount, 'At least some v2 response criteria should have non-null CriterionTypeCode');
    }

    #[Test]
    public function v2RequestContainsSpecificLegacyCodes(): void
    {
        $xml = self::fixture('ESPD-Request.xml');
        $request = self::$deserializer->deserialize($xml, QualificationApplicationRequest::class);

        $codes = [];

        foreach ($request->getTenderingCriterions() as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code !== null) {
                $codes[] = $code;
            }
        }

        self::assertContains(
            CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION,
            $codes,
            'V2 Request should contain CRITERION.EXCLUSION.CONVICTIONS.CORRUPTION',
        );
    }
}
