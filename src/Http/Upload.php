<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Http;

use InvalidArgumentException;

final readonly class Upload
{
    public string $filename;

    public function __construct(public string $filePath, ?string $filename = null)
    {
        if (str_contains($filePath, '://') || !is_file($filePath) || !is_readable($filePath) || filesize($filePath) > 25_000_000) {
            throw new InvalidArgumentException('Supply a readable local file of at most 25 MB.');
        }
        $filename ??= basename($filePath);
        if ($filename === '' || preg_match('/[\x00-\x1f\x7f"\\\\\/]/', $filename)) {
            throw new InvalidArgumentException('Invalid upload filename.');
        }
        $this->filename = $filename;
    }
}
