<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

use InvalidArgumentException;

enum ChannelType: string
{
    case WhatsApp = 'whatsapp';
    case Instagram = 'instagram';
    case Messenger = 'messenger';
    case Email = 'email';

    public function validateMessage(#[\SensitiveParameter] array $message): void
    {
        $mediaAndText = ['text', 'image', 'video', 'audio', 'document'];
        $allowed = match ($this) {
            self::WhatsApp => [...$mediaAndText, 'location', 'whatsapp_sticker', 'whatsapp_template', 'whatsapp_interactive'],
            self::Instagram, self::Messenger => $mediaAndText,
            self::Email => ['email', 'text'],
        };
        if (!in_array($message['type'] ?? null, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported message type for {$this->value}.");
        }
        $byteLimit = match ($this) {
            self::Instagram => 1000,
            self::Messenger => 2000,
            default => null,
        };
        if ($byteLimit !== null && $message['type'] === 'text' && is_string($message['text'] ?? null)
            && strlen($message['text']) > $byteLimit) {
            throw new InvalidArgumentException("Text on {$this->value} exceeds the limit of $byteLimit UTF-8 bytes.");
        }
    }
}
