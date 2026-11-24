<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Webhooks;

use CommuniQate\Workspaces\Exception\InvalidWebhookSignature;
use InvalidArgumentException;
use UnexpectedValueException;

final class WebhookVerifier
{
    public const SIGNATURE_HEADER = 'Signature';

    public function __construct(#[\SensitiveParameter] private readonly string $secret)
    {
        if (trim($secret) === '') {
            throw new InvalidArgumentException('Configure the signing secret of this webhook endpoint.');
        }
    }

    public function verify(#[\SensitiveParameter] string $rawBody, ?string $signature): WebhookDelivery
    {
        if ($signature === null || !hash_equals(hash_hmac('sha256', $rawBody, $this->secret), $signature)) {
            throw new InvalidWebhookSignature('Invalid CommuniQate webhook signature.');
        }

        $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        $metadata = $payload['metadata'] ?? null;
        $event = is_string($payload['event'] ?? null) ? WebhookEvent::tryFrom($payload['event']) : null;

        if ($event === null || ($payload['version'] ?? null) !== 'v1'
            || !is_array($payload['data'] ?? null) || !is_array($metadata)
            || !is_string($payload['id'] ?? null) || $payload['id'] === ''
            || !is_string($metadata['workspace_id'] ?? null) || $metadata['workspace_id'] === ''
            || !is_string($metadata['subscription_id'] ?? null) || $metadata['subscription_id'] === ''
            || (array_key_exists('is_test', $metadata) && !is_bool($metadata['is_test']))) {
            throw new UnexpectedValueException('Invalid or unsupported CommuniQate webhook envelope.');
        }

        return new WebhookDelivery($payload['id'], $event, $payload['version'], $payload['data'],
            $metadata['workspace_id'], $metadata['subscription_id'], $metadata['is_test'] ?? false);
    }
}
