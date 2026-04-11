<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Integration\Validation;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\DocumentType;
use Xterr\Espd\Validation\EspdValidator;
use Xterr\Espd\Validation\Exception\ValidationException;

#[RequiresPhpExtension('saxonc')]
final class EspdValidatorTest extends TestCase
{
    private static EspdValidator $validator;

    public static function setUpBeforeClass(): void
    {
        self::$validator = EspdValidator::create();
    }

    #[Test]
    public function createFactoryReturnsSelf(): void
    {
        $validator = EspdValidator::create();

        self::assertInstanceOf(EspdValidator::class, $validator);
    }

    #[Test]
    public function validateOfficialRequestSample(): void
    {
        $xml = file_get_contents(dirname(__DIR__, 3) . '/resources/test-samples/ESPD-Request.xml');
        self::assertIsString($xml);

        $result = self::$validator->validateXml($xml, DocumentType::Request);

        self::assertTrue($result->isValid(), sprintf(
            'Official ESPD Request sample should be valid, but got %d violation(s): %s',
            count($result),
            implode('; ', array_map('strval', $result->violations)),
        ));
        self::assertCount(0, $result->getFailures());
    }

    #[Test]
    public function validateOfficialResponseSample(): void
    {
        $xml = file_get_contents(dirname(__DIR__, 3) . '/resources/test-samples/ESPD-Response.xml');
        self::assertIsString($xml);

        $result = self::$validator->validateXml($xml, DocumentType::Response);

        self::assertTrue($result->isValid(), sprintf(
            'Official ESPD Response sample should be valid, but got %d violation(s): %s',
            count($result),
            implode('; ', array_map('strval', $result->violations)),
        ));
        self::assertCount(0, $result->getFailures());
    }

    #[Test]
    public function validateEmptyRequestProducesViolations(): void
    {
        $xml = file_get_contents(dirname(__DIR__, 3) . '/tests/Fixtures/invalid-request-empty.xml');
        self::assertIsString($xml);

        $result = self::$validator->validateXml($xml, DocumentType::Request);

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
        $xml = file_get_contents(dirname(__DIR__, 3) . '/tests/Fixtures/invalid-request-missing-criteria.xml');
        self::assertIsString($xml);

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
    public function validateXmlWithDoctypeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DOCTYPE');

        $xml = '<?xml version="1.0"?><!DOCTYPE foo><root/>';

        self::$validator->validateXml($xml, DocumentType::Request);
    }

    #[Test]
    public function validateXmlWithMalformedXmlThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to parse source XML');

        self::$validator->validateXml('<not valid xml', DocumentType::Request);
    }
}
