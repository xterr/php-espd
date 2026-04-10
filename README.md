# PHP ESPD

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Packagist Version](https://img.shields.io/packagist/v/xterr/php-espd)](https://packagist.org/packages/xterr/php-espd)

Typed PHP classes for ESPD (European Single Procurement Document) based on ESPD-EDM v4.1.0 / UBL 2.3.

Serialize and deserialize `QualificationApplicationRequest` and `QualificationApplicationResponse` documents without DOM code. Codelist values are PHP enums with full type safety, including union-typed properties for elements that span multiple codelists.

## Features

- **325 Generated Classes** — 2 document roots, 290 aggregate types, 8 leaf value types, 16 codelist enums, 5 XSD enums, 2 registries
- **Codelist Enums** — `CriterionCode`, `CriterionElement`, `ResponseData`, `PropertyGroup`, `CountryCode`, `LanguageCode`, and 10 more
- **Union Enum Types** — `ExpectedCode` resolves to `BooleanGUIControl|FinancialRatio|OccupationCode` based on `listID` at runtime
- **Criterion Taxonomy** — `ESPD-criterion.xml` ships as a resource, deserializable into the generated classes
- **Full Round-Trip** — serialize → deserialize produces identical object graphs

## Installation

```bash
composer require xterr/php-espd
```

**Requirements:**
- PHP 8.2 or higher
- `ext-dom`
- `ext-libxml`

## Quick Start

### Deserialize an ESPD Request

```php
use Xterr\UBL\Xml\XmlDeserializer;
use Xterr\Espd\Doc\QualificationApplicationRequest;

$xml = file_get_contents('espd-request.xml');

$deserializer = new XmlDeserializer();
$request = $deserializer->deserialize($xml, QualificationApplicationRequest::class);

echo $request->getId()->getValue();                    // "ESPDREQ-..."
echo $request->getProfileExecutionID()->getValue();    // "ESPD-EDMv4.1.0"
echo count($request->getTenderingCriterions());        // 62
```

### Work with typed enums

```php
use Xterr\Espd\Codelist\CriterionCode;
use Xterr\Espd\Codelist\CriterionElement;
use Xterr\Espd\Codelist\ResponseData;

$criterion = $request->getTenderingCriterions()[0];

// CriterionCode enum — not a raw string
$typeCode = $criterion->getCriterionTypeCode();        // CriterionCode::CRIME_ORG
echo $typeCode->value;                                  // "crime-org"

$group = $criterion->getTenderingCriterionPropertyGroups()[0];
$prop = $group->getTenderingCriterionProperties()[0];

$prop->getTypeCode();                                   // CriterionElement::QUESTION
$prop->getValueDataTypeCode();                          // ResponseData::INDICATOR
```

### Union enum — ExpectedCode

`ExpectedCode` can hold values from three different codelists depending on the criterion context. The `listID` XML attribute acts as the runtime discriminator:

```php
// The property type is: BooleanGUIControl|FinancialRatio|OccupationCode|null
$expectedCode = $prop->getExpectedCode();

if ($expectedCode instanceof \Xterr\Espd\Codelist\BooleanGUIControl) {
    // e.g. BooleanGUIControl::CHECKBOX_TRUE
}
```

### Serialize to XML

```php
use Xterr\UBL\Xml\XmlSerializer;

$serializer = new XmlSerializer();
$xml = $serializer->serialize($request);
```

### Load the criterion taxonomy

The full ESPD criterion tree ships as a resource:

```php
$taxonomyXml = file_get_contents('vendor/xterr/php-espd/resources/criterion/ESPD-criterion.xml');
$taxonomy = $deserializer->deserialize($taxonomyXml, QualificationApplicationRequest::class);

// 62 criteria with their property groups, questions, and response types
foreach ($taxonomy->getTenderingCriterions() as $criterion) {
    echo $criterion->getCriterionTypeCode()->value . "\n";
}
```

## Generated Structure

```
src/
├── Cbc/           8 leaf value types (Amount, Code, Identifier, Text, ...)
├── Cac/         290 aggregate types (TenderingCriterion, Party, Address, ...)
├── Doc/           2 document roots
│   ├── QualificationApplicationRequest.php
│   └── QualificationApplicationResponse.php
├── Codelist/     16 codelist enums from Genericode files
├── Enum/          5 XSD-defined enums
└── Xml/           2 registries (DocumentRegistry, TypeMap)
```

## Codelist Enums

| Enum | listID | Values |
|------|--------|--------|
| `CriterionCode` | `criterion` | `crime-org`, `corruption`, `fraud`, ... |
| `CriterionElement` | `criterion-element-type` | `QUESTION`, `REQUIREMENT`, `CRITERION`, ... |
| `ResponseData` | `response-data-type` | `INDICATOR`, `AMOUNT`, `DATE`, `DESCRIPTION`, ... |
| `PropertyGroup` | `property-group-type` | `ON*`, `ONTRUE`, `ONFALSE` |
| `CountryCode` | `country` | ISO 3166-1 codes |
| `LanguageCode` | `language` | ISO 639 codes |
| `CurrencyCode` | `currency` | ISO 4217 codes |
| `OccupationCode` | `occupation` | ESCO occupation codes |
| `EconomicOperatorSize` | `economic-operator-size` | SME, micro, large, ... |
| `EoRole` | `eo-role-type` | Sole tenderer, lead entity, ... |
| `BooleanGUIControl` | `boolean-gui-control-type` | `CHECKBOX_TRUE`, `RADIO_BUTTON_TRUE`, ... |
| `FinancialRatio` | `financial-ratio-type` | Ratio types |
| `ProfileExecutionID` | `profile-execution-id` | `ESPD-EDMv4.1.0`, ... |
| `AccessRight` | `access-right` | Access right codes |
| `Docrefcontent` | `docrefcontent-type` | Document reference content types |
| `EOID` | `eoid-type` | Economic operator ID types |

## Codelist Bindings

These properties are typed with codelist enums instead of generic `Code`:

| Class | Property | Enum |
|-------|----------|------|
| `TenderingCriterion` | `criterionTypeCode` | `CriterionCode` |
| `TenderingCriterionProperty` | `typeCode` | `CriterionElement` |
| `TenderingCriterionProperty` | `valueDataTypeCode` | `ResponseData` |
| `TenderingCriterionProperty` | `expectedCode` | `BooleanGUIControl\|FinancialRatio\|OccupationCode` |
| `TenderingCriterionPropertyGroup` | `propertyGroupTypeCode` | `PropertyGroup` |
| `Country` | `identificationCode` | `CountryCode` |
| `Language` | `localeCode` | `LanguageCode` |

## Schema Source

Generated from [OP-TED/ESPD-EDM](https://github.com/OP-TED/ESPD-EDM) v4.1.0, which uses OASIS UBL 2.3 OS schemas. The XSD schemas and Genericode codelist files are in `resources/` (not committed — dev-only).

## Regenerating

```bash
composer require --dev xterr/php-ubl-generator

php vendor/bin/php-ubl-generator --config=ubl-generator.yaml --force
```

## Development

```bash
composer install

# Regenerate classes
php vendor/bin/php-ubl-generator --config=ubl-generator.yaml --force
```

## License

[MIT](LICENSE) - Copyright (c) 2026 Ceana Razvan
