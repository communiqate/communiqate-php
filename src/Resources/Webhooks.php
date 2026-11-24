<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class Webhooks extends Resource
{
    /**
     * List webhook endpoints
     * Requires webhooks:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'webhooks', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Create a webhook endpoint
     * Requires webhooks:write.
     */
    public function create(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        $this->requireFields($body, ['delivery_url', 'events']);
        return $this->connection->request('POST', 'webhooks', $query, $body, $idempotencyKey);
    }

    /**
     * Get a webhook endpoint
     * Requires webhooks:read.
     */
    public function get(string $webhookSubscription, array $query = []): array
    {
        return $this->connection->request('GET', 'webhooks/'.ResourcePath::segment($webhookSubscription), $query);
    }

    /**
     * Update a webhook endpoint
     * Requires webhooks:write.
     */
    public function update(string $webhookSubscription, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->connection->request('PATCH', 'webhooks/'.ResourcePath::segment($webhookSubscription), $query, $body);
    }

    /**
     * Delete a webhook endpoint
     * Requires webhooks:write.
     */
    public function delete(string $webhookSubscription, array $query = []): void
    {
        $this->connection->request('DELETE', 'webhooks/'.ResourcePath::segment($webhookSubscription), $query);
    }
}
