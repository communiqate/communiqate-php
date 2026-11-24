<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $errors,
        public readonly ?string $retryAfter,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(ResponseInterface $response, string $body): self
    {
        return self::fromHttp($response->getStatusCode(), $body, $response->getHeaderLine('Retry-After'));
    }

    public static function fromHttp(int $status, string $body, string $retryAfter): self
    {
        $error = json_decode($body, true)['error'] ?? [];
        if ($retryAfter === '' && is_scalar($error['retry_after'] ?? null)) {
            $retryAfter = (string) $error['retry_after'];
        }
        return new self(
            $status,
            is_string($error['code'] ?? null) ? $error['code'] : 'http_error',
            is_string($error['message'] ?? null) ? $error['message'] : "CommuniQate returned HTTP $status.",
            is_array($error['errors'] ?? null) ? $error['errors'] : [],
            $retryAfter === '' ? null : $retryAfter,
        );
    }
}
