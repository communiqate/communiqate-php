<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Tests;

use CommuniQate\Workspaces\ChannelType;
use CommuniQate\Workspaces\Client;
use DateTimeImmutable;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MessageBuilderTest extends TestCase
{
    private const WORKSPACE_ID = '019ad5a2-7740-7080-9dd8-47a000000001';
    private const OTHER_WORKSPACE_ID = '019ad5a2-7740-7080-9dd8-47a000000002';
    private const CHANNEL_ID = '019ad5a2-7740-7080-9dd8-47a000000003';

    private array $history = [];

    private function client(?string $key = 'orders-key', ?string $workspace = self::WORKSPACE_ID): Client
    {
        $handler = HandlerStack::create(new MockHandler(array_fill(0, 3, new Response(201, [], '{"message_id":"accepted"}'))));
        $handler->push(Middleware::history($this->history));
        return new Client($key, $workspace, new Guzzle(['handler' => $handler]), 'https://gateway.example.com/v1');
    }

    public static function content(): iterable
    {
        yield 'WhatsApp text' => [ChannelType::WhatsApp, 'text', ['Hello'], 'text', 'Hello'];
        yield 'Instagram image' => [ChannelType::Instagram, 'image', ['https://example.com/image.jpg'], 'image', ['link' => 'https://example.com/image.jpg']];
        yield 'Messenger video' => [ChannelType::Messenger, 'video', ['fileId' => 'file'], 'video', ['file_id' => 'file']];
        yield 'Messenger audio' => [ChannelType::Messenger, 'audio', ['fileId' => 'voice'], 'audio', ['file_id' => 'voice']];
        yield 'WhatsApp document' => [ChannelType::WhatsApp, 'document', ['fileId' => 'f', 'filename' => 'bill.pdf', 'caption' => 'Invoice'], 'document', ['file_id' => 'f', 'caption' => 'Invoice', 'filename' => 'bill.pdf']];
        yield 'WhatsApp sticker' => [ChannelType::WhatsApp, 'whatsAppSticker', ['fileId' => 'sticker'], 'whatsapp_sticker', ['file_id' => 'sticker']];
        $location = ['latitude' => '52.3', 'longitude' => '4.9', 'name' => 'Store', 'address' => 'Market 1'];
        yield 'WhatsApp location' => [ChannelType::WhatsApp, 'location', $location, 'location', $location];
        yield 'WhatsApp template' => [ChannelType::WhatsApp, 'whatsAppTemplate', ['login', 'otpCode' => '001234'], 'whatsapp_template', ['message_template' => 'login', 'otp_code' => '001234']];
        $interactive = ['type' => 'button', 'body' => ['text' => 'Confirm?'], 'action' => ['buttons' => [['type' => 'reply', 'reply' => ['id' => 'yes', 'title' => 'Yes']]]]];
        yield 'WhatsApp interactive' => [ChannelType::WhatsApp, 'whatsAppInteractive', [$interactive], 'whatsapp_interactive', $interactive];
        $email = ['subject' => 'Sign in', 'html' => '<p>Your link</p>', 'sensitive' => true];
        yield 'email' => [ChannelType::Email, 'email', [$email], 'email', $email];
    }

    #[DataProvider('content')]
    public function testBuildAndSendUseTheSameWirePayload(ChannelType $type, string $method, array $args, string $wireType, mixed $content): void
    {
        $client = $this->client();
        $draft = $client->$method(...$args)->to('recipient')->channel(self::CHANNEL_ID, $type);
        $payload = ['channel_id' => self::CHANNEL_ID, 'to' => 'recipient', 'type' => $wireType, $wireType => $content];
        self::assertSame($payload, $draft->build());
        self::assertSame([], $this->history);
        self::assertSame(['message_id' => 'accepted'], $draft->send('delivery-42'));
        self::assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://gateway.example.com/v1/'.self::WORKSPACE_ID.'/messages', (string) $request->getUri());
        self::assertSame('delivery-42', $request->getHeaderLine('Idempotency-Key'));
        self::assertSame($payload, json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testWorkspaceSelectionPreservesTheTransportAndIsolatesCredentials(): void
    {
        $client = $this->client();
        $original = $client->text('Original')->channel('orders')->to('first');
        $client->forWorkspace(self::OTHER_WORKSPACE_ID, 'other-key')->text('Scoped')->to('second')->channel('other')->send();
        $client->forWorkspace(self::WORKSPACE_ID)->text('Default key')->channel('orders')->to('third')->send();
        $original->send();
        $requests = array_column($this->history, 'request');
        self::assertSame([
            'https://gateway.example.com/v1/'.self::OTHER_WORKSPACE_ID.'/messages',
            'https://gateway.example.com/v1/'.self::WORKSPACE_ID.'/messages',
            'https://gateway.example.com/v1/'.self::WORKSPACE_ID.'/messages',
        ], array_map(fn ($r) => (string) $r->getUri(), $requests));
        self::assertSame(['Bearer other-key', 'Bearer orders-key', 'Bearer orders-key'],
            array_map(fn ($r) => $r->getHeaderLine('Authorization'), $requests));
    }

    public function testBuilderCanBranchWithoutChangingRecipientsChannelsOrOptionsOnOtherDrafts(): void
    {
        $original = $this->client()->text('Ready')->channel('orders')->to('one');
        $reply = $original->channel('support', ChannelType::Messenger)->to('two')
            ->replyTo('message')->asHumanAgent()->scheduleFor(new DateTimeImmutable('2030-03-01T15:00:00+01:00'));
        self::assertSame(['channel_id' => 'orders', 'to' => 'one', 'type' => 'text', 'text' => 'Ready'], $original->build());
        self::assertSame([
            'channel_id' => 'support', 'to' => 'two', 'type' => 'text', 'text' => 'Ready',
            'reply_to_message_id' => 'message', 'human_agent' => true, 'scheduled_for' => '2030-03-01T15:00:00+01:00',
        ], $reply->build());
    }

    public static function invalidDrafts(): iterable
    {
        yield 'missing sender' => [fn (Client $c) => $c->text('hello')->to('recipient')];
        yield 'missing recipient' => [fn (Client $c) => $c->text('hello')->channel(self::CHANNEL_ID)];
        yield 'media without source' => [fn (Client $c) => $c->image()];
        yield 'media with two sources' => [fn (Client $c) => $c->audio('https://example.com/a.mp3', 'file')];
        yield 'template on Instagram' => [fn (Client $c) => $c->whatsAppTemplate('confirm')->channel(self::CHANNEL_ID, ChannelType::Instagram)->to('recipient')];
        yield 'sensitive email cannot be scheduled' => [fn (Client $c) => $c->email(['subject' => 'Login', 'text' => 'Code', 'sensitive' => true])->channel(self::CHANNEL_ID)->to('recipient')->scheduleFor('2030-05-01T12:00:00Z')];
        yield 'human agent WhatsApp message' => [fn (Client $c) => $c->text('hello')->channel(self::CHANNEL_ID, ChannelType::WhatsApp)->to('recipient')->asHumanAgent()];
    }

    #[DataProvider('invalidDrafts')]
    public function testInvalidMessagesFailBeforeTransport(callable $draft): void
    {
        $this->expectException(InvalidArgumentException::class);
        try {
            $draft($this->client())->send();
        } finally {
            self::assertSame([], $this->history);
        }
    }

    public function testExplicitWorkspaceCanBeUsedWithoutAnyDefaults(): void
    {
        $client = $this->client(null, null);
        $client->forWorkspace(self::WORKSPACE_ID, 'ws-key')->text('Hello')->to('someone')->channel(self::CHANNEL_ID)->send();
        self::assertSame('Bearer ws-key', $this->history[0]['request']->getHeaderLine('Authorization'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Select a workspace');
        $client->listChannels();
    }

    public function testAnUnconfiguredKeyFailsBeforeTransport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('workspace API key');
        $this->client(null, self::WORKSPACE_ID)->text('hello')->to('recipient')->channel(self::CHANNEL_ID)->send();
    }
}
