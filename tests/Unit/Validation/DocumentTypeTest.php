<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\DocumentType;
use Xterr\Espd\Validation\Exception\ValidationException;
use Xterr\Espd\Validation\VersionFamily;

final class DocumentTypeTest extends TestCase
{
    #[Test]
    public function xslDirectoryReturnsCorrectValues(): void
    {
        self::assertSame('ESPDRequest', DocumentType::Request->xslDirectory());
        self::assertSame('ESPDResponse', DocumentType::Response->xslDirectory());
    }

    #[Test]
    public function ruleFilesReturnsNonEmptySortedXslFilenames(): void
    {
        $resourcesDir = dirname(__DIR__, 3) . '/resources/validation';

        $files = DocumentType::Request->ruleFiles($resourcesDir, VersionFamily::V4_1);

        self::assertNotEmpty($files);

        foreach ($files as $file) {
            self::assertStringEndsWith('.xsl', $file);
            self::assertStringNotContainsString('/', $file);
        }

        $sorted = $files;
        sort($sorted, \SORT_STRING);
        self::assertSame($sorted, $files);
    }

    #[Test]
    public function ruleFilesThrowsValidationExceptionForNonExistentDirectory(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No XSL validation rule files found in');

        DocumentType::Request->ruleFiles('/nonexistent/path', VersionFamily::V4_1);
    }

    #[Test]
    public function ruleFilesReturnsDifferentFilesForDifferentVersions(): void
    {
        $resourcesDir = dirname(__DIR__, 3) . '/resources/validation';

        $v2Files = DocumentType::Request->ruleFiles($resourcesDir, VersionFamily::V2);
        $v4Files = DocumentType::Request->ruleFiles($resourcesDir, VersionFamily::V4_1);

        self::assertNotSame($v2Files, $v4Files, 'V2 and V4.1 should return different rule file lists');
    }

    #[Test]
    public function v211HasCorrectFileCount(): void
    {
        $resourcesDir = dirname(__DIR__, 3) . '/resources/validation';

        $requestFiles = DocumentType::Request->ruleFiles($resourcesDir, VersionFamily::V2);
        $responseFiles = DocumentType::Response->ruleFiles($resourcesDir, VersionFamily::V2);

        self::assertCount(10, $requestFiles, 'V2.1.1 Request should have 10 XSL files');
        self::assertCount(12, $responseFiles, 'V2.1.1 Response should have 12 XSL files');
    }

    #[Test]
    public function v330HasCorrectFileCount(): void
    {
        $resourcesDir = dirname(__DIR__, 3) . '/resources/validation';

        $requestFiles = DocumentType::Request->ruleFiles($resourcesDir, VersionFamily::V3);
        $responseFiles = DocumentType::Response->ruleFiles($resourcesDir, VersionFamily::V3);

        self::assertCount(10, $requestFiles, 'V3.3.0 Request should have 10 XSL files');
        self::assertCount(12, $responseFiles, 'V3.3.0 Response should have 12 XSL files');
    }

    #[Test]
    public function allVersionsReturnNonEmptyRuleFiles(): void
    {
        $resourcesDir = dirname(__DIR__, 3) . '/resources/validation';

        foreach (VersionFamily::cases() as $version) {
            foreach ([DocumentType::Request, DocumentType::Response] as $docType) {
                $files = $docType->ruleFiles($resourcesDir, $version);
                self::assertNotEmpty(
                    $files,
                    sprintf('%s %s should return non-empty rule files', $version->value, $docType->name),
                );

                foreach ($files as $file) {
                    self::assertStringEndsWith('.xsl', $file);
                }
            }
        }
    }
}
