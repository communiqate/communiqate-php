<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Http;

use CommuniQate\Workspaces\Exception\ApiException;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use UnexpectedValueException;

final class Connection
{
    private readonly Transport $http;
    private readonly ?string $workspaceUrl;

    public function __construct(
        #[\SensitiveParameter] private readonly ?string $apiKey,
        ?string $workspace,
        Transport|ClientInterface|null $http,
        private readonly string $baseUrl,
    ) {
        if ($apiKey !== null && (trim($apiKey) === '' || preg_match('/[\x00-\x20\x7f]/', $apiKey))) {
            throw new InvalidArgumentException('A workspace API key is required.');
        }
        $url = parse_url($baseUrl);
        if (!isset($url['host']) || ($url['scheme'] ?? '') !== 'https'
            || array_intersect(['user', 'pass', 'query', 'fragment'], array_keys($url))) {
            throw new InvalidArgumentException('Use an HTTPS API base URL without credentials, query or fragment.');
        }
        $this->workspaceUrl = $workspace === null ? null : rtrim($baseUrl, '/').'/'.ResourcePath::segment($workspace);
        $this->http = $http instanceof ClientInterface ? new Psr18Transport($http) : ($http ?? new CurlTransport());
    }

    public function forWorkspace(string $workspace, #[\SensitiveParameter] ?string $apiKey = null): self
    {
        return new self($apiKey ?? $this->apiKey, $workspace, $this->http, $this->baseUrl);
    }

    public function request(string $method, string $path, array $query, #[\SensitiveParameter] array|Upload|null $body = null, ?string $key = null): ?array
    {
        if ($this->workspaceUrl === null) {
            throw new InvalidArgumentException('Select a workspace with forWorkspace() before making an API request.');
        }
        if ($this->apiKey === null) {
            throw new InvalidArgumentException('A workspace API key is required.');
        }
        $headers = ['Authorization' => 'Bearer '.$this->apiKey, 'Accept' => 'application/json'];
        if ($key !== null) {
            if (trim($key) === '' || mb_strlen($key) > 255 || preg_match('/[\x00-\x1f\x7f]/', $key)) {
                throw new InvalidArgumentException('Idempotency-Key must be a non-empty header value of at most 255 characters.');
            }
            $headers['Idempotency-Key'] = $key;
        }
        if (is_array($body)) {
            $headers['Content-Type'] = 'application/json';
            $body = json_encode((object) $body, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        }
        $url = $this->workspaceUrl.'/'.$path;
        if ($query !== []) {
            $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $response = $this->http->send($method, $url, $headers, $body);
        $status = $response->status;
        $raw = $response->body;
        if ($status < 200 || $status >= 300) {
            throw ApiException::fromHttp($status, $raw, $response->retryAfter);
        }
        if ($status === 204) {
            return null;
        }
        $result = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($result)) {
            throw new UnexpectedValueException('Expected a JSON resource or list envelope.');
        }
        return $result;
    }
}
