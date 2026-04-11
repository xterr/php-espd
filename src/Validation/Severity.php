<?php

declare(strict_types=1);

namespace Xterr\Espd\Validation;

enum Severity: string
{
    case Fatal = 'fatal';
    case Error = 'error';
    case Warning = 'warning';
}
