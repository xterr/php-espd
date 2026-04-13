<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

use Saxon\SaxonProcessor;
use Saxon\XsltExecutable;
use Xterr\Espd\Doc\QualificationApplicationRequest;
use Xterr\Espd\Doc\QualificationApplicationResponse;
use Xterr\UBL\Xml\XmlSerializer;

final class EspdValidator
{
    /** @var array<string, XsltExecutable> */
    private array $executables = [];

    public function __construct(
        private readonly SaxonProcessor $processor,
        private readonly XmlSerializer $serializer,
        private readonly SvrlParser $parser,
        private readonly string $resourcesDir,
    ) {
    }

    public static function create(?string $resourcesDir = null): self
    {
        if (!extension_loaded('saxonc')) {
            throw new Exception\SaxonNotAvailableException();
        }

        try {
            $processor = new SaxonProcessor();
        } catch (\Throwable $e) {
            throw new Exception\SaxonNotAvailableException(
                'SaxonProcessor initialization failed: ' . $e->getMessage(),
                $e,
            );
        }

        return new self(
            $processor,
            new XmlSerializer(),
            new SvrlParser(),
            $resourcesDir ?? dirname(__DIR__, 2) . '/resources/validation',
        );
    }

    public function validate(QualificationApplicationRequest|QualificationApplicationResponse $document, ?VersionFamily $version = null): ValidationResult
    {
        $type = match (true) {
            $document instanceof QualificationApplicationRequest => DocumentType::Request,
            $document instanceof QualificationApplicationResponse => DocumentType::Response,
        };

        $xml = $this->serializer->serialize($document);

        return $this->validateXml($xml, $type, $version);
    }

    public function validateXml(string $xml, DocumentType $type, ?VersionFamily $version = null): ValidationResult
    {
        if (preg_match('/<!DOCTYPE/i', $xml) === 1) {
            throw new Exception\ValidationException('XML input must not contain a DOCTYPE declaration.');
        }

        if ($version === null) {
            $version = VersionDetector::detect($xml);
            if ($version === null) {
                throw new Exception\ValidationException(
                    'Could not detect ESPD-EDM version from ProfileExecutionID. Specify the version explicitly.',
                );
            }
        }

        try {
            $sourceNode = $this->processor->parseXmlFromString($xml);
        } catch (\Throwable $e) {
            throw new Exception\ValidationException(
                'Failed to parse source XML: ' . $e->getMessage(),
                $e,
            );
        }

        $violationSets = [];
        $xslDir = $this->resourcesDir . '/' . $version->value . '/' . $type->xslDirectory() . '/xsl';

        foreach ($type->ruleFiles($this->resourcesDir, $version) as $ruleFile) {
            $xslPath = $xslDir . '/' . $ruleFile;

            try {
                $executable = $this->getExecutable($xslPath);
                $svrl = $executable->transformToString($sourceNode);
            } catch (\Throwable $e) {
                throw new Exception\ValidationException(
                    sprintf('XSLT transformation failed for rule "%s": %s', $ruleFile, $e->getMessage()),
                    $e,
                );
            }

            if ($svrl !== null && $svrl !== '') {
                $violationSets[] = $this->parser->parse($svrl);
            }
        }

        return new ValidationResult(array_merge([], ...$violationSets));
    }

    private function getExecutable(string $xslPath): XsltExecutable
    {
        if (!isset($this->executables[$xslPath])) {
            if (!is_file($xslPath)) {
                throw new Exception\ValidationException(sprintf(
                    'Validation rule file not found: %s. Ensure the resources/validation directory is included in the package.',
                    $xslPath,
                ));
            }

            $xslt = $this->processor->newXslt30Processor();
            $this->executables[$xslPath] = $xslt->compileFromFile($xslPath);
        }

        return $this->executables[$xslPath];
    }
}
