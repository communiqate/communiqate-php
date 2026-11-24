<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class MetaFields extends Resource
{
    /**
     * List meta fields
     * Requires meta_fields:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'meta-fields', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Create a meta field
     * Requires meta_fields:write.
     */
    public function create(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        $this->requireFields($body, ['name', 'type']);
        return $this->connection->request('POST', 'meta-fields', $query, $body, $idempotencyKey);
    }

    /**
     * Get a single meta field
     * Requires meta_fields:read.
     */
    public function get(string $metaField, array $query = []): array
    {
        return $this->connection->request('GET', 'meta-fields/'.ResourcePath::segment($metaField), $query);
    }

    /**
     * Update a meta field
     * Requires meta_fields:write.
     */
    public function update(string $metaField, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->connection->request('PATCH', 'meta-fields/'.ResourcePath::segment($metaField), $query, $body);
    }

    /**
     * Delete a meta field
     * Requires meta_fields:write.
     */
    public function delete(string $metaField, array $query = []): void
    {
        $this->connection->request('DELETE', 'meta-fields/'.ResourcePath::segment($metaField), $query);
    }
}
