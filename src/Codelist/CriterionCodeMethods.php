<?php

declare(strict_types=1);

namespace Xterr\Espd\Codelist;

trait CriterionCodeMethods
{
    public function isLegacy(): bool
    {
        return str_starts_with($this->value, 'CRITERION.');
    }

    public function toV4Equivalent(): ?self
    {
        if (!$this->isLegacy()) {
            return $this;
        }

        $map = self::getV2ToV4Map();
        $v4Value = $map[$this->value] ?? null;

        return $v4Value !== null ? self::tryFrom($v4Value) : null;
    }

    /**
     * @return array<string, string> v2 code → v4 code value
     */
    private static function getV2ToV4Map(): array
    {
        static $map = null;

        return $map ??= (static function (): array {
            $file = __DIR__ . '/../../resources/criterion/v2-to-v4-mapping.php';

            return file_exists($file) ? require $file : [];
        })();
    }
}
