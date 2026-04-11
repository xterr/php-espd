# PHP ESPD

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Packagist Version](https://img.shields.io/packagist/v/xterr/php-espd)](https://packagist.org/packages/xterr/php-espd)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)

Typed PHP classes for ESPD (European Single Procurement Document) based on ESPD-EDM v4.1.0 / UBL 2.3.

Serialize and deserialize `QualificationApplicationRequest` and `QualificationApplicationResponse` documents without DOM code. Validate documents against the official OP-TED Schematron business rules with 1:1 compliance. Codelist values are PHP enums with full type safety, including union-typed properties for elements that span multiple codelists.

## Features

- **325 Generated Classes** — 2 document roots, 290 aggregate types, 8 leaf value types, 16 codelist enums, 5 XSD enums, 2 registries
- **Business Rule Validation** — Official OP-TED ESPD-EDM v4.1.0 Schematron rules via SaxonC-HE XSLT processor, 1:1 compliant with the EU reference validator
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

**Optional (for validation):**
- `ext-saxonc` — [SaxonC-HE 12.x](https://www.saxonica.com/download/c.html) (free, MPL-2.0 license)

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

### Serialize to XML

```php
use Xterr\UBL\Xml\XmlSerializer;

$serializer = new XmlSerializer();
$xml = $serializer->serialize($request);
```

### Validate against EU business rules

Requires the `ext-saxonc` PHP extension ([installation guide](#installing-saxonc-he)).

```php
use Xterr\Espd\Validation\EspdValidator;

$validator = EspdValidator::create();
$result = $validator->validate($request);

if (!$result->isValid()) {
    foreach ($result->getFailures() as $violation) {
        echo $violation . PHP_EOL;
        // [fatal] BR-OTH-04-01: The element '/cbc:UBLVersionID' is mandatory. (at /*)
    }
}

// Filter by severity
$result->getFatals();    // Severity::Fatal only
$result->getErrors();    // Severity::Error only
$result->getWarnings();  // Severity::Warning only
$result->getFailures();  // Fatal + Error combined
```

You can also validate raw XML strings directly:

```php
use Xterr\Espd\Validation\DocumentType;

$xml = file_get_contents('espd-response.xml');
$result = $validator->validateXml($xml, DocumentType::Response);
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

## Validation

The validation subsystem runs the official [OP-TED/ESPD-EDM v4.1.0](https://github.com/OP-TED/ESPD-EDM/tree/v4.1.0/validation) Schematron business rules via SaxonC-HE. This guarantees 1:1 compliance with the EU reference validator — identical rule IDs, severity levels, and error messages.

### What gets validated

| Category | Rule IDs | Severity |
|----------|----------|----------|
| Cardinality | `BR-OTH-04-*` | Fatal |
| Codelist values | auto-generated | Fatal |
| Criterion structure | `BR-TC-02` through `BR-TC-21` | Fatal/Error |
| Exclusion criteria | `BR-REQ-30` | Fatal |
| Selection criteria | `BR-REQ-40` | Warning |
| Procurer data | `BR-REQ-20-*` | Warning/Error |
| Economic operator | `BR-RESP-10-*`, `BR-RESP-20-*` | Error |
| Other | `BR-SC-10`, `BR-OTH-*` | Mixed |

### Validation result API

```php
$result->isValid();      // true if no Fatal or Error violations
$result->hasWarnings();  // true if any Warning violations
$result->getFatals();    // list<Violation> — Fatal only
$result->getErrors();    // list<Violation> — Error only
$result->getFailures();  // list<Violation> — Fatal + Error
$result->getWarnings();  // list<Violation> — Warning only
$result->violations;     // list<Violation> — all violations
count($result);          // total violation count
```

Each `Violation` exposes:

| Property | Description |
|----------|-------------|
| `ruleId` | Schematron rule ID (e.g. `BR-OTH-04-01`) |
| `severity` | `Severity::Fatal`, `Severity::Error`, or `Severity::Warning` |
| `message` | Human-readable error message |
| `location` | XPath to the failing XML node |
| `test` | XPath test expression from the rule |
| `pattern` | Pattern group ID |

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
├── Validation/    8 hand-written validation classes
│   ├── EspdValidator.php
│   ├── ValidationResult.php
│   ├── Violation.php
│   ├── Severity.php
│   ├── DocumentType.php
│   ├── SvrlParser.php
│   └── Exception/
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

## Installing SaxonC-HE

The `ext-saxonc` PHP extension is required only for validation. Serialization and deserialization work without it.

### Docker (easiest)

```dockerfile
FROM php:8.2-fpm

COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN IPE_SAXON_EDITION=HE install-php-extensions saxon
```

### macOS

```bash
curl -LO https://downloads.saxonica.com/SaxonC/HE/12/SaxonCHE-macos-arm64-12-9-0.zip
unzip SaxonCHE-macos-arm64-12-9-0.zip
xattr -dr com.apple.quarantine SaxonCHE-macos-arm64-12-9-0

sudo cp -P SaxonCHE-macos-arm64-12-9-0/SaxonCHE/lib/libsaxonc-*.dylib /usr/local/lib/

cd SaxonCHE-macos-arm64-12-9-0/php/src
phpize && ./configure --with-saxon=../../SaxonCHE && make -j$(nproc) && sudo make install
sudo install_name_tool -add_rpath /usr/local/lib $(php -r "echo ini_get('extension_dir');")/saxon.so

echo "extension=saxon.so" > $(php --ini | grep "Scan for" | awk -F: '{print $2}' | xargs)/50-saxon.ini
```

For Intel Macs, replace `arm64` with `x86_64` in the download URL.

### Linux (Ubuntu/Debian)

```bash
sudo apt-get install -y php8.2-dev build-essential unzip wget libstdc++6

wget https://downloads.saxonica.com/SaxonC/HE/12/SaxonCHE-linux-x86_64-12-9-0.zip
unzip SaxonCHE-linux-x86_64-12-9-0.zip

sudo cp SaxonCHE-linux-x86_64-12-9-0/SaxonCHE/lib/libsaxon*.so /usr/local/lib/ && sudo ldconfig

cd SaxonCHE-linux-x86_64-12-9-0/php/src
phpize && ./configure --with-saxon=../../SaxonCHE && make -j$(nproc) && sudo make install
echo "extension=saxon.so" | sudo tee /etc/php/8.2/mods-available/saxon.ini && sudo phpenmod saxon
```

### Verify

```bash
php -r "echo (new Saxon\SaxonProcessor())->version() . PHP_EOL;"
# SaxonC-HE 12.9 from Saxonica
```

## Schema Source

Generated from [OP-TED/ESPD-EDM](https://github.com/OP-TED/ESPD-EDM) v4.1.0, which uses OASIS UBL 2.3 OS schemas. The XSD schemas and Genericode codelist files are in `resources/` (not committed — dev-only). The pre-compiled Schematron XSL validation rules are bundled in `resources/validation/`.

## Regenerating

```bash
composer require --dev xterr/php-ubl-generator

php vendor/bin/php-ubl-generator --config=ubl-generator.yaml --force
```

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyze

# Regenerate classes
php vendor/bin/php-ubl-generator --config=ubl-generator.yaml --force
```

## License

[MIT](LICENSE) - Copyright (c) 2026 Ceana Razvan
