<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation\Exception;

final class SaxonNotAvailableException extends \RuntimeException
{
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            $message !== '' ? $message : 'The saxonc PHP extension is required for ESPD validation. '
                . 'Install SaxonC-HE 12.x from https://www.saxonica.com/download/c.html',
            previous: $previous,
        );
    }
}
