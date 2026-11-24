<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Webhooks;

/** The v1 event envelope. The complete event-specific data is available in $data. */
final readonly class WebhookDelivery
{
    public function __construct(
        public string $id,
        public WebhookEvent $event,
        public string $version,
        public array $data,
        public string $workspaceId,
        public string $subscriptionId,
        public bool $isTest,
    ) {}
}
