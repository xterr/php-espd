<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Validation\VersionDetector;
use Xterr\Espd\Validation\VersionFamily;

final class VersionDetectorTest extends TestCase
{
    #[Test]
    public function detectsV41FromRequest(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv4.1.0</cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertSame(VersionFamily::V4_1, VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsV2FromRequest(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv2.1.1-BASIC</cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertSame(VersionFamily::V2, VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsV3FromResponse(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationResponse xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationResponse-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv3.3.0</cbc:ProfileExecutionID>
            </QualificationApplicationResponse>
            XML;

        self::assertSame(VersionFamily::V3, VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsV40FromRequest(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv4.0.0</cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertSame(VersionFamily::V4_0, VersionDetector::detect($xml));
    }

    #[Test]
    public function returnsNullForMissingProfileExecutionID(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
            </QualificationApplicationRequest>
            XML;

        self::assertNull(VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsV41FromResponse(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationResponse xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationResponse-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv4.1.0</cbc:ProfileExecutionID>
            </QualificationApplicationResponse>
            XML;

        self::assertSame(VersionFamily::V4_1, VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsVersionWithWhitespaceAroundProfileExecutionID(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>  ESPD-EDMv4.1.0  </cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertSame(VersionFamily::V4_1, VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsV2RegulatedVariant(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv2.0.0-REGULATED</cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertSame(VersionFamily::V2, VersionDetector::detect($xml));
    }

    #[Test]
    public function detectsV2SelfcontainedVariant(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv2.1.1-EXTENDED</cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertSame(VersionFamily::V2, VersionDetector::detect($xml));
    }

    #[Test]
    public function returnsNullForInvalidXml(): void
    {
        self::assertNull(VersionDetector::detect('<not valid xml'));
    }

    #[Test]
    public function returnsNullForUnknownProfileExecutionID(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <QualificationApplicationRequest xmlns="urn:oasis:names:specification:ubl:schema:xsd:QualificationApplicationRequest-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
                <cbc:ProfileExecutionID>ESPD-EDMv99.0.0</cbc:ProfileExecutionID>
            </QualificationApplicationRequest>
            XML;

        self::assertNull(VersionDetector::detect($xml));
    }
}
