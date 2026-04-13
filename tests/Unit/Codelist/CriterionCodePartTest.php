<?php

declare(strict_types=1);

namespace Xterr\Espd\Tests\Unit\Codelist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xterr\Espd\Codelist\CriterionCode;
use Xterr\Espd\Codelist\EspdPart;

final class CriterionCodePartTest extends TestCase
{
    // ── 8.2 getPart() — v4 exclusion codes → Part III ───────────────

    #[Test]
    public function allV4ExclusionCodesMapToPartIII(): void
    {
        $exclusionCodes = [
            CriterionCode::CRIME_ORG,
            CriterionCode::CORRUPTION,
            CriterionCode::FRAUD,
            CriterionCode::TERR_OFFENCE,
            CriterionCode::FINAN_LAUND,
            CriterionCode::HUMAN_TRAFFIC,
            CriterionCode::TAX_PAY,
            CriterionCode::SOCSEC_PAY,
            CriterionCode::ENVIR_LAW,
            CriterionCode::SOCSEC_LAW,
            CriterionCode::LABOUR_LAW,
            CriterionCode::BANKRUPTCY,
            CriterionCode::INSOLVENCY,
            CriterionCode::CRED_ARRAN,
            CriterionCode::BANKR_NAT,
            CriterionCode::LIQ_ADMIN,
            CriterionCode::SUSP_ACT,
            CriterionCode::PROF_MISCONDUCT,
            CriterionCode::DISTORSION,
            CriterionCode::PARTIC_CONFL,
            CriterionCode::PREP_CONFL,
            CriterionCode::SANCTION,
            CriterionCode::MISINTERPR,
            CriterionCode::NATI_GROUND,
        ];

        self::assertCount(24, $exclusionCodes);

        foreach ($exclusionCodes as $code) {
            self::assertSame(
                EspdPart::III,
                $code->getPart(),
                sprintf('%s should map to Part III', $code->name),
            );
        }
    }

    #[Test]
    public function allV4SelectionCodesMapToPartIV(): void
    {
        $selectionCodes = [
            CriterionCode::PROF_REGIST,
            CriterionCode::TRADE_REGIST,
            CriterionCode::AUTORISATION,
            CriterionCode::MEMBERSHIP,
            CriterionCode::GEN_YEAR_TO,
            CriterionCode::AVER_YEAR_TO,
            CriterionCode::SPEC_AVER_TO,
            CriterionCode::SPEC_YEAR_TO,
            CriterionCode::FINAN_RAT,
            CriterionCode::INDEM_INSU,
            CriterionCode::FINAN_REQU,
            CriterionCode::WORK_PERFORM,
            CriterionCode::SUPPLY_PERFORM,
            CriterionCode::SERVICE_PERFORM,
            CriterionCode::QUAL_CONT_TECH,
            CriterionCode::WORK_TECH,
            CriterionCode::QUAL_FACIL,
            CriterionCode::RESEARCH_FAC,
            CriterionCode::CHAIN_MANAGE,
            CriterionCode::QUALIFICATION,
            CriterionCode::ENVIR_MEASURE,
            CriterionCode::TECH_EQUIP,
            CriterionCode::SPEC_REQ_CHECK,
            CriterionCode::MANAGE_STAFF,
            CriterionCode::YEAR_MANPOWER,
            CriterionCode::SUNCONT_PORT,
            CriterionCode::WO_AUTENT,
            CriterionCode::W_AUTENT,
            CriterionCode::QA_CERTIF_INST,
            CriterionCode::QU_CERTIF_INDEP,
            CriterionCode::ENVIR_CERTIF_INDEP,
        ];

        self::assertCount(31, $selectionCodes);

        foreach ($selectionCodes as $code) {
            self::assertSame(
                EspdPart::IV,
                $code->getPart(),
                sprintf('%s should map to Part IV', $code->name),
            );
        }
    }

    #[Test]
    public function otherPartIICodesMapCorrectly(): void
    {
        $partIICodes = [
            CriterionCode::SHELT_WORKSH,
            CriterionCode::REGISTERED,
            CriterionCode::EO_GROUP,
            CriterionCode::RELIED,
            CriterionCode::SUBCO_ENT,
            CriterionCode::SME,
        ];

        foreach ($partIICodes as $code) {
            self::assertSame(
                EspdPart::II,
                $code->getPart(),
                sprintf('%s should map to Part II', $code->name),
            );
        }
    }

    #[Test]
    public function staffRedMapsToPartV(): void
    {
        self::assertSame(EspdPart::V, CriterionCode::STAFF_RED->getPart());
    }

    #[Test]
    public function aliasCodesMapToCorrectParts(): void
    {
        self::assertSame(EspdPart::III, CriterionCode::MISREPRESENT->getPart());
        self::assertSame(EspdPart::IV, CriterionCode::AUTHORISATION->getPart());
    }

    // ── 8.3 getSection() — Part III sections ────────────────────────

    #[Test]
    public function partIIIConvictionCodesHaveSectionA(): void
    {
        $codes = [
            CriterionCode::CRIME_ORG,
            CriterionCode::CORRUPTION,
            CriterionCode::FRAUD,
            CriterionCode::TERR_OFFENCE,
            CriterionCode::FINAN_LAUND,
            CriterionCode::HUMAN_TRAFFIC,
        ];

        foreach ($codes as $code) {
            self::assertSame('A', $code->getSection(), sprintf('%s should be section A', $code->name));
        }
    }

    #[Test]
    public function partIIIContributionCodesHaveSectionB(): void
    {
        self::assertSame('B', CriterionCode::TAX_PAY->getSection());
        self::assertSame('B', CriterionCode::SOCSEC_PAY->getSection());
    }

    #[Test]
    public function partIIIDiscretionaryCodesHaveSectionC(): void
    {
        $codes = [
            CriterionCode::ENVIR_LAW,
            CriterionCode::SOCSEC_LAW,
            CriterionCode::LABOUR_LAW,
            CriterionCode::BANKRUPTCY,
            CriterionCode::INSOLVENCY,
            CriterionCode::CRED_ARRAN,
            CriterionCode::BANKR_NAT,
            CriterionCode::LIQ_ADMIN,
            CriterionCode::SUSP_ACT,
            CriterionCode::PROF_MISCONDUCT,
            CriterionCode::DISTORSION,
            CriterionCode::PARTIC_CONFL,
            CriterionCode::PREP_CONFL,
            CriterionCode::SANCTION,
            CriterionCode::MISINTERPR,
            CriterionCode::MISREPRESENT,
        ];

        foreach ($codes as $code) {
            self::assertSame('C', $code->getSection(), sprintf('%s should be section C', $code->name));
        }
    }

    #[Test]
    public function partIIINationalGroundHasSectionD(): void
    {
        self::assertSame('D', CriterionCode::NATI_GROUND->getSection());
    }

    // ── 8.3 getSection() — Part IV sections ─────────────────────────

    #[Test]
    public function partIVSuitabilityCodesHaveSectionA(): void
    {
        $codes = [
            CriterionCode::PROF_REGIST,
            CriterionCode::TRADE_REGIST,
            CriterionCode::AUTORISATION,
            CriterionCode::AUTHORISATION,
            CriterionCode::MEMBERSHIP,
        ];

        foreach ($codes as $code) {
            self::assertSame('A', $code->getSection(), sprintf('%s should be section A', $code->name));
        }
    }

    #[Test]
    public function partIVEconomicCodesHaveSectionB(): void
    {
        $codes = [
            CriterionCode::GEN_YEAR_TO,
            CriterionCode::AVER_YEAR_TO,
            CriterionCode::SPEC_AVER_TO,
            CriterionCode::SPEC_YEAR_TO,
            CriterionCode::FINAN_RAT,
            CriterionCode::INDEM_INSU,
            CriterionCode::FINAN_REQU,
        ];

        foreach ($codes as $code) {
            self::assertSame('B', $code->getSection(), sprintf('%s should be section B', $code->name));
        }
    }

    #[Test]
    public function partIVTechnicalCodesHaveSectionC(): void
    {
        $codes = [
            CriterionCode::WORK_PERFORM,
            CriterionCode::SUPPLY_PERFORM,
            CriterionCode::SERVICE_PERFORM,
            CriterionCode::QUAL_CONT_TECH,
            CriterionCode::WORK_TECH,
            CriterionCode::QUAL_FACIL,
            CriterionCode::RESEARCH_FAC,
            CriterionCode::CHAIN_MANAGE,
            CriterionCode::QUALIFICATION,
            CriterionCode::ENVIR_MEASURE,
            CriterionCode::TECH_EQUIP,
            CriterionCode::SPEC_REQ_CHECK,
            CriterionCode::MANAGE_STAFF,
            CriterionCode::YEAR_MANPOWER,
            CriterionCode::SUNCONT_PORT,
            CriterionCode::WO_AUTENT,
            CriterionCode::W_AUTENT,
        ];

        foreach ($codes as $code) {
            self::assertSame('C', $code->getSection(), sprintf('%s should be section C', $code->name));
        }
    }

    #[Test]
    public function partIVCertificateCodesHaveSectionD(): void
    {
        $codes = [
            CriterionCode::QA_CERTIF_INST,
            CriterionCode::QU_CERTIF_INDEP,
            CriterionCode::ENVIR_CERTIF_INDEP,
        ];

        foreach ($codes as $code) {
            self::assertSame('D', $code->getSection(), sprintf('%s should be section D', $code->name));
        }
    }

    // ── 8.3 getSection() — Part II sections ─────────────────────────

    #[Test]
    public function partIISectionACodesMapCorrectly(): void
    {
        $codes = [
            CriterionCode::SME,
            CriterionCode::SHELT_WORKSH,
            CriterionCode::REGISTERED,
            CriterionCode::EO_GROUP,
        ];

        foreach ($codes as $code) {
            self::assertSame('A', $code->getSection(), sprintf('%s should be section A', $code->name));
        }
    }

    #[Test]
    public function partIIReliedHasSectionC(): void
    {
        self::assertSame('C', CriterionCode::RELIED->getSection());
    }

    #[Test]
    public function partIISubcoEntHasSectionD(): void
    {
        self::assertSame('D', CriterionCode::SUBCO_ENT->getSection());
    }

    // ── 8.3 getSection() — Part V ───────────────────────────────────

    #[Test]
    public function partVStaffRedHasNullSection(): void
    {
        self::assertNull(CriterionCode::STAFF_RED->getSection());
    }

    // ── 8.4 V2 getPart and getSection ───────────────────────────────

    #[Test]
    public function v2ExclusionConvictionMapsToPartIIISectionA(): void
    {
        $code = CriterionCode::V2_CRITERION_EXCLUSION_CONVICTIONS_CORRUPTION;

        self::assertSame(EspdPart::III, $code->getPart());
        self::assertSame('A', $code->getSection());
    }

    #[Test]
    public function v2SelectionSuitabilityMapsToPartIVSectionA(): void
    {
        $code = CriterionCode::V2_CRITERION_SELECTION_SUITABILITY_PROFESSIONAL_REGISTER_ENROLMENT;

        self::assertSame(EspdPart::IV, $code->getPart());
        self::assertSame('A', $code->getSection());
    }

    #[Test]
    public function v2SelectionEconomicMapsToPartIVSectionB(): void
    {
        $code = CriterionCode::V2_CRITERION_SELECTION_ECONOMIC_FINANCIAL_STANDING_FINANCIAL_RATIO;

        self::assertSame(EspdPart::IV, $code->getPart());
        self::assertSame('B', $code->getSection());
    }

    #[Test]
    public function v2SelectionTechnicalMapsToPartIVSectionC(): void
    {
        $code = CriterionCode::V2_CRITERION_SELECTION_TECHNICAL_PROFESSIONAL_ABILITY_REFERENCES_WORKS_PERFORMANCE;

        self::assertSame(EspdPart::IV, $code->getPart());
        self::assertSame('C', $code->getSection());
    }

    #[Test]
    public function v2SelectionCertificatesMapsToPartIVSectionD(): void
    {
        $code = CriterionCode::V2_CRITERION_SELECTION_TECHNICAL_PROFESSIONAL_ABILITY_CERTIFICATES_QUALITY_ASSURANCE_QA_INSTITUTES_CERTIFICATE;

        self::assertSame(EspdPart::IV, $code->getPart());
        self::assertSame('D', $code->getSection());
    }

    #[Test]
    public function v2OtherEoDataShelteredWorkshopMapsToPartIISectionA(): void
    {
        $code = CriterionCode::V2_CRITERION_OTHER_EO_DATA_SHELTERED_WORKSHOP;

        self::assertSame(EspdPart::II, $code->getPart());
        self::assertSame('A', $code->getSection());
    }

    #[Test]
    public function v2OtherEoDataReductionOfCandidatesMapsToPartVNullSection(): void
    {
        $code = CriterionCode::V2_CRITERION_OTHER_EO_DATA_REDUCTION_OF_CANDIDATES;

        self::assertSame(EspdPart::V, $code->getPart());
        self::assertNull($code->getSection());
    }

    #[Test]
    public function v2OtherEoDataLotsTenderedMapsToPartIISectionA(): void
    {
        $code = CriterionCode::V2_CRITERION_OTHER_EO_DATA_LOTS_TENDERED;

        self::assertSame(EspdPart::II, $code->getPart());
        self::assertSame('A', $code->getSection());
    }

    #[Test]
    public function v2DefenceSelectionOtherMapsToPartIV(): void
    {
        $code = CriterionCode::V2_CRITERION_DEFENCE_SELECTION_OTHER;

        self::assertSame(EspdPart::IV, $code->getPart());
    }

    #[Test]
    public function v2SelectionAllMapsToPartIVSectionAlpha(): void
    {
        $code = CriterionCode::V2_CRITERION_SELECTION_ALL;

        self::assertSame(EspdPart::IV, $code->getPart());
        self::assertSame("\u{03B1}", $code->getSection());
    }

    // ── 8.5 Exhaustive coverage ─────────────────────────────────────

    #[Test]
    public function everyCodeHasANonNullPart(): void
    {
        foreach (CriterionCode::cases() as $code) {
            self::assertNotNull(
                $code->getPart(),
                sprintf('%s::getPart() must not return null', $code->name),
            );
        }
    }

    #[Test]
    public function sectionIsNonNullForPartIIIAndPartII(): void
    {
        foreach (CriterionCode::cases() as $code) {
            $part = $code->getPart();

            if ($part === EspdPart::III || $part === EspdPart::II) {
                self::assertNotNull(
                    $code->getSection(),
                    sprintf('%s has Part %s but null section', $code->name, $part->value),
                );
            }
        }
    }

    #[Test]
    public function sectionIsNonNullForPartIVExceptGenericSelectors(): void
    {
        $allowedNullSection = [
            CriterionCode::V2_CRITERION_DEFENCE_SELECTION_OTHER,
            CriterionCode::V2_CRITERION_UTILITIES_SELECTION_OTHER,
        ];

        foreach (CriterionCode::cases() as $code) {
            if ($code->getPart() !== EspdPart::IV) {
                continue;
            }

            if (in_array($code, $allowedNullSection, true)) {
                continue;
            }

            self::assertNotNull(
                $code->getSection(),
                sprintf('%s has Part IV but null section', $code->name),
            );
        }
    }
}
