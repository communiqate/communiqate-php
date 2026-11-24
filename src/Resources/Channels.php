<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class Channels extends Resource
{
    /**
     * List channels
     * Requires channels:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'channels', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Get a single channel
     * Requires channels:read.
     */
    public function get(string $channel, array $query = []): array
    {
        return $this->connection->request('GET', 'channels/'.ResourcePath::segment($channel), $query);
    }
}
