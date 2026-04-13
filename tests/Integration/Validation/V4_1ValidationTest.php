<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Validation;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\DocumentType;
use Xterr\Espd\Validation\EspdValidator;
use Xterr\Espd\Validation\Exception\ValidationException;
use Xterr\Espd\Validation\VersionFamily;

#[RequiresPhpExtension('saxonc')]
final class V4_1ValidationTest extends TestCase
{
    private static EspdValidator $validator;

    public static function setUpBeforeClass(): void
    {
        self::$validator = EspdValidator::create();
    }

    private static function fixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/v4.1.0/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    private static function rootFixture(string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/' . $file;
        $xml = file_get_contents($path);
        self::assertIsString($xml, sprintf('Fixture not found: %s', $path));

        return $xml;
    }

    #[Test]
    public function createFactoryReturnsSelf(): void
    {
        $validator = EspdValidator::create();

        self::assertInstanceOf(EspdValidator::class, $validator);
    }

    #[Test]
    public function v410RequestValidatesClean(): void
    {
        $xml = self::fixture('ESPD-Request.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request);

        self::assertTrue($result->isValid(), sprintf(
            'v4.1.0 Request should be valid, got %d failure(s): %s',
            count($result->getFailures()),
            implode('; ', array_map('strval', $result->getFailures())),
        ));
        self::assertCount(0, $result->getFailures());
    }

    #[Test]
    public function v410ResponseValidatesClean(): void
    {
        $xml = self::fixture('ESPD-Response.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Response);

        self::assertTrue($result->isValid(), sprintf(
            'v4.1.0 Response should be valid, got %d failure(s): %s',
            count($result->getFailures()),
            implode('; ', array_map('strval', $result->getFailures())),
        ));
        self::assertCount(0, $result->getFailures());
    }

    #[Test]
    public function v410RequestValidatesCleanWithExplicitVersion(): void
    {
        $xml = self::fixture('ESPD-Request.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request, VersionFamily::V4_1);

        self::assertTrue($result->isValid());
    }

    #[Test]
    public function crossVersionValidationProducesFailures(): void
    {
        $xml = self::fixture('ESPD-Request.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request, VersionFamily::V4_0);

        self::assertFalse($result->isValid(), 'Validating v4.1.0 doc against v4.0.0 rules should produce violations');
        self::assertGreaterThan(0, count($result->getFailures()));
    }

    #[Test]
    public function validateEmptyRequestProducesViolations(): void
    {
        $xml = self::rootFixture('invalid-request-empty.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request, VersionFamily::V4_1);

        self::assertFalse($result->isValid());

        $ruleIds = array_map(
            static fn ($v) => $v->ruleId,
            $result->violations,
        );

        self::assertContains('BR-OTH-04-01', $ruleIds, 'Should flag missing UBLVersionID');
        self::assertContains('BR-OTH-04-06', $ruleIds, 'Should flag missing ContractFolderID');
    }

    #[Test]
    public function validateRequestMissingCriteriaProducesViolations(): void
    {
        $xml = self::rootFixture('invalid-request-missing-criteria.xml');

        $result = self::$validator->validateXml($xml, DocumentType::Request);

        $ruleIds = array_map(
            static fn ($v) => $v->ruleId,
            $result->violations,
        );

        self::assertTrue(
            in_array('BR-OTH-04-06', $ruleIds, true) || in_array('BR-REQ-30', $ruleIds, true),
            sprintf(
                'Expected BR-OTH-04-06 or BR-REQ-30 but got: %s',
                implode(', ', $ruleIds),
            ),
        );
    }

    #[Test]
    public function doctypeThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DOCTYPE');

        $xml = '<?xml version="1.0"?><!DOCTYPE foo><root/>';

        self::$validator->validateXml($xml, DocumentType::Request);
    }

    #[Test]
    public function malformedXmlThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to parse source XML');

        self::$validator->validateXml('<not valid xml', DocumentType::Request, VersionFamily::V4_1);
    }

    #[Test]
    public function missingProfileExecutionIDWithoutExplicitVersionThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not detect ESPD-EDM version');

        $xml = self::rootFixture('invalid-request-empty.xml');

        self::$validator->validateXml($xml, DocumentType::Request);
    }
}
