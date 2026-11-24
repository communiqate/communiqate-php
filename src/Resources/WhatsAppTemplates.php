<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class WhatsAppTemplates extends Resource
{
    /**
     * List WhatsApp message templates
     * Requires templates:read.
     */
    public function list(string $whatsappAccount, array $query = []): array
    {
        return $this->connection->request('GET', 'whatsapp-accounts/'.ResourcePath::segment($whatsappAccount).'/message-templates', $query);
    }

    public function iterate(string $whatsappAccount, array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($whatsappAccount, $page));
    }

    /**
     * Create a WhatsApp message template
     * Requires templates:write.
     */
    public function create(string $whatsappAccount, #[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        $this->requireFields($body, ['name', 'language', 'category', 'components']);
        return $this->connection->request('POST', 'whatsapp-accounts/'.ResourcePath::segment($whatsappAccount).'/message-templates', $query, $body, $idempotencyKey);
    }

    /**
     * Get a single WhatsApp message template
     * Requires templates:read.
     */
    public function get(string $whatsappAccount, string $messageTemplate, array $query = []): array
    {
        return $this->connection->request('GET', 'whatsapp-accounts/'.ResourcePath::segment($whatsappAccount).'/message-templates/'.ResourcePath::segment($messageTemplate), $query);
    }

    /**
     * Update a WhatsApp message template
     * Requires templates:write.
     */
    public function update(string $whatsappAccount, string $messageTemplate, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->connection->request('PATCH', 'whatsapp-accounts/'.ResourcePath::segment($whatsappAccount).'/message-templates/'.ResourcePath::segment($messageTemplate), $query, $body);
    }

    /**
     * Submit a WhatsApp message template
     * Requires templates:write.
     */
    public function submit(string $whatsappAccount, string $messageTemplate, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->connection->request('POST', 'whatsapp-accounts/'.ResourcePath::segment($whatsappAccount).'/message-templates/'.ResourcePath::segment($messageTemplate).'/submit', $query, null, $idempotencyKey);
    }
}
