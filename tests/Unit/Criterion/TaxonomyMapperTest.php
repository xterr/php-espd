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
    public function crossReferenceFinds61Mappings(): void
    {
        $v2Map = $this->mapper->parse(self::resourcePath('criterion/v2.1.1/ESPD-CriteriaTaxonomy-Basic.xml'));
        $v4Map = $this->mapper->parse(self::resourcePath('criterion/v4.1.0/ESPD-criterion.xml'));

        $crossRef = $this->mapper->crossReference($v2Map, $v4Map);

        self::assertCount(61, $crossRef);

        self::assertSame('shelt-worksh', $crossRef['CRITERION.OTHER.EO_DATA.SHELTERED_WORKSHOP']);
        self::assertSame('registered', $crossRef['CRITERION.OTHER.EO_DATA.REGISTERED_IN_OFFICIAL_LIST']);
        self::assertSame('eo-group', $crossRef['CRITERION.OTHER.EO_DATA.TOGETHER_WITH_OTHERS']);
        self::assertSame('relied', $crossRef['CRITERION.OTHER.EO_DATA.RELIES_ON_OTHER_CAPACITIES']);
        self::assertSame('subco-ent', $crossRef['CRITERION.OTHER.EO_DATA.SUBCONTRACTS_WITH_THIRD_PARTIES']);
        self::assertSame('staff-red', $crossRef['CRITERION.OTHER.EO_DATA.REDUCTION_OF_CANDIDATES']);
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
    public function parseCriterionListReturns55Entries(): void
    {
        $result = $this->mapper->parseCriterionList(
            self::resourcePath('validation/v4.1.0/ESPDRequest/xsl/criterionList.xml'),
        );

        self::assertCount(55, $result);

        self::assertSame('III', $result['crime-org']['part']);
        self::assertSame('A', $result['crime-org']['section']);

        self::assertSame('III', $result['tax-pay']['part']);
        self::assertSame('B', $result['tax-pay']['section']);

        self::assertSame('III', $result['bankruptcy']['part']);
        self::assertSame('C', $result['bankruptcy']['section']);

        self::assertSame('III', $result['nati-ground']['part']);
        self::assertSame('D', $result['nati-ground']['section']);

        self::assertArrayHasKey('service-perform', $result);

        self::assertSame('IV', $result['prof-regist']['part']);
    }

    #[Test]
    public function parseMissingFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Taxonomy file not found');

        $this->mapper->parse('/nonexistent/path/taxonomy.xml');
    }
}
