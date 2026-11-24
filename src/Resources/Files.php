<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Resources;

use CommuniQate\Workspaces\Http\Upload;

final class Files extends Resource
{
    /**
     * Upload a file
     * Requires messages:write.
     */
    public function upload(string $filePath, ?string $filename = null, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->connection->request('POST', 'files', $query, new Upload($filePath, $filename), $idempotencyKey);
    }
}
