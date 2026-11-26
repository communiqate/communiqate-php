<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

final class Events extends Resource
{
    /** Record an occurrence for an existing contact. Requires events:write. */
    public function record(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null): array
    {
        $this->requireFields($body, ['key', 'contact_id', 'external_id']);

        return $this->connection->request('POST', 'events', [], $body, $idempotencyKey);
    }
}
