<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Http;

interface Transport
{
    /** @param array<string, string> $headers */
    public function send(string $method, string $url, #[\SensitiveParameter] array $headers, #[\SensitiveParameter] string|Upload|null $body): Response;
}
