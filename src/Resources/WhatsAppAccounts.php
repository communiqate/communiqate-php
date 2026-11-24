<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class WhatsAppAccounts extends Resource
{
    /**
     * List WhatsApp accounts
     * Requires channels:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'whatsapp-accounts', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Get a single WhatsApp account
     * Requires channels:read.
     */
    public function get(string $whatsappAccount, array $query = []): array
    {
        return $this->connection->request('GET', 'whatsapp-accounts/'.ResourcePath::segment($whatsappAccount), $query);
    }
}
