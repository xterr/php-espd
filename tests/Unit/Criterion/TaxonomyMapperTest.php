<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Criterion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Criterion\TaxonomyMapper;

final class TaxonomyMapperTest extends TestCase
{
    private TaxonomyMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new TaxonomyMapper();
    }

    private static function resourcePath(string $relative): string
    {
        return dirname(__DIR__, 3) . '/resources/' . $relative;
    }

    #[Test]
    public function parseV4TaxonomyReturns62Criteria(): void
    {
        $result = $this->mapper->parse(self::resourcePath('criterion/v4.1.0/ESPD-criterion.xml'));

        self::assertCount(62, $result);

        foreach ($result as $uuid => $entry) {
            self::assertNotEmpty($uuid);
            self::assertArrayHasKey('code', $entry);
            self::assertArrayHasKey('name', $entry);
            self::assertNotEmpty($entry['code']);
        }
    }

    #[Test]
    public function parseV2TaxonomyReturns66Criteria(): void
    {
        $result = $this->mapper->parse(self::resourcePath('criterion/v2.1.1/ESPD-CriteriaTaxonomy-Basic.xml'));

        self::assertCount(66, $result);

        foreach ($result as $uuid => $entry) {
            self::assertNotEmpty($uuid);
            self::assertArrayHasKey('code', $entry);
            self::assertArrayHasKey('name', $entry);
            self::assertNotEmpty($entry['code']);
        }
    }

    #[Test]
    public function crossReferenceFinds55Mappings(): void
    {
        $v2Map = $this->mapper->parse(self::resourcePath('criterion/v2.1.1/ESPD-CriteriaTaxonomy-Basic.xml'));
        $v4Map = $this->mapper->parse(self::resourcePath('criterion/v4.1.0/ESPD-criterion.xml'));

        $crossRef = $this->mapper->crossReference($v2Map, $v4Map);

        self::assertCount(55, $crossRef);
    }

    #[Test]
    public function crossReferenceValuesAreV4Codes(): void
    {
        $v2Map = $this->mapper->parse(self::resourcePath('criterion/v2.1.1/ESPD-CriteriaTaxonomy-Basic.xml'));
        $v4Map = $this->mapper->parse(self::resourcePath('criterion/v4.1.0/ESPD-criterion.xml'));

        $crossRef = $this->mapper->crossReference($v2Map, $v4Map);

        foreach ($crossRef as $v4Code) {
            self::assertStringNotContainsString(
                'CRITERION.',
                $v4Code,
                sprintf('V4 code "%s" should not start with CRITERION.', $v4Code),
            );
        }
    }

    #[Test]
    public function crossReferenceKeysAreV2Codes(): void
    {
        $v2Map = $this->mapper->parse(self::resourcePath('criterion/v2.1.1/ESPD-CriteriaTaxonomy-Basic.xml'));
        $v4Map = $this->mapper->parse(self::resourcePath('criterion/v4.1.0/ESPD-criterion.xml'));

        $crossRef = $this->mapper->crossReference($v2Map, $v4Map);

        foreach ($crossRef as $v2Code => $v4Code) {
            self::assertStringStartsWith(
                'CRITERION.',
                $v2Code,
                sprintf('V2 code "%s" should start with CRITERION.', $v2Code),
            );
        }
    }

    #[Test]
    public function parseMissingFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Taxonomy file not found');

        $this->mapper->parse('/nonexistent/path/taxonomy.xml');
    }
}
