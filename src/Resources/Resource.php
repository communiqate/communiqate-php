<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\Connection;
use InvalidArgumentException;

abstract class Resource
{
    public function __construct(protected readonly Connection $connection) {}

    protected function requireFields(#[\SensitiveParameter] array $body, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $body)) {
                throw new InvalidArgumentException("Missing required request field: $field.");
            }
        }
    }
}
