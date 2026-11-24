<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

use InvalidArgumentException;

/** Request-local rules. Channel capabilities, template state and workspace limits are checked by the API. */
final class MessageValidation
{
    public static function check(#[\SensitiveParameter] array $body, bool $explicitRecipient): void
    {
        if ($explicitRecipient) {
            self::requiredString($body['to'] ?? null, 'to', 255);
            self::requiredString($body['channel_id'] ?? null, 'channel_id', 255);
        }
        $email = $body['email'] ?? [];
        if (is_array($email) && array_key_exists('sensitive', $email)
            && (!$explicitRecipient || ($body['type'] ?? null) !== 'email' || !is_bool($email['sensitive']))) {
            throw new InvalidArgumentException('email.sensitive must be boolean and is only accepted by sendMessage for type email.');
        }
        switch ($body['type'] ?? null) {
            case 'email':
                if (!is_array($email)) {
                    throw new InvalidArgumentException('email must be an object.');
                }
                self::email($email, $body);
                break;
            case 'text':
                self::requiredString($body['text'] ?? null, 'text', 2024);
                break;
            case 'whatsapp_template':
                $template = $body['whatsapp_template'] ?? [];
                self::requiredString($template['message_template'] ?? $template['message_template_id'] ?? null, 'whatsapp_template.message_template', 255);
                if (isset($template['otp_code'])) {
                    self::requiredString($template['otp_code'], 'whatsapp_template.otp_code', 15);
                    if (preg_match('/\s/u', $template['otp_code']) || !empty($template['components'])) {
                        throw new InvalidArgumentException('Use otp_code without whitespace or components for an authentication template.');
                    }
                }
                break;
        }
    }

    private static function email(#[\SensitiveParameter] array $email, #[\SensitiveParameter] array $body): void
    {
        $template = isset($email['email_template_id']);
        $html = array_key_exists('html', $email);
        $sensitive = $email['sensitive'] ?? false;
        if ($template && ($html || isset($email['text']))) {
            throw new InvalidArgumentException('A template supplies the email body. Omit email.text and email.html.');
        }
        if ($html) {
            if (!is_string($email['html']) || trim($email['html']) === '' || strlen($email['html']) > 500_000) {
                throw new InvalidArgumentException('email.html must contain HTML of at most 500,000 UTF-8 bytes.');
            }
        }
        if ((!$template && !$html) || isset($email['text'])) {
            self::requiredString($email['text'] ?? null, 'email.text', 20_000);
        }
        if ($html || ($sensitive && !$template) || isset($email['subject'])) {
            self::requiredString($email['subject'] ?? null, 'email.subject', 255);
            if (strpbrk($email['subject'], "\r\n") !== false) {
                throw new InvalidArgumentException('email.subject must be a single line.');
            }
        }
        if ($sensitive && (!empty($email['cc']) || !empty($email['bcc']) || !empty($email['attachments'])
            || !empty($body['scheduled_for']) || !empty($body['reply_to_message_id']))) {
            throw new InvalidArgumentException('Sensitive email cannot contain copies, attachments, a schedule or a reply reference.');
        }
        foreach (['cc', 'bcc', 'attachments'] as $field) {
            if (isset($email[$field]) && (!is_array($email[$field]) || !array_is_list($email[$field]) || count($email[$field]) > 10)) {
                throw new InvalidArgumentException("email.$field must be a list of at most 10 items.");
            }
        }
        if (isset($email['data'])) {
            $data = $email['data'];
            if (!$template || (!is_array($data) && !($data instanceof \stdClass)) || count((array) $data) > 100) {
                throw new InvalidArgumentException('email.data requires an email template and an object of at most 100 keys.');
            }
            if (is_array($data) && array_is_list($data) && $data !== []) {
                throw new InvalidArgumentException('email.data must be a key-value object.');
            }
            foreach ($data as $key => $value) {
                if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', (string) $key) || !(is_null($value) || is_int($value) || is_float($value)
                    || (is_string($value) && mb_strlen($value) <= 20_000))) {
                    throw new InvalidArgumentException('Invalid email.data key or value.');
                }
            }
        }
    }

    private static function requiredString(#[\SensitiveParameter] mixed $value, string $field, int $limit): void
    {
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $limit) {
            throw new InvalidArgumentException("$field must be a non-empty string of at most $limit characters.");
        }
    }
}
