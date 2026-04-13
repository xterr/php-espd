<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Deserialization;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\CriterionCode;
use Xterr\Espd\Doc\QualificationApplicationRequest;
use Xterr\Espd\Doc\QualificationApplicationResponse;
use Xterr\UBL\Xml\XmlDeserializer;

final class V4_1DeserializationTest extends TestCase
{
    private static XmlDeserializer $deserializer;

    public static function setUpBeforeClass(): void
    {
        self::$deserializer = new XmlDeserializer();
    }

    private static function fixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/v4.1.0/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    #[Test]
    public function v41RequestDeserializesWithNonNullCriterionTypeCodes(): void
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

        self::assertGreaterThan(0, $nonNullCount, 'At least some v4.1.0 criteria should have non-null CriterionTypeCode');
    }

    #[Test]
    public function v41CodesAreNotLegacy(): void
    {
        $xml = self::fixture('ESPD-Request.xml');
        $request = self::$deserializer->deserialize($xml, QualificationApplicationRequest::class);

        foreach ($request->getTenderingCriterions() as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code !== null) {
                self::assertFalse($code->isLegacy(), sprintf('V4.1 code "%s" should not be legacy', $code->value));
            }
        }
    }

    #[Test]
    public function v41ResponseDeserializesWithNonNullCriterionTypeCodes(): void
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

        self::assertGreaterThan(0, $nonNullCount, 'At least some v4.1.0 response criteria should have non-null CriterionTypeCode');
    }

    #[Test]
    public function v41RequestContainsSpecificV4Codes(): void
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
            CriterionCode::CRIME_ORG,
            $codes,
            'V4.1 Request should contain crime-org criterion code',
        );
    }
}
