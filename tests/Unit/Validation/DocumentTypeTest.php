<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\DocumentType;
use Xterr\Espd\Validation\Exception\ValidationException;

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

        $files = DocumentType::Request->ruleFiles($resourcesDir);

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

        DocumentType::Request->ruleFiles('/nonexistent/path');
    }
}
