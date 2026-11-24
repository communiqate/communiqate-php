<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Http;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;

final readonly class Psr18Transport implements Transport
{
    public function __construct(private ClientInterface $client)
    {
        if (!class_exists(Request::class)) {
            throw new \LogicException('Install guzzlehttp/psr7 to use the optional PSR-18 adapter.');
        }
    }

    public function send(string $method, string $url, #[\SensitiveParameter] array $headers, #[\SensitiveParameter] string|Upload|null $body): Response
    {
        if ($body instanceof Upload) {
            $body = new MultipartStream([[
                'name' => 'file', 'filename' => $body->filename, 'contents' => Utils::tryFopen($body->filePath, 'rb'),
            ]]);
            $headers['Content-Type'] = 'multipart/form-data; boundary='.$body->getBoundary();
        }
        $response = $this->client->sendRequest(new Request($method, $url, $headers, $body));
        return new Response($response->getStatusCode(), (string) $response->getBody(), $response->getHeaderLine('Retry-After'));
    }
}
