<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;
use CommuniQate\Workspaces\MessageValidation;

final class Messages extends Resource
{
    /**
     * Send a message in a conversation (legacy)
     * Requires messages:write.
     * @deprecated Use send() with an explicit recipient.
     */
    public function sendInConversation(string $conversation, #[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        MessageValidation::check($body, false);
        return $this->connection->request('POST', 'conversations/'.ResourcePath::segment($conversation).'/messages', $query, $body, $idempotencyKey);
    }

    /**
     * List messages
     * Requires messages:read.
     */
    public function list(string $conversation, array $query = []): array
    {
        return $this->connection->request('GET', 'conversations/'.ResourcePath::segment($conversation).'/messages', $query);
    }

    public function iterate(string $conversation, array $query = []): \Generator
    {
        yield from Pagination::Cursor->items($query, fn (array $page) => $this->list($conversation, $page));
    }

    /**
     * Get a single message
     * Requires messages:read.
     */
    public function get(string $conversation, string $message, array $query = []): array
    {
        return $this->connection->request('GET', 'conversations/'.ResourcePath::segment($conversation).'/messages/'.ResourcePath::segment($message), $query);
    }

    /**
     * Send a message
     * Requires messages:write.
     */
    public function send(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        $this->requireFields($body, ['channel_id', 'to']);
        MessageValidation::check($body, true);
        return $this->connection->request('POST', 'messages', $query, $body, $idempotencyKey);
    }

    /**
     * Show a typing indicator
     * Requires messages:write.
     */
    public function typingIndicator(string $conversation, #[\SensitiveParameter] array $body = [], ?string $idempotencyKey = null, array $query = []): void
    {
        $this->connection->request('POST', 'conversations/'.ResourcePath::segment($conversation).'/typing-indicator', $query, $body, $idempotencyKey);
    }
}
