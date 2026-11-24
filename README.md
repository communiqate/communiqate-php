# CommuniQate Workspaces SDK for PHP

Send email and WhatsApp messages, reply on Instagram or Messenger, manage workspace resources and verify incoming webhooks. The SDK covers all 38 operations of the public Workspaces v1 API. For Blade Mailables, Laravel Notifications and a facade, install the [Laravel integration](https://github.com/communiqate/communiqate-laravel).

## Installation

Run this command in your application directory to install the SDK from Packagist.

```sh
composer require communiqate/communiqate-php
```

The SDK supports PHP 8.3 through 8.6 and requires the cURL, JSON and mbstring extensions. Its default HTTP transport uses cURL and has no third-party Composer runtime dependencies.

Create a workspace key under **Settings → API & webhooks**. Sending requires `messages:write`. To look up a sending channel, use `channels:read` and take the channel’s `id` from `$api->channels()->list()`. CommuniQate resource IDs, including workspace, channel and meta-field IDs, are UUIDv7 strings. A connected account or mailbox can have a different ID. The recipient identifier follows the selected channel's addressing rules.

## Send your first message

```php
use CommuniQate\Workspaces\Client;

require __DIR__.'/vendor/autoload.php';

$api = new Client(
    apiKey: (string) getenv('COMMUNIQATE_API_KEY'),
    workspace: (string) getenv('COMMUNIQATE_WORKSPACE'),
);

$ordersChannelId = '019ad5a2-7740-7080-9dd8-47a000000003'; // Replace with your email channel ID.
$draft = $api->email([
    'subject' => 'Order 1042 is packed',
    'html' => '<p>Order <strong>1042</strong> is ready to ship.</p>',
    'text' => 'Order 1042 is ready to ship.',
])->to('buyer@example.com')->channel($ordersChannelId);

$accepted = $draft->send(idempotencyKey: 'order-1042-packed-email');
$messageId = $accepted['message_id'];
```

HTTP 201 means CommuniQate accepted the message. The result includes the message, conversation, contact and channel IDs. Follow delivery through `messages.status_updated` or call `$api->messages()->get($conversationId, $messageId)`.

### Build for a workspace and channel

Use `forWorkspace($workspaceId)` with that workspace's configured API key, or supply its key explicitly. The returned client keeps the selected workspace and credentials for subsequent calls. This also works with `new Client()` when your application supplies both values per send.

```php
use CommuniQate\Workspaces\ChannelType;

$workspace = $api->forWorkspace($workspaceId, apiKey: $workspaceKey);
$reply = $workspace->text('Yes, that size is in stock.')
    ->to($instagramScopedUserId)
    ->channel($instagramChannelId, ChannelType::Instagram);

$payload = $reply->build(); // Inspect the API payload locally.
$accepted = $reply->send(idempotencyKey: $replyId);
```

`channel($id)` selects the sending workspace channel. Pass a `ChannelType` as the optional second argument to check that type's content rules locally. Each fluent method returns a new draft, so you can reuse the same content for another recipient. Only `send()` calls the API. Pass the same idempotency key when retrying an intended send.

| Channel | Example |
| --- | --- |
| WhatsApp | `$workspace->whatsAppTemplate('order_shipped', $components)->to($phone)->channel($whatsAppChannelId)->send()` |
| Instagram | `$workspace->image(link: $imageUrl)->to($instagramScopedUserId)->channel($instagramChannelId)->send()` |
| Messenger | `$workspace->text('Ready for pickup.')->to($pageScopedUserId)->channel($messengerChannelId)->send()` |
| Email | `$workspace->email(['subject' => 'Receipt', 'text' => 'Payment received.'])->to($address)->channel($emailChannelId)->send()` |

Start a media message with `image()`, `video()`, `audio()` or `document()`. Supply a public HTTPS `link:` or an uploaded `fileId:`. A document can also take a `filename:`. For WhatsApp you can also use `location()`, `whatsAppSticker()`, `whatsAppInteractive($payload)`, or `whatsAppTemplate('login', otpCode: $code)`. The `email()` method accepts the API email fields, including templates, copies, uploaded attachment IDs and sensitive mode.

Use `replyTo($messageId)` to reference a message and `scheduleFor($when)` for a future ISO 8601 timestamp or a `DateTimeInterface`. On Instagram and Messenger, `asHumanAgent()` marks a reply sent by a human agent. Channel service windows and restrictions still apply.

### Email content, templates and attachments

Rendered HTML needs a single-line subject of up to 255 characters. The API cleans the HTML, accepts up to 500,000 UTF-8 bytes and derives plain text if you omit `text`. Use absolute HTTPS URLs for images. To use a workspace template, supply `email_template_id` and the template's `data` values in place of the rendered body.

For a login link, password reset or one-time code, set `sensitive` to the boolean `true`. Send to one explicit email address with a subject and body, or use a transactional template:

```php
$api->sendEmail($emailChannel, $userEmail, [
    'sensitive' => true,
    'email_template_id' => $loginTemplateId,
    'data' => ['loginLink' => $oneTimeUrl],
], idempotencyKey: $loginAttemptId);
```

Sensitive mail starts a new transactional thread without open or click tracking. The inbox, API and webhooks display a placeholder. The encrypted delivery copy expires after at most 15 minutes and is removed earlier on provider acceptance or final failure. Your application controls the token's expiry and single use. Copies, attachments, scheduling, reply references and marketing templates are incompatible with sensitive mail.

Ordinary email supports up to ten cc addresses, ten bcc addresses and ten attachments totaling at most 25 MB. Upload each file with `$file = $api->files()->upload($pdfPath, idempotencyKey: $uploadKey)`, then pass `'attachments' => [$file['id']]` in the email payload.

### WhatsApp, Instagram and Messenger

```php
use CommuniQate\Workspaces\ChannelType;

// A free-form WhatsApp reply within the service window.
$api->sendWhatsAppText($whatsAppChannel, $phone, 'Which size would you like?', $replyId);

// An approved WhatsApp template can start a conversation outside the 24-hour window.
$api->sendWhatsAppTemplate($whatsAppChannel, $phone, [
    'message_template' => 'order_shipped',
    'components' => [[
        'type' => 'body',
        'parameters' => [['type' => 'text', 'text' => '1042']],
    ]],
], idempotencyKey: 'order-1042-shipped-whatsapp');

$api->sendOnChannel(ChannelType::Instagram, $instagramChannel, $instagramScopedId,
    ['type' => 'text', 'text' => 'Yes, that size is in stock.'], $instagramReplyId);
```

Use `ChannelType::Messenger` and a page-scoped user ID for Messenger. Both social channels reply to known contacts who wrote first. Their ordinary reply window is 24 hours. Instagram accepts up to 1,000 UTF-8 bytes of text and Messenger up to 2,000. Both also accept image, video, audio and document messages.

## API resources

See the [Workspaces API reference](https://api.communiqate.nl/docs/workspaces-api/v1) for endpoint details.

| Client accessor | Methods | Scope family |
| --- | --- | --- |
| `channels()` | `list`, `iterate`, `get` | `channels:read` |
| `contacts()` | `list`, `iterate`, `get`, `create`, `update`, `setMetaField`, `addToList`, `removeFromList` | `contacts:read/write` |
| `conversations()` | `list`, `iterate`, `get`, `start`, `update` | `conversations:read/write` |
| `messages()` | `list`, `iterate`, `get`, `send`, `typingIndicator`, legacy `sendInConversation` | `messages:read/write` |
| `files()` | `upload` | `messages:write` |
| `metaFields()` | `list`, `iterate`, `get`, `create`, `update`, `delete` | `meta_fields:read/write` |
| `emailTemplates()` | `list`, `iterate`, `get` | `templates:read` |
| `whatsAppAccounts()` | `list`, `iterate`, `get` | `channels:read` |
| `whatsAppTemplates()` | `list`, `iterate`, `get`, `create`, `update`, `submit` | `templates:read/write` |
| `webhooks()` | `list`, `iterate`, `get`, `create`, `update`, `delete` | `webhooks:read/write` |

Each resource documents the exact scope on the method. A write scope includes read access for the same family. Following a file preview or download URL requires `files:read`. Existing direct methods such as `listContacts()` and `sendMessage()` remain available.

`list()` returns one page with `data`, `meta` and `links`. Use `iterate()` for a lazy stream that preserves filters, sort and includes. Conversations and messages use cursors. Other lists use page numbers. The API caps `per_page` at 100.

```php
foreach ($api->contacts()->iterate(['filter' => ['search' => 'Smith'], 'per_page' => 100]) as $contact) {
    // Process one contact.
}
```

### Errors and retries

An `ApiException` exposes `status`, `errorCode`, field-level `errors` and `retryAfter`. Invalid local input raises `InvalidArgumentException`. cURL connection failures raise `NetworkException`. Channel eligibility, scopes and workspace-specific rules are enforced by the API.

Every POST accepts `idempotencyKey`. Persist one key per intended operation. If a request times out, retry with the same path, key and body within 24 hours. A conflict can mean the original request is still running. After `idempotency_attempt_failed`, check the outcome before issuing another write.

## Receiving webhooks

Register an HTTPS endpoint in **Settings → API & webhooks**, or create it through the SDK with `webhooks:write`. Store the returned `secret` immediately. Each subscription has its own secret.

```php
use CommuniQate\Workspaces\Webhooks\WebhookEvent;

$endpoint = $api->webhooks()->create([
    'delivery_url' => 'https://example.com/api/webhooks/communiqate',
    'events' => [
        ['event' => WebhookEvent::MessagesInbound->value, 'version' => 'v1'],
        ['event' => WebhookEvent::MessagesStatusUpdated->value, 'version' => 'v1'],
    ],
], idempotencyKey: $registrationId);
```

CommuniQate signs the original body bytes with HMAC-SHA256. Pass them unchanged with the hexadecimal `Signature` header:

```php
use CommuniQate\Workspaces\Exception\InvalidWebhookSignature;
use CommuniQate\Workspaces\Webhooks\WebhookVerifier;

$verifier = new WebhookVerifier((string) getenv('COMMUNIQATE_WEBHOOK_SECRET'));
try {
    $delivery = $verifier->verify(file_get_contents('php://input'), $_SERVER['HTTP_SIGNATURE'] ?? null);
} catch (InvalidWebhookSignature) {
    http_response_code(401);
    return;
} catch (JsonException | UnexpectedValueException) {
    http_response_code(422);
    return;
}

// Persist or enqueue the verified delivery before acknowledging it.
http_response_code(204);
```

The seven v1 events are `messages.inbound`, `messages.outbound`, `messages.status_updated`, `templates.created`, `templates.status_changed`, `templates.category_changed` and `templates.deleted`. Inspect `$delivery->event`, `data`, `workspaceId`, `subscriptionId` and `isTest`. Respond within ten seconds. Retries reuse the same delivery ID. Deduplicate on `$delivery->id` and process status updates by their event timestamp.

## Dependency injection

In Symfony, register the client as a service:

```yaml
# config/services.yaml
services:
    CommuniQate\Workspaces\Client:
        arguments:
            $apiKey: '%env(COMMUNIQATE_API_KEY)%'
            $workspace: '%env(COMMUNIQATE_WORKSPACE)%'
    CommuniQate\Workspaces\Contracts\MessageSender: '@CommuniQate\Workspaces\Client'
```

Application services that only send messages can depend on `MessageSender`. To override HTTP, pass a `CommuniQate\Workspaces\Http\Transport` to the `http` constructor argument. A PSR-18 client is also supported when `guzzlehttp/psr7` is installed. The default cURL transport uses a ten-second connection timeout and a 30-second total timeout.
