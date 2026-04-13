<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

enum DocumentType
{
    case Request;
    case Response;

    /** @return list<string> */
    public function ruleFiles(string $resourcesDir, VersionFamily $version): array
    {
        static $cache = [];

        $dir = $resourcesDir . '/' . $version->value . '/' . $this->xslDirectory() . '/xsl';

        if (!isset($cache[$dir])) {
            $pattern = $dir . '/*.xsl';
            $files = glob($pattern);

            if ($files === false || $files === []) {
                throw new Exception\ValidationException(sprintf(
                    'No XSL validation rule files found in: %s. Ensure the ESPD validation resources are installed.',
                    $dir,
                ));
            }

            sort($files, \SORT_STRING);
            $cache[$dir] = array_map('basename', $files);
        }

        return $cache[$dir];
    }

    public function xslDirectory(): string
    {
        return match ($this) {
            self::Request => 'ESPDRequest',
            self::Response => 'ESPDResponse',
        };
    }
}
