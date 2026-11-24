<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Contracts;

use CommuniQate\Workspaces\ChannelType;

interface MessageSender
{
    public function sendOnChannel(ChannelType $channel, string $channelId, string $to, #[\SensitiveParameter] array $content, ?string $idempotencyKey = null): array;

    public function sendEmail(string $channelId, string $to, #[\SensitiveParameter] array $email, ?string $idempotencyKey = null): array;
}
