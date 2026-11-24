<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

use CommuniQate\Workspaces\Contracts\MessageSender;
use CommuniQate\Workspaces\Http\Connection;
use CommuniQate\Workspaces\Http\Transport;
use CommuniQate\Workspaces\Resources\Channels;
use CommuniQate\Workspaces\Resources\Contacts;
use CommuniQate\Workspaces\Resources\Conversations;
use CommuniQate\Workspaces\Resources\EmailTemplates;
use CommuniQate\Workspaces\Resources\Files;
use CommuniQate\Workspaces\Resources\Messages;
use CommuniQate\Workspaces\Resources\MetaFields;
use CommuniQate\Workspaces\Resources\Webhooks;
use CommuniQate\Workspaces\Resources\WhatsAppAccounts;
use CommuniQate\Workspaces\Resources\WhatsAppTemplates;
use Psr\Http\Client\ClientInterface;

final class Client implements MessageSender
{
    use BuildsMessages;
    use DirectOperations;

    public const BASE_URL = 'https://api.communiqate.nl/workspaces/v1';

    private Connection $connection;

    public function __construct(
        #[\SensitiveParameter] ?string $apiKey = null,
        ?string $workspace = null,
        Transport|ClientInterface|null $http = null,
        string $baseUrl = self::BASE_URL,
    ) {
        $this->connection = new Connection($apiKey, $workspace, $http, $baseUrl);
    }

    public function forWorkspace(string $workspace, #[\SensitiveParameter] ?string $apiKey = null): self
    {
        $client = clone $this;
        $client->connection = $this->connection->forWorkspace($workspace, $apiKey);
        return $client;
    }

    public function channels(): Channels
    {
        return new Channels($this->connection);
    }

    public function contacts(): Contacts
    {
        return new Contacts($this->connection);
    }

    public function conversations(): Conversations
    {
        return new Conversations($this->connection);
    }

    public function messages(): Messages
    {
        return new Messages($this->connection);
    }

    public function emailTemplates(): EmailTemplates
    {
        return new EmailTemplates($this->connection);
    }

    public function files(): Files
    {
        return new Files($this->connection);
    }

    public function metaFields(): MetaFields
    {
        return new MetaFields($this->connection);
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this->connection);
    }

    public function whatsAppAccounts(): WhatsAppAccounts
    {
        return new WhatsAppAccounts($this->connection);
    }

    public function whatsAppTemplates(): WhatsAppTemplates
    {
        return new WhatsAppTemplates($this->connection);
    }

    /** Send a typed message using the routing identifier for the chosen channel. */
    public function sendOnChannel(ChannelType $channel, string $channelId, string $to, #[\SensitiveParameter] array $content, ?string $idempotencyKey = null): array
    {
        $channel->validateMessage($content);
        return $this->sendMessage(['channel_id' => $channelId, 'to' => $to] + $content, $idempotencyKey);
    }

    public function sendEmail(string $channelId, string $to, #[\SensitiveParameter] array $email, ?string $idempotencyKey = null): array
    {
        return $this->sendMessage(['channel_id' => $channelId, 'to' => $to, 'type' => 'email', 'email' => $email], $idempotencyKey);
    }

    public function sendWhatsAppText(string $channelId, string $to, string $text, ?string $idempotencyKey = null): array
    {
        return $this->sendMessage(['channel_id' => $channelId, 'to' => $to, 'type' => 'text', 'text' => $text], $idempotencyKey);
    }

    public function sendWhatsAppTemplate(string $channelId, string $to, #[\SensitiveParameter] array $template, ?string $idempotencyKey = null): array
    {
        return $this->sendMessage([
            'channel_id' => $channelId, 'to' => $to, 'type' => 'whatsapp_template', 'whatsapp_template' => $template,
        ], $idempotencyKey);
    }
}
