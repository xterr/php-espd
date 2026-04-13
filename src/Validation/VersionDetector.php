<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

use Xterr\Espd\Codelist\ProfileExecutionID;

final class VersionDetector
{
    public static function detect(string $xml): ?VersionFamily
    {
        $dom = new \DOMDocument();
        if (@$dom->loadXML($xml) === false) {
            return null;
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $nodes = $xpath->query('/*/cbc:ProfileExecutionID');
        if ($nodes === false) {
            return null;
        }

        $node = $nodes->item(0);
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $profileId = ProfileExecutionID::tryFrom(trim($node->textContent));
        if ($profileId === null) {
            return null;
        }

        return VersionFamily::fromProfileExecutionID($profileId);
    }
}
