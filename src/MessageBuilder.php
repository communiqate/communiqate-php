<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

use CommuniQate\Workspaces\Resources\Messages;
use DateTimeInterface;
use InvalidArgumentException;

/** An unsent message. Each fluent method returns a new draft. */
final class MessageBuilder
{
    private ?string $recipient = null;
    private ?string $channelId = null;
    private ?ChannelType $channelType = null;

    public function __construct(private readonly Messages $messages, #[\SensitiveParameter] private array $content) {}

    public function to(#[\SensitiveParameter] string $recipient): self
    {
        $draft = clone $this;
        $draft->recipient = $recipient;
        return $draft;
    }

    public function channel(string $channelId, ?ChannelType $type = null): self
    {
        $draft = clone $this;
        $draft->channelId = $channelId;
        $draft->channelType = $type;
        return $draft;
    }

    public function replyTo(string $messageId): self
    {
        $draft = clone $this;
        $draft->content['reply_to_message_id'] = $messageId;
        return $draft;
    }

    public function scheduleFor(DateTimeInterface|string $when): self
    {
        $draft = clone $this;
        $draft->content['scheduled_for'] = $when instanceof DateTimeInterface ? $when->format(DATE_ATOM) : $when;
        return $draft;
    }

    public function asHumanAgent(): self
    {
        $draft = clone $this;
        $draft->content['human_agent'] = true;
        return $draft;
    }

    /** Return the payload for POST /messages without making an HTTP request. */
    public function build(): array
    {
        $payload = $this->payload();
        MessageValidation::check($payload, true);
        return $payload;
    }

    public function send(?string $idempotencyKey = null): array
    {
        return $this->messages->send($this->payload(), $idempotencyKey);
    }

    private function payload(): array
    {
        $this->channelType?->validateMessage($this->content);
        if (isset($this->content['human_agent']) && $this->channelType !== null
            && !in_array($this->channelType, [ChannelType::Instagram, ChannelType::Messenger], true)) {
            throw new InvalidArgumentException('Human agent replies require an Instagram or Messenger channel.');
        }
        return ['channel_id' => $this->channelId, 'to' => $this->recipient] + $this->content;
    }
}
