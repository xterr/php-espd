<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Validation;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\DocumentType;
use Xterr\Espd\Validation\EspdValidator;
use Xterr\Espd\Validation\Exception\ValidationException;
use Xterr\Espd\Validation\ValidationResult;
use Xterr\Espd\Validation\VersionFamily;

#[RequiresPhpExtension('saxonc')]
final class V2ValidationTest extends TestCase
{
    private static EspdValidator $validator;

    public static function setUpBeforeClass(): void
    {
        self::$validator = EspdValidator::create();
    }

    private static function fixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/v2.1.1/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    #[Test]
    public function v211RequestValidatesWithExplicitVersion(): void
    {
        $xml = self::fixture('ESPD-Request.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request, VersionFamily::V2);

        self::assertInstanceOf(ValidationResult::class, $result);
    }

    #[Test]
    public function v211ResponseValidatesWithExplicitVersion(): void
    {
        $xml = self::fixture('ESPD-Response.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Response, VersionFamily::V2);

        self::assertInstanceOf(ValidationResult::class, $result);
    }

    #[Test]
    public function v211RequestAutoDetectThrowsBecauseNoProfileExecutionID(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not detect ESPD-EDM version');

        $xml = self::fixture('ESPD-Request.xml');

        self::$validator->validateXml($xml, DocumentType::Request);
    }

    #[Test]
    public function v211ResponseAutoDetectThrowsBecauseNoProfileExecutionID(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not detect ESPD-EDM version');

        $xml = self::fixture('ESPD-Response.xml');

        self::$validator->validateXml($xml, DocumentType::Response);
    }
}
