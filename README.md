# PHP ESPD

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Packagist Version](https://img.shields.io/packagist/v/xterr/php-espd)](https://packagist.org/packages/xterr/php-espd)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)

Typed PHP classes for ESPD (European Single Procurement Document) based on ESPD-EDM v2.1.1–v4.1.0 / UBL 2.3.

Serialize and deserialize `QualificationApplicationRequest` and `QualificationApplicationResponse` documents without DOM code, with multi-version support (v2.1.1 through v4.1.0). Validate documents against the official OP-TED Schematron business rules with 1:1 compliance. Codelist values are PHP enums with full type safety, including union-typed properties for elements that span multiple codelists.

## Features

- **325 Generated Classes** — 2 document roots, 290 aggregate types, 8 leaf value types, 16 codelist enums, 5 XSD enums, 2 registries
- **Business Rule Validation** — Official OP-TED ESPD-EDM Schematron rules for v2.1.1, v3.3.0, v4.0.0, and v4.1.0 via SaxonC-HE XSLT processor, 1:1 compliant with the EU reference validator
- **Codelist Enums** — `CriterionCode`, `CriterionElement`, `ResponseData`, `PropertyGroup`, `CountryCode`, `LanguageCode`, and 10 more
- **Union Enum Types** — `ExpectedCode` resolves to `BooleanGUIControl|FinancialRatio|OccupationCode` based on `listID` at runtime
- **Multi-Version Support** — Deserialize and validate ESPD documents from v2.1.1, v3.3.0, v4.0.0, and v4.1.0. V2 long-form criterion codes automatically merge into the `CriterionCode` enum with `V2_` prefix and bidirectional v2↔v4 mapping
- **ESPD Part & Section Classification** — `CriterionCode::getPart()` returns the `EspdPart` enum (I–VI per [EU Regulation 2016/7](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0007)), `getSection()` returns the sub-section letter (A–D, α) per [Directive 2014/24/EU](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32014L0024) Articles 57–62
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

### Multi-version deserialization

The library supports ESPD documents from all major versions. V2 long-form criterion codes are automatically resolved:

```php
// V2 document — long-form codes resolve to V2_ prefixed enum cases
$v2Request = $deserializer->deserialize($v2Xml, QualificationApplicationRequest::class);
$code = $v2Request->getTenderingCriterions()[0]->getCriterionTypeCode();
// CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION

$code->isLegacy();        // true — it's a v2 long-form code
$code->toV4Equivalent();  // CriterionCode::CORRUPTION — the v4 equivalent

// V3/V4 documents — short-form codes work as before
$v4Request = $deserializer->deserialize($v4Xml, QualificationApplicationRequest::class);
$code = $v4Request->getTenderingCriterions()[0]->getCriterionTypeCode();
// CriterionCode::CRIME_ORG

$code->isLegacy();        // false
$code->toV4Equivalent();  // CriterionCode::CRIME_ORG (returns self)
```

### Criterion Part & Section classification

Every criterion code knows its position in the ESPD form hierarchy defined by [Commission Implementing Regulation (EU) 2016/7](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0007):

```php
use Xterr\Espd\Codelist\CriterionCode;
use Xterr\Espd\Codelist\EspdPart;

$code = CriterionCode::CORRUPTION;

$code->getPart();      // EspdPart::III
$code->getPart()->label(); // "Exclusion grounds"
$code->getSection();   // "A" — Criminal convictions (Directive 2014/24/EU Art. 57(1))

// Works for all codes — v4 short-form and v2 legacy
CriterionCode::PROF_REGIST->getPart();    // EspdPart::IV (Selection criteria)
CriterionCode::PROF_REGIST->getSection(); // "A" (Suitability)

CriterionCode::SME->getPart();            // EspdPart::II (Economic operator info)
CriterionCode::SME->getSection();         // "A"

CriterionCode::STAFF_RED->getPart();      // EspdPart::V (Reduction of candidates)

// V2 codes inherit Part/Section from their v4 equivalent
CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION->getPart();    // EspdPart::III
CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION->getSection(); // "A"
```

**ESPD Form Parts** (per EU Regulation 2016/7, Annex 2):

| Part | Title | Sections |
|------|-------|----------|
| I | Information concerning the procurement procedure | — |
| II | Information concerning the economic operator | A, B, C, D |
| III | Exclusion grounds | A (Criminal convictions), B (Taxes/social security), C (Insolvency/misconduct), D (National grounds) |
| IV | Selection criteria | α (Global indication), A (Suitability), B (Economic standing), C (Technical ability), D (Quality/environment certs) |
| V | Reduction of the number of qualified candidates | — |
| VI | Concluding statements | — |

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
$taxonomyXml = file_get_contents('vendor/xterr/php-espd/resources/criterion/v4.1.0/ESPD-criterion.xml');
$taxonomy = $deserializer->deserialize($taxonomyXml, QualificationApplicationRequest::class);

// 62 criteria with their property groups, questions, and response types
foreach ($taxonomy->getTenderingCriterions() as $criterion) {
    echo $criterion->getCriterionTypeCode()->value . "\n";
}
```

## Validation

The validation subsystem runs the official [OP-TED/ESPD-EDM](https://github.com/OP-TED/ESPD-EDM) Schematron business rules via SaxonC-HE. This guarantees 1:1 compliance with the EU reference validator — identical rule IDs, severity levels, and error messages.

### Multi-version validation

The validator supports **4 ESPD-EDM version families** and automatically detects which rule set to apply based on the document's `ProfileExecutionID`:

| Version Family | Rule Set | ProfileExecutionID values |
|----------------|----------|---------------------------|
| `VersionFamily::V2` | v2.1.1 | `ESPD-EDMv2.0.0-REGULATED`, `ESPD-EDMv2.0.0-SELFCONTAINED`, `ESPD-EDMv2.1.0-REGULATED`, `ESPD-EDMv2.1.0-SELFCONTAINED`, `ESPD-EDMv2.1.1-BASIC`, `ESPD-EDMv2.1.1-EXTENDED` |
| `VersionFamily::V3` | v3.3.0 | `ESPD-EDMv3.0.0`, `ESPD-EDMv3.0.1`, `ESPD-EDMv3.1.0`, `ESPD-EDMv3.2.0`, `ESPD-EDMv3.3.0` |
| `VersionFamily::V4_0` | v4.0.0 | `ESPD-EDMv4.0.0` |
| `VersionFamily::V4_1` | v4.1.0 | `ESPD-EDMv4.1.0` |

**Auto-detection** (recommended) — the validator reads `ProfileExecutionID` from the XML and selects the matching rule set:

```php
use Xterr\Espd\Validation\EspdValidator;

$validator = EspdValidator::create();

// Version is auto-detected from the document's ProfileExecutionID
$result = $validator->validate($request);
$result = $validator->validateXml($xml, DocumentType::Request);
```

**Explicit version** — useful when the document lacks a `ProfileExecutionID` or you want to force a specific rule set:

```php
use Xterr\Espd\Validation\VersionFamily;

$result = $validator->validate($request, VersionFamily::V2);
$result = $validator->validateXml($xml, DocumentType::Response, VersionFamily::V3);
```

If auto-detection fails (missing or unrecognized `ProfileExecutionID`), a `ValidationException` is thrown prompting you to specify the version explicitly.

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
| `CriterionCode` | `criterion` | `crime-org`, `corruption`, `fraud`, ... (+ `getPart()`, `getSection()`) |
| `EspdPart` | — | `I`, `II`, `III`, `IV`, `V`, `VI` (EU Regulation 2016/7 form Parts) |
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

Generated from [OP-TED/ESPD-EDM](https://github.com/OP-TED/ESPD-EDM) v4.1.0, which uses OASIS UBL 2.3 OS schemas. The XSD schemas and Genericode codelist files are in `resources/` (not committed — dev-only). The pre-compiled Schematron XSL validation rules are bundled in `resources/validation/` for all 4 version families (v2.1.1, v3.3.0, v4.0.0, v4.1.0). V2.1.1 codelist GC files are in `resources/cl/gc/`.

## Regenerating

The `php-espd` CLI tool orchestrates the full code generation pipeline:

```bash
php php-espd espd:generate --force
```

### What it does

1. **Parses criterion taxonomies** — reads ESPD-EDM v2.1.1 and v4.1.0 taxonomy XML files
2. **Cross-references UUIDs** — matches v2 long-form codes to v4 short-form codes by criterion UUID (+ 6 manual EO_DATA equivalences for codes where v4 uses structured IDs instead of UUIDs)
3. **Generates v2→v4 mapping file** — writes `resources/criterion/v2-to-v4-mapping.php` (v2 → v4 lookup)
3b. **Generates code-to-part mapping** — parses `criterionList.xml` for exclusion/selection classification, resolves sub-sections from v2 code prefixes and [EU Regulation 2016/7](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0007) Annex 2, writes `resources/criterion/code-to-part.php`
4. **Generates Genericode file** — writes `resources/codelists/gc/CriteriaTypeCode.gc` with all v2 codes
5. **Runs UBL generator** — regenerates all PHP classes, codelist enums, and registries from XSD schemas and Genericode files

The generator automatically merges v2 criterion codes into `CriterionCode` with a `V2_` prefix and injects the `CriterionCodeMethods` trait for v2↔v4 conversion.

### Options

| Option | Description |
|--------|-------------|
| `--force`, `-f` | Actually generate files (without this, dry-run only) |
| `--codelists-only` | Only regenerate codelist enums (skip class generation) |
| `--mapping-only` | Only regenerate v2→v4 mapping files (skip UBL generator) |

### Example output

```
ESPD PHP Code Generator
=======================

Step 1: Parsing criterion taxonomies
  V2 taxonomy: 66 criteria
  V4 taxonomy: 62 criteria

Step 2: Cross-referencing UUIDs
  Mapped: 61 codes, V2-only: 5 codes

Step 3: Generating v2→v4 mapping file
  → resources/criterion/v2-to-v4-mapping.php

Step 3b: Generating code-to-part mapping
  Criterion list: 55 codes classified
  Part mapping: 130 codes total
  → resources/criterion/code-to-part.php

Step 4: Generating CriteriaTypeCode.gc
  → resources/codelists/gc/CriteriaTypeCode.gc

Step 5: Running UBL generator
  Loading XSD schemas...
  Emitting codelist enums...

Result
  UBL 2.3: 8 CBC classes, 292 CAC classes, 2 document roots, 5 enums, 16 codelist enums, 325 total files
```

### Multi-version support

The generated `CriterionCode` enum contains both v4 short codes (`crime-org`, `corruption`, ...) and v2 long-form codes prefixed with `V2_` (`CRITERION.EXCLUSION.CONVICTIONS.PARTICIPATION_IN_CRIMINAL_ORGANISATION`, ...). This allows deserializing ESPD documents from both v2.x and v4.x without data loss:

```php
$code = CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION;

$code->isLegacy();        // true
$code->toV4Equivalent();  // CriterionCode::CORRUPTION
```

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyze

# Regenerate all classes and codelists
php php-espd espd:generate --force

# Regenerate only codelist enums (faster)
php php-espd espd:generate --force --codelists-only
```

## EU Legislation References

The ESPD form structure and criterion classification are defined by EU legislation:

| Document | Reference | Relevance |
|----------|-----------|-----------|
| [Commission Implementing Regulation (EU) 2016/7](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0007) | Standard form for the ESPD | Defines the 6-part form structure (Parts I–VI) with sections A–D, used by `EspdPart` enum and `getPart()`/`getSection()` |
| [Directive 2014/24/EU](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32014L0024) | Public procurement directive | Art. 57: exclusion grounds (Parts III-A/B/C/D), Art. 58: selection criteria (Parts IV-A/B/C), Art. 62: quality/environment certificates (Part IV-D) |
| [ESPD-EDM](https://github.com/OP-TED/ESPD-EDM) (OP-TED) | Exchange Data Model | Source of criterion taxonomy XML, Schematron validation rules, and Genericode codelists used by this library |

## License

[MIT](LICENSE) - Copyright (c) 2026 Ceana Razvan
