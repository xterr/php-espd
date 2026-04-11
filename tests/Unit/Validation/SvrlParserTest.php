<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\Exception\ValidationException;
use Xterr\Espd\Validation\Severity;
use Xterr\Espd\Validation\SvrlParser;

final class SvrlParserTest extends TestCase
{
    private SvrlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SvrlParser();
    }

    #[Test]
    public function parseSampleSvrlFixture(): void
    {
        $svrl = file_get_contents(__DIR__ . '/../../Fixtures/sample-svrl.xml');
        self::assertIsString($svrl);

        $violations = $this->parser->parse($svrl);

        self::assertCount(3, $violations);

        self::assertSame('BR-OTH-04-01', $violations[0]->ruleId);
        self::assertSame(Severity::Fatal, $violations[0]->severity);
        self::assertSame("The element '/cbc:UBLVersionID' is mandatory.", $violations[0]->message);
        self::assertSame("/*[local-name()='QualificationApplicationRequest']", $violations[0]->location);
        self::assertSame('(cbc:UBLVersionID)', $violations[0]->test);
        self::assertSame('BR-OTH-CARD', $violations[0]->pattern);

        self::assertSame('BR-OTH-04-03', $violations[1]->ruleId);
        self::assertSame(Severity::Error, $violations[1]->severity);
        self::assertSame("The element '/cbc:ID' is mandatory.", $violations[1]->message);
        self::assertSame('BR-OTH-CARD', $violations[1]->pattern);

        self::assertSame('BR-REQ-30', $violations[2]->ruleId);
        self::assertSame(Severity::Warning, $violations[2]->severity);
        self::assertSame('Exclusion criteria are required.', $violations[2]->message);
        self::assertSame('BR-REQ-CR', $violations[2]->pattern);
    }

    #[Test]
    public function emptyStringReturnsEmptyArray(): void
    {
        $violations = $this->parser->parse('');

        self::assertSame([], $violations);
    }

    #[Test]
    public function malformedXmlThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to parse SVRL output');

        $this->parser->parse('<not valid xml');
    }

    #[Test]
    public function svrlWithNoFailedAssertReturnsEmptyArray(): void
    {
        $svrl = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svrl:schematron-output xmlns:svrl="http://purl.oclc.org/dsdl/svrl">'
            . '<svrl:active-pattern id="test" name="Test"/>'
            . '<svrl:fired-rule context="/*"/>'
            . '</svrl:schematron-output>';

        $violations = $this->parser->parse($svrl);

        self::assertSame([], $violations);
    }

    #[Test]
    public function missingAttributesDefaultToEmptyString(): void
    {
        $svrl = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svrl:schematron-output xmlns:svrl="http://purl.oclc.org/dsdl/svrl">'
            . '<svrl:failed-assert><svrl:text>msg</svrl:text></svrl:failed-assert>'
            . '</svrl:schematron-output>';

        $violations = $this->parser->parse($svrl);

        self::assertCount(1, $violations);
        self::assertSame('', $violations[0]->ruleId);
        self::assertSame('', $violations[0]->location);
        self::assertSame('', $violations[0]->test);
        self::assertSame('', $violations[0]->pattern);
    }

    #[Test]
    public function severityFallsBackToErrorForUnknownFlag(): void
    {
        $svrl = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svrl:schematron-output xmlns:svrl="http://purl.oclc.org/dsdl/svrl">'
            . '<svrl:failed-assert flag="information" id="TEST-01">'
            . '<svrl:text>test</svrl:text>'
            . '</svrl:failed-assert>'
            . '</svrl:schematron-output>';

        $violations = $this->parser->parse($svrl);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }
}
