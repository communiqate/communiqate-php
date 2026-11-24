<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\ResourcePath;
use CommuniQate\Workspaces\Pagination;

final class Contacts extends Resource
{
    /**
     * List contacts
     * Requires contacts:read.
     */
    public function list(array $query = []): array
    {
        return $this->connection->request('GET', 'contacts', $query);
    }

    public function iterate(array $query = []): \Generator
    {
        yield from Pagination::Page->items($query, fn (array $page) => $this->list($page));
    }

    /**
     * Create a contact
     * Requires contacts:write.
     */
    public function create(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->connection->request('POST', 'contacts', $query, $body, $idempotencyKey);
    }

    /**
     * Get a single contact
     * Requires contacts:read.
     */
    public function get(string $contact, array $query = []): array
    {
        return $this->connection->request('GET', 'contacts/'.ResourcePath::segment($contact), $query);
    }

    /**
     * Update a contact
     * Requires contacts:write.
     */
    public function update(string $contact, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->connection->request('PATCH', 'contacts/'.ResourcePath::segment($contact), $query, $body);
    }

    /**
     * Set a custom field value
     * Requires contacts:write.
     */
    public function setMetaField(string $contact, string $metaField, #[\SensitiveParameter] array $body, array $query = []): array
    {
        $this->requireFields($body, ['value']);
        return $this->connection->request('PUT', 'contacts/'.ResourcePath::segment($contact).'/meta-fields/'.ResourcePath::segment($metaField), $query, $body);
    }

    /**
     * Add a contact to a list
     * Requires contacts:write.
     */
    public function addToList(string $contact, string $contactList, ?string $idempotencyKey = null, array $query = []): void
    {
        $this->connection->request('POST', 'contacts/'.ResourcePath::segment($contact).'/lists/'.ResourcePath::segment($contactList), $query, null, $idempotencyKey);
    }

    /**
     * Remove a contact from a list
     * Requires contacts:write.
     */
    public function removeFromList(string $contact, string $contactList, array $query = []): void
    {
        $this->connection->request('DELETE', 'contacts/'.ResourcePath::segment($contact).'/lists/'.ResourcePath::segment($contactList), $query);
    }
}
