<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Http;

use InvalidArgumentException;

final class ResourcePath
{
    public static function segment(string $value): string
    {
        if (trim($value) === '' || $value === '.' || $value === '..') {
            throw new InvalidArgumentException('A non-empty resource identifier is required.');
        }
        return rawurlencode($value);
    }
}
