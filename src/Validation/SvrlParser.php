<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class SvrlParser
{
    private const SVRL_NS = 'http://purl.oclc.org/dsdl/svrl';

    /** @return list<Violation> */
    public function parse(string $svrlXml): array
    {
        if ($svrlXml === '') {
            return [];
        }

        $dom = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svrlXml, \LIBXML_NOERROR | \LIBXML_NONET);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (!$loaded) {
            throw new Exception\ValidationException(sprintf(
                'Failed to parse SVRL output: %s',
                $errors[0]->message ?? 'unknown error',
            ));
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('svrl', self::SVRL_NS);

        $violations = [];
        $failedAsserts = $xpath->query('//svrl:failed-assert');

        if ($failedAsserts === false) {
            return [];
        }

        foreach ($failedAsserts as $assert) {
            if (!$assert instanceof DOMElement) {
                continue;
            }

            $ruleId = $assert->getAttribute('id');
            $flag = $assert->getAttribute('flag');
            $location = $assert->getAttribute('location');
            $test = $assert->getAttribute('test');

            $severity = Severity::tryFrom($flag) ?? Severity::Error;

            $textNodes = $xpath->query('svrl:text', $assert);
            $message = '';
            if ($textNodes !== false && $textNodes->length > 0) {
                $textNode = $textNodes->item(0);
                $message = $textNode instanceof \DOMElement ? trim($textNode->textContent) : '';
            }

            $pattern = $this->findPrecedingPattern($assert);

            $violations[] = new Violation(
                ruleId: $ruleId,
                severity: $severity,
                message: $message,
                location: $location,
                test: $test,
                pattern: $pattern,
            );
        }

        return $violations;
    }

    private function findPrecedingPattern(DOMElement $element): string
    {
        $sibling = $element->previousSibling;

        while ($sibling !== null) {
            if (
                $sibling instanceof DOMElement
                && $sibling->localName === 'active-pattern'
                && $sibling->namespaceURI === self::SVRL_NS
            ) {
                return $sibling->getAttribute('id');
            }

            $sibling = $sibling->previousSibling;
        }

        return '';
    }
}
