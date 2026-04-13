<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Validation;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\DocumentType;
use Xterr\Espd\Validation\EspdValidator;
use Xterr\Espd\Validation\VersionFamily;

#[RequiresPhpExtension('saxonc')]
final class V3ValidationTest extends TestCase
{
    private static EspdValidator $validator;

    public static function setUpBeforeClass(): void
    {
        self::$validator = EspdValidator::create();
    }

    private static function fixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/v3.3.0/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    #[Test]
    public function v330RequestValidatesClean(): void
    {
        $xml = self::fixture('ESPD-Request.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request);

        self::assertTrue($result->isValid(), sprintf(
            'v3.3.0 Request should be valid, got %d failure(s): %s',
            count($result->getFailures()),
            implode('; ', array_map('strval', $result->getFailures())),
        ));
    }

    #[Test]
    public function v330ResponseValidatesClean(): void
    {
        $xml = self::fixture('ESPD-Response.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Response);

        self::assertTrue($result->isValid(), sprintf(
            'v3.3.0 Response should be valid, got %d failure(s): %s',
            count($result->getFailures()),
            implode('; ', array_map('strval', $result->getFailures())),
        ));
    }

    #[Test]
    public function v330RequestValidatesCleanWithExplicitVersion(): void
    {
        $xml = self::fixture('ESPD-Request.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request, VersionFamily::V3);

        self::assertTrue($result->isValid(), sprintf(
            'v3.3.0 Request with explicit V3 should be valid, got %d failure(s): %s',
            count($result->getFailures()),
            implode('; ', array_map('strval', $result->getFailures())),
        ));
    }
}
