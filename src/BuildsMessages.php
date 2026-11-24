<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

use CommuniQate\Workspaces\Resources\Messages;
use InvalidArgumentException;

trait BuildsMessages
{
    abstract public function messages(): Messages;

    public function text(#[\SensitiveParameter] string $text): MessageBuilder
    {
        return $this->draft('text', $text);
    }

    /** Email fields such as subject, html, text, email_template_id, data, attachments and sensitive. */
    public function email(#[\SensitiveParameter] array $email): MessageBuilder
    {
        return $this->draft('email', $email);
    }

    public function image(?string $link = null, ?string $fileId = null, ?string $caption = null): MessageBuilder
    {
        return $this->media('image', $link, $fileId, $caption);
    }

    public function video(?string $link = null, ?string $fileId = null, ?string $caption = null): MessageBuilder
    {
        return $this->media('video', $link, $fileId, $caption);
    }

    public function audio(?string $link = null, ?string $fileId = null): MessageBuilder
    {
        return $this->media('audio', $link, $fileId);
    }

    public function document(?string $link = null, ?string $fileId = null, ?string $filename = null, ?string $caption = null): MessageBuilder
    {
        return $this->media('document', $link, $fileId, $caption, $filename);
    }

    public function whatsAppSticker(?string $link = null, ?string $fileId = null): MessageBuilder
    {
        return $this->media('whatsapp_sticker', $link, $fileId);
    }

    public function location(string $latitude, string $longitude, string $name, string $address): MessageBuilder
    {
        return $this->draft('location', compact('latitude', 'longitude', 'name', 'address'));
    }

    public function whatsAppTemplate(string $name, #[\SensitiveParameter] array $components = [], #[\SensitiveParameter] ?string $otpCode = null): MessageBuilder
    {
        $template = ['message_template' => $name];
        if ($components !== []) {
            $template['components'] = $components;
        }
        if ($otpCode !== null) {
            $template['otp_code'] = $otpCode;
        }

        return $this->draft('whatsapp_template', $template);
    }

    public function whatsAppInteractive(#[\SensitiveParameter] array $interactive): MessageBuilder
    {
        return $this->draft('whatsapp_interactive', $interactive);
    }

    private function media(string $type, ?string $link, ?string $fileId, ?string $caption = null, ?string $filename = null): MessageBuilder
    {
        if (($link !== null && $fileId !== null) || trim($link ?? $fileId ?? '') === '') {
            throw new InvalidArgumentException('Supply either a media link or an uploaded file ID.');
        }
        $media = $link !== null ? ['link' => $link] : ['file_id' => $fileId];
        if ($caption !== null) {
            $media['caption'] = $caption;
        }
        if ($filename !== null) {
            $media['filename'] = $filename;
        }

        return $this->draft($type, $media);
    }

    private function draft(string $type, #[\SensitiveParameter] string|array $content): MessageBuilder
    {
        return new MessageBuilder($this->messages(), ['type' => $type, $type => $content]);
    }
}
