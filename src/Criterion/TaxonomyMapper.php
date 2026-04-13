<?php

declare(strict_types=1);

namespace Xterr\Espd\Criterion;

final class TaxonomyMapper
{
    private const NS_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    private const NS_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

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
}
