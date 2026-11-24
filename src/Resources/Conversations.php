<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;
use CommuniQate\Workspaces\MessageValidation;

final class Conversations extends Resource
{
    /**
     * List conversations
     * Requires conversations:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'conversations', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Cursor->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Start a conversation
     * Requires conversations:write.
     */
    public function start(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        $this->requireFields($body, ['channel_id', 'identifier', 'message']);
        MessageValidation::check($body['message'] ?? [], false);
        return $this->connection->request('POST', 'conversations', $query, $body, $idempotencyKey);
    }

    /**
     * Get a single conversation
     * Requires conversations:read.
     */
    public function get(string $conversation, array $query = []): array
    {
        return $this->connection->request('GET', 'conversations/'.ResourcePath::segment($conversation), $query);
    }

    /**
     * Update a conversation
     * Requires conversations:write.
     */
    public function update(string $conversation, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->connection->request('PATCH', 'conversations/'.ResourcePath::segment($conversation), $query, $body);
    }
}
