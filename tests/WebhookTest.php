<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Tests;

use CommuniQate\Workspaces\Exception\InvalidWebhookSignature;
use CommuniQate\Workspaces\Webhooks\WebhookEvent;
use CommuniQate\Workspaces\Webhooks\WebhookVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    public static function events(): iterable
    {
        foreach (WebhookEvent::cases() as $event) {
            yield $event->value => [$event->value];
        }
    }

    #[DataProvider('events')]
    public function testVerifiedOpenApiEvent(string $name): void
    {
        $body = json_encode([
            'id' => 'delivery', 'event' => $name, 'version' => 'v1', 'data' => ['label' => 'café'],
            'metadata' => ['workspace_id' => 'ws', 'subscription_id' => 'sub', 'is_test' => true],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $delivery = (new WebhookVerifier('endpoint-secret'))->verify($body, hash_hmac('sha256', $body, 'endpoint-secret'));
        self::assertSame(WebhookEvent::from($name), $delivery->event);
        self::assertSame(['delivery', 'v1', ['label' => 'café'], 'ws', 'sub', true], [
            $delivery->id, $delivery->version, $delivery->data, $delivery->workspaceId, $delivery->subscriptionId, $delivery->isTest,
        ]);
    }

    public static function badSignatures(): iterable
    {
        yield 'missing' => [null];
        yield 'another endpoint' => [hash_hmac('sha256', '{}', 'other-secret')];
        yield 'different bytes' => [hash_hmac('sha256', "{ }", 'secret')];
    }

    #[DataProvider('badSignatures')]
    public function testRejectsInvalidSignatureBeforeParsing(string|null $signature): void
    {
        $this->expectException(InvalidWebhookSignature::class);
        (new WebhookVerifier('secret'))->verify('{}', $signature);
    }

    public function testRejectsSignedButIncompleteEnvelope(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        (new WebhookVerifier('secret'))->verify('{}', hash_hmac('sha256', '{}', 'secret'));
    }

    public function testFailsClosedWithMissingSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WebhookVerifier('');
    }
}
