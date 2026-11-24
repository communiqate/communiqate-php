<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class EmailTemplates extends Resource
{
    /**
     * List email templates
     * Requires templates:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'email-templates', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Get a single email template
     * Requires templates:read.
     */
    public function get(string $emailTemplate, array $query = []): array
    {
        return $this->connection->request('GET', 'email-templates/'.ResourcePath::segment($emailTemplate), $query);
    }
}
