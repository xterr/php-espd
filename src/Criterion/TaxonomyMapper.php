<?php

declare(strict_types=1);

namespace Xterr\Espd\Criterion;

final class TaxonomyMapper
{
    private const NS_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    private const NS_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    /**
     * V2 CRITERION.OTHER.EO_DATA codes with semantic v4 equivalents.
     * UUID cross-reference misses these because v4 uses structured IDs (C{n}_OT_*) instead of UUIDs.
     */
    private const EO_DATA_EQUIVALENCES = [
        'CRITERION.OTHER.EO_DATA.SHELTERED_WORKSHOP' => 'shelt-worksh',
        'CRITERION.OTHER.EO_DATA.REGISTERED_IN_OFFICIAL_LIST' => 'registered',
        'CRITERION.OTHER.EO_DATA.TOGETHER_WITH_OTHERS' => 'eo-group',
        'CRITERION.OTHER.EO_DATA.RELIES_ON_OTHER_CAPACITIES' => 'relied',
        'CRITERION.OTHER.EO_DATA.SUBCONTRACTS_WITH_THIRD_PARTIES' => 'subco-ent',
        'CRITERION.OTHER.EO_DATA.REDUCTION_OF_CANDIDATES' => 'staff-red',
    ];

    /**
     * Part + section assignments for v4 "Other" codes (C{n}_OT_* structured IDs).
     * These are not in criterionList.xml. Classification per EU Regulation 2016/7 Annex 2.
     *
     * @see https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0007
     */
    private const OTHER_CODE_PARTS = [
        'shelt-worksh' => ['II', 'A'],
        'registered'   => ['II', 'A'],
        'eo-group'     => ['II', 'A'],
        'sme'          => ['II', 'A'],
        'relied'       => ['II', 'C'],
        'subco-ent'    => ['II', 'D'],
        'staff-red'    => ['V', null],
    ];

    /**
     * Parse a UBL QualificationApplicationRequest taxonomy XML and extract
     * UUID → CriterionTypeCode pairs from every TenderingCriterion.
     *
     * @return array<string, array{code: string, name: string}> UUID → {code, name}
     */
    public function parse(string $xmlPath): array
    {
        if (!file_exists($xmlPath)) {
            throw new \RuntimeException(sprintf('Taxonomy file not found: %s', $xmlPath));
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            if (!$dom->load($xmlPath)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $msg = $errors !== [] ? $errors[0]->message : 'unknown error';
                throw new \RuntimeException(sprintf('Failed to parse taxonomy XML %s: %s', $xmlPath, trim($msg)));
            }
        } finally {
            libxml_use_internal_errors($previous);
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('cac', self::NS_CAC);
        $xpath->registerNamespace('cbc', self::NS_CBC);

        $nodes = $xpath->query('//cac:TenderingCriterion');
        if ($nodes === false) {
            return [];
        }

        $map = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $idList = $xpath->query('cbc:ID', $node);
            $codeList = $xpath->query('cbc:CriterionTypeCode', $node);
            $nameList = $xpath->query('cbc:Name', $node);

            if ($idList === false || $codeList === false || $nameList === false) {
                continue;
            }

            $idNode = $idList->item(0);
            $codeNode = $codeList->item(0);
            $nameNode = $nameList->item(0);

            if (!$idNode instanceof \DOMElement || !$codeNode instanceof \DOMElement) {
                continue;
            }

            $uuid = trim($idNode->textContent);
            $code = trim($codeNode->textContent);
            $name = $nameNode instanceof \DOMElement ? trim($nameNode->textContent) : $code;

            if ($uuid !== '' && $code !== '') {
                $map[$uuid] = ['code' => $code, 'name' => $name];
            }
        }

        return $map;
    }

    /**
     * Cross-reference v2 and v4 taxonomy maps by UUID.
     *
     * @param array<string, array{code: string, name: string}> $v2Map UUID → {code, name}
     * @param array<string, array{code: string, name: string}> $v4Map UUID → {code, name}
     * @return array<string, string> v2 code → v4 code
     */
    public function crossReference(array $v2Map, array $v4Map): array
    {
        $mapping = [];

        foreach ($v2Map as $uuid => $v2) {
            if (isset($v4Map[$uuid])) {
                $mapping[$v2['code']] = $v4Map[$uuid]['code'];
            }
        }

        // Add EO_DATA equivalences that UUID cross-reference misses
        foreach (self::EO_DATA_EQUIVALENCES as $v2Code => $v4Code) {
            if (!isset($mapping[$v2Code])) {
                $mapping[$v2Code] = $v4Code;
            }
        }

        ksort($mapping);

        return $mapping;
    }

    /**
     * Generate a Genericode (.gc) file from extracted criterion codes.
     *
     * @param array<string, array{code: string, name: string}> $codes UUID → {code, name}
     */
    public function generateGenericode(array $codes, string $outputPath): void
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS('http://docs.oasis-open.org/codelist/ns/genericode/1.0/', 'gc:CodeList');
        $dom->appendChild($root);

        $id = $dom->createElement('Identification');
        $root->appendChild($id);

        $shortName = $dom->createElement('ShortName', 'CriteriaTypeCode');
        $id->appendChild($shortName);

        $longName = $dom->createElement('LongName', 'CriteriaTypeCode');
        $id->appendChild($longName);

        $longNameListId = $dom->createElement('LongName', 'CriteriaTypeCode');
        $longNameListId->setAttribute('Identifier', 'listId');
        $id->appendChild($longNameListId);

        $version = $dom->createElement('Version', '2.1.1');
        $id->appendChild($version);

        $agency = $dom->createElement('Agency');
        $id->appendChild($agency);

        $agencyShort = $dom->createElement('ShortName', 'EU-COM-GROW');
        $agency->appendChild($agencyShort);

        $agencyIdentifier = $dom->createElement('Identifier');
        $agencyIdentifier->setAttribute('Identifier', 'EU-COM-GROW');
        $agency->appendChild($agencyIdentifier);

        $columnSet = $dom->createElement('ColumnSet');
        $root->appendChild($columnSet);

        $codeCol = $dom->createElement('Column');
        $codeCol->setAttribute('Id', 'code');
        $codeCol->setAttribute('Use', 'required');
        $columnSet->appendChild($codeCol);

        $codeColShort = $dom->createElement('ShortName', 'Code');
        $codeCol->appendChild($codeColShort);

        $codeColData = $dom->createElement('Data');
        $codeColData->setAttribute('Type', 'normalizedString');
        $codeColData->setAttribute('Lang', 'eng');
        $codeCol->appendChild($codeColData);

        $nameCol = $dom->createElement('Column');
        $nameCol->setAttribute('Id', 'Name');
        $nameCol->setAttribute('Use', 'optional');
        $columnSet->appendChild($nameCol);

        $nameColShort = $dom->createElement('ShortName', 'Name');
        $nameCol->appendChild($nameColShort);

        $nameColData = $dom->createElement('Data');
        $nameColData->setAttribute('Type', 'string');
        $nameColData->setAttribute('Lang', 'eng');
        $nameCol->appendChild($nameColData);

        $simpleCodeList = $dom->createElement('SimpleCodeList');
        $root->appendChild($simpleCodeList);

        foreach ($codes as $entry) {
            $row = $dom->createElement('Row');
            $simpleCodeList->appendChild($row);

            $codeValue = $dom->createElement('Value');
            $codeValue->setAttribute('ColumnRef', 'code');
            $row->appendChild($codeValue);

            $codeSimple = $dom->createElement('SimpleValue', $entry['code']);
            $codeValue->appendChild($codeSimple);

            $nameValue = $dom->createElement('Value');
            $nameValue->setAttribute('ColumnRef', 'Name');
            $row->appendChild($nameValue);

            $nameSimple = $dom->createElement('SimpleValue', htmlspecialchars($entry['name'], ENT_XML1, 'UTF-8'));
            $nameValue->appendChild($nameSimple);
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dom->save($outputPath);
    }

    /**
     * Generate a PHP return-array mapping file.
     *
     * @param array<string, string> $mapping v2 code → v4 code
     */
    public function generateMappingFile(array $mapping, string $outputPath): void
    {
        $lines = ["<?php\n"];
        $lines[] = "declare(strict_types=1);\n";
        $lines[] = '';
        $lines[] = '/**';
        $lines[] = ' * @generated by php-espd criterion mapping generator';
        $lines[] = ' *';
        $lines[] = ' * V2 long-form CriterionTypeCode → V4 short-form equivalent.';
        $lines[] = ' * Derived from UUID cross-reference between ESPD-EDM v2.1.1 and v4.1.0 taxonomies.';
        $lines[] = ' *';
        $lines[] = ' * Do not edit — regenerate with: php php-espd espd:generate --force';
        $lines[] = ' */';
        $lines[] = 'return [';

        foreach ($mapping as $v2Code => $v4Code) {
            $lines[] = sprintf("    '%s' => '%s',", $v2Code, $v4Code);
        }

        $lines[] = '];';
        $lines[] = '';

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, implode("\n", $lines));
    }

    /**
     * Parse criterionList.xml and extract Part + section classification.
     *
     * @return array<string, array{part: string, section: ?string}>
     */
    public function parseCriterionList(string $xmlPath): array
    {
        if (!file_exists($xmlPath)) {
            throw new \RuntimeException(sprintf('Criterion list file not found: %s', $xmlPath));
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            if (!$dom->load($xmlPath)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $msg = $errors !== [] ? $errors[0]->message : 'unknown error';
                throw new \RuntimeException(sprintf('Failed to parse criterion list XML %s: %s', $xmlPath, trim($msg)));
            }
        } finally {
            libxml_use_internal_errors($previous);
        }

        $map = [];

        $exclusions = $dom->getElementsByTagName('exclusion-criterion');
        /** @var \DOMNode $node */
        foreach ($exclusions as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $nameNode = $node->getElementsByTagName('name')->item(0);
            if (!$nameNode instanceof \DOMElement) {
                continue;
            }

            $code = trim($nameNode->textContent);
            if ($code === '') {
                continue;
            }

            $type = $node->getAttribute('type');
            if ($type === 'conviction') {
                $section = 'A';
            } elseif ($type === 'contributions') {
                $section = 'B';
            } else {
                $section = 'C';
            }

            if ($code === 'nati-ground') {
                $section = 'D';
            }

            $map[$code] = ['part' => 'III', 'section' => $section];
        }

        $selections = $dom->getElementsByTagName('selection-criterion');
        /** @var \DOMNode $node */
        foreach ($selections as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $nameNode = $node->getElementsByTagName('name')->item(0);
            if (!$nameNode instanceof \DOMElement) {
                continue;
            }

            $code = trim($nameNode->textContent);
            if ($code === '') {
                continue;
            }

            $map[$code] = ['part' => 'IV', 'section' => null];
        }

        return $map;
    }

    /**
     * Resolve Part IV section from the v2 code prefix.
     *
     * @param array<string, string> $v2ToV4Mapping v2 code => v4 code
     */
    private function resolveSelectionSection(string $v4Code, array $v2ToV4Mapping): ?string
    {
        $reverse = array_flip($v2ToV4Mapping);

        $v2Code = $reverse[$v4Code] ?? null;
        if ($v2Code === null) {
            return null;
        }

        if (str_contains($v2Code, 'SUITABILITY')) {
            return 'A';
        }
        if (str_contains($v2Code, 'ECONOMIC_FINANCIAL_STANDING')) {
            return 'B';
        }
        if (str_contains($v2Code, 'CERTIFICATES')) {
            return 'D';
        }
        if (str_contains($v2Code, 'TECHNICAL_PROFESSIONAL_ABILITY')) {
            return 'C';
        }

        return null;
    }

    /**
     * Build complete code-to-part+section mapping.
     *
     * @param array<string, array{part: string, section: ?string}> $criterionListParts from parseCriterionList
     * @param array<string, array{code: string, name: string}> $v4Map from parse()
     * @param array<string, string> $v2ToV4Mapping from crossReference()
     * @param array<string, array{code: string, name: string}> $v2Map from parse()
     * @return array<string, array{part: string, section: ?string}>
     */
    public function buildPartMapping(array $criterionListParts, array $v4Map, array $v2ToV4Mapping, array $v2Map): array
    {
        // Step 1: Start with criterionListParts (55 codes with Part III/IV)
        $result = $criterionListParts;

        // Step 2: Resolve Part IV sections
        foreach ($result as $code => $entry) {
            if ($entry['part'] === 'IV' && $entry['section'] === null) {
                $section = $this->resolveSelectionSection($code, $v2ToV4Mapping);
                if ($section !== null) {
                    $result[$code]['section'] = $section;
                }
            }
        }

        // Step 3: UUID alias propagation
        // Some criterionList codes (e.g. 'misinterpr', 'autorisation') don't exist in the
        // v4 taxonomy. Their v4 taxonomy equivalents (e.g. 'misrepresent', 'authorisation')
        // need the same part+section.
        $v4CodesSet = [];
        foreach ($v4Map as $entry) {
            $v4CodesSet[$entry['code']] = true;
        }

        foreach ($v4Map as $uuid => $entry) {
            $v4Code = $entry['code'];
            if (isset($result[$v4Code])) {
                continue;
            }

            // Only process codes that have a v2 counterpart (aliases, not OTHER codes)
            if (!isset($v2Map[$uuid])) {
                continue;
            }

            $v2Code = $v2Map[$uuid]['code'];
            $derivedPart = null;
            if (str_starts_with($v2Code, 'CRITERION.EXCLUSION.')) {
                $derivedPart = 'III';
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.')) {
                $derivedPart = 'IV';
            }

            if ($derivedPart === null) {
                continue;
            }

            // Find the orphan criterionListParts code (in result but not in v4Map)
            foreach ($result as $candidateCode => $partData) {
                if (isset($v4CodesSet[$candidateCode])) {
                    continue;
                }

                $candidatePart = $partData['part'] ?? null;
                if ($candidatePart === $derivedPart) {
                    $result[$v4Code] = $partData;

                    // Resolve section for alias if still missing
                    if ($result[$v4Code]['section'] === null && $candidatePart === 'IV') {
                        $section = $this->resolveSelectionSection($v4Code, $v2ToV4Mapping);
                        if ($section !== null) {
                            $result[$v4Code]['section'] = $section;
                            $result[$candidateCode]['section'] = $section;
                        }
                    }

                    break;
                }
            }
        }

        // Step 4: Apply OTHER_CODE_PARTS for remaining v4 codes not yet mapped
        foreach (self::OTHER_CODE_PARTS as $code => [$part, $section]) {
            if (!isset($result[$code])) {
                $result[$code] = ['part' => $part, 'section' => $section];
            }
        }

        // Step 5: V2 codes
        foreach ($v2Map as $v2Entry) {
            $v2Code = $v2Entry['code'];
            if (isset($result[$v2Code])) {
                continue;
            }

            $v4Equivalent = $v2ToV4Mapping[$v2Code] ?? null;
            if ($v4Equivalent !== null && isset($result[$v4Equivalent])) {
                $result[$v2Code] = $result[$v4Equivalent];
                continue;
            }

            if (str_starts_with($v2Code, 'CRITERION.EXCLUSION.CONVICTIONS')) {
                $result[$v2Code] = ['part' => 'III', 'section' => 'A'];
            } elseif (str_starts_with($v2Code, 'CRITERION.EXCLUSION.CONTRIBUTIONS')) {
                $result[$v2Code] = ['part' => 'III', 'section' => 'B'];
            } elseif (str_starts_with($v2Code, 'CRITERION.EXCLUSION.NATIONAL')) {
                $result[$v2Code] = ['part' => 'III', 'section' => 'D'];
            } elseif (str_starts_with($v2Code, 'CRITERION.EXCLUSION.')) {
                $result[$v2Code] = ['part' => 'III', 'section' => 'C'];
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.ALL')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => "\u{03B1}"];
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.SUITABILITY')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => 'A'];
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.ECONOMIC_FINANCIAL_STANDING')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => 'B'];
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.TECHNICAL_PROFESSIONAL_ABILITY.CERTIFICATES')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => 'D'];
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.TECHNICAL_PROFESSIONAL_ABILITY')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => 'C'];
            } elseif (str_starts_with($v2Code, 'CRITERION.SELECTION.')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => null];
            } elseif (str_starts_with($v2Code, 'CRITERION.OTHER.EO_DATA.')) {
                $result[$v2Code] = ['part' => 'II', 'section' => 'A'];
            } elseif (str_starts_with($v2Code, 'CRITERION.DEFENCE.') || str_starts_with($v2Code, 'CRITERION.UTILITIES.')) {
                $result[$v2Code] = ['part' => 'IV', 'section' => null];
            }
        }

        ksort($result);

        /** @var array<string, array{part: string, section: ?string}> */
        return $result;
    }

    /**
     * Generate a PHP return-array mapping file with Part + section data.
     *
     * @param array<string, array{part: string, section: ?string}> $mapping
     */
    public function generatePartMappingFile(array $mapping, string $outputPath): void
    {
        $lines = ["<?php\n"];
        $lines[] = "declare(strict_types=1);\n";
        $lines[] = '';
        $lines[] = '/**';
        $lines[] = ' * @generated by php-espd criterion mapping generator';
        $lines[] = ' *';
        $lines[] = ' * CriterionTypeCode → ESPD Part + section assignment.';
        $lines[] = ' * Derived from criterionList.xml classification and EU Regulation 2016/7 Annex 2.';
        $lines[] = ' *';
        $lines[] = ' * Do not edit — regenerate with: php php-espd espd:generate --force';
        $lines[] = ' */';
        $lines[] = 'return [';

        foreach ($mapping as $code => $entry) {
            $sectionStr = $entry['section'] !== null ? sprintf("'%s'", $entry['section']) : 'null';
            $lines[] = sprintf("    '%s' => ['part' => '%s', 'section' => %s],", $code, $entry['part'], $sectionStr);
        }

        $lines[] = '];';
        $lines[] = '';

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, implode("\n", $lines));
    }
}
