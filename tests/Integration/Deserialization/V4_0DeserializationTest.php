<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Deserialization;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\CriterionCode;
use Xterr\Espd\Doc\QualificationApplicationRequest;
use Xterr\UBL\Xml\XmlDeserializer;

final class V4_0DeserializationTest extends TestCase
{
    private static XmlDeserializer $deserializer;

    public static function setUpBeforeClass(): void
    {
        self::$deserializer = new XmlDeserializer();
    }

    private static function fixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/v4.0.0/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    #[Test]
    public function v40RequestDeserializesWithNonNullCriterionTypeCodes(): void
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

        self::assertGreaterThan(0, $nonNullCount, 'At least some v4.0.0 criteria should have non-null CriterionTypeCode');
    }

    #[Test]
    public function v40CodesAreNotLegacy(): void
    {
        $xml = self::fixture('ESPD-Request.xml');
        $request = self::$deserializer->deserialize($xml, QualificationApplicationRequest::class);

        foreach ($request->getTenderingCriterions() as $criterion) {
            $code = $criterion->getCriterionTypeCode();

            if ($code !== null) {
                self::assertFalse($code->isLegacy(), sprintf('V4.0 code "%s" should not be legacy', $code->value));
            }
        }
    }
}
