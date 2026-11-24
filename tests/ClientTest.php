<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Tests;

use CommuniQate\Workspaces\Client;
use CommuniQate\Workspaces\Exception\ApiException;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/EndpointCases.php';

final class ClientTest extends TestCase
{
    private const WORKSPACE_ID = '019ad5a2-7740-7080-9dd8-47a000000001';

    private array $history = [];

    private function client(Response ...$responses): Client
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        return new Client('test-key', self::WORKSPACE_ID, new Guzzle(['handler' => $handler]));
    }

    public static function operations(): iterable
    {
        foreach (EndpointCases::all() as $operation => [$resource, $action, $verb, $path, $status]) {
            foreach ([true, false] as $grouped) {
                yield $operation.($grouped ? ' resource' : ' direct') => [$operation, $resource, $action, $verb, $path, $status, $grouped];
            }
        }
    }

    #[DataProvider('operations')]
    public function testEndpoint(string $operation, string $resource, string $action, string $verb, string $path, int $code, bool $grouped): void
    {
        $client = $this->client(new Response($code, [], $code === 204 ? '' : '{"id":"accepted"}'));
        $args = [];
        $path = str_replace('{workspace}', self::WORKSPACE_ID, $path);
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        foreach ($matches[1] as $index => $name) {
            $args[$name] = sprintf('019ad5a2-7740-7080-9dd8-%012d', $index + 10);
            $path = str_replace('{'.$name.'}', rawurlencode($args[$name]), $path);
        }
        $multipart = $operation === 'uploadFile';
        if ($multipart) {
            $args['filePath'] = __FILE__;
            $args['filename'] = 'example.txt';
        } else {
            $body = match ($operation) {
                'sendMessage' => ['channel_id' => 'channel', 'to' => '+31600000000', 'type' => 'text', 'text' => 'hello'],
                'startConversation' => ['channel_id' => 'channel', 'identifier' => '+31600000000', 'message' => ['type' => 'text', 'text' => 'hello']],
                'putContactMetaFieldValue' => ['value' => null],
                'createMetaField' => ['name' => 'Order', 'type' => 'text'],
                'createWebhookEndpoint' => ['delivery_url' => 'https://example.org/events', 'events' => [['event' => 'messages.inbound', 'version' => 'v1']]],
                'createMessageTemplate' => ['name' => 'news', 'language' => 'en', 'category' => 'utility', 'components' => [['type' => 'body', 'text' => 'Update']]],
                'createContact', 'updateContact' => ['email' => 'a@example.com'],
                'updateMessageTemplate' => ['name' => 'news'],
                'updateWebhookEndpoint' => ['is_enabled' => false],
                'updateMetaField' => ['name' => 'Order number'],
                'updateConversation' => ['closed' => true],
                'sendConversationMessage' => ['type' => 'text', 'text' => 'Hello'],
                'showTypingIndicator' => [],
                default => null,
            };
            if ($body !== null) {
                $args['body'] = $body;
            }
        }
        if ($verb === 'POST') {
            $args['idempotencyKey'] = 'stable-key';
        }
        $result = $grouped ? $client->$resource()->$action(...$args) : $client->$operation(...$args);
        self::assertSame($code === 204 ? null : ['id' => 'accepted'], $result);
        $req = $this->history[0]['request'];
        self::assertSame($verb, $req->getMethod());
        self::assertSame(Client::BASE_URL.$path, (string) $req->getUri());
        self::assertSame('Bearer test-key', $req->getHeaderLine('Authorization'));
        self::assertSame('application/json', $req->getHeaderLine('Accept'));
        self::assertSame($verb === 'POST' ? 'stable-key' : '', $req->getHeaderLine('Idempotency-Key'));
        if ($multipart) {
            self::assertStringStartsWith('multipart/form-data; boundary=', $req->getHeaderLine('Content-Type'));
            self::assertStringContainsString('name="file"; filename="example.txt"', (string) $req->getBody());
            self::assertStringContainsString(file_get_contents($args['filePath']), (string) $req->getBody());
        } elseif (isset($args['body'])) {
            self::assertSame($args['body'], json_decode((string) $req->getBody(), true));
        } else {
            self::assertSame('', (string) $req->getBody());
        }
    }

    public static function pages(): iterable
    {
        yield 'number' => ['iterateContacts', 'page', ['current_page' => 1], ['current_page' => 2], '2'];
        yield 'cursor' => ['iterateConversations', 'cursor', ['next_cursor' => 'abc+='], ['next_cursor' => null], 'abc+='];
    }

    #[DataProvider('pages')]
    public function testPaginationRetainsQueryAndOrigin(string $iterator, string $key, array $first, array $last, string $token): void
    {
        $page = fn ($id, $meta, $next) => new Response(200, [], json_encode(['data' => [['id' => $id]], 'meta' => $meta, 'links' => ['next' => $next]]));
        $client = $this->client($page('a', $first, 'https://untrusted.example/'), $page('b', $last, null));
        $rows = $client->$iterator(['per_page' => 17, 'filter' => ['search' => 'Anne & Jan'], 'sort' => '-created_at']);
        self::assertSame([], $this->history);
        self::assertSame([['id' => 'a'], ['id' => 'b']], iterator_to_array($rows));
        $uri = $this->history[1]['request']->getUri();
        self::assertSame('api.communiqate.nl', $uri->getHost());
        parse_str($uri->getQuery(), $query);
        self::assertSame(['per_page' => '17', 'filter' => ['search' => 'Anne & Jan'], 'sort' => '-created_at', $key => $token], $query);
    }

    public function testResourceIdRequiresAStringInStrictCode(): void
    {
        $this->expectException(\TypeError::class);
        $this->client()->metaFields()->update(123, ['name' => 'Order number']);
    }

    public function testErrorsPreserveStatusCodeRetryAfterAndFieldMessages(): void
    {
        $errors = ['email.data.code' => ['Required']];
        $client = $this->client(new Response(422, [], json_encode(['error' => ['message' => 'Invalid', 'code' => 'validation_failed', 'errors' => $errors]])),
            new Response(429, ['Retry-After' => '30'], '{"error":{"code":"rate_limited","message":"Wait"}}'));
        try {
            $client->listContacts();
            self::fail();
        } catch (ApiException $e) {
            self::assertSame([422, 'validation_failed', $errors], [$e->status, $e->errorCode, $e->errors]);
        }
        try {
            $client->listContacts();
            self::fail();
        } catch (ApiException $e) {
            self::assertSame([429, 'rate_limited', '30'], [$e->status, $e->errorCode, $e->retryAfter]);
        }
        self::assertCount(2, $this->history);
    }

    public static function invalidMail(): iterable
    {
        yield 'HTML requires subject' => [['html' => '<b>x</b>']];
        yield 'HTML byte ceiling' => [['subject' => 'x', 'html' => str_repeat('é', 250001)]];
        yield 'template and HTML' => [['subject' => 'x', 'html' => 'x', 'email_template_id' => 'id']];
        yield 'strict boolean' => [['text' => 'x', 'sensitive' => 'true']];
        yield 'sensitive copy' => [['subject' => 'x', 'text' => 'x', 'sensitive' => true, 'bcc' => ['a@example.com']]];
        yield 'sensitive attachment' => [['subject' => 'x', 'text' => 'x', 'sensitive' => true, 'attachments' => ['id']]];
        yield 'data requires template' => [['text' => 'x', 'data' => ['code' => self::WORKSPACE_ID]]];
        yield 'subject newline' => [['subject' => "a\nb", 'text' => 'x']];
    }

    #[DataProvider('invalidMail')]
    public function testInvalidEmailFailsBeforeHttp(array $email): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->client()->sendEmail('channel', 'a@example.com', $email);
    }

    public function testSensitiveIsRefusedOnReplyEndpointEvenWhenFalse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->client()->sendConversationMessage('conversation', ['type' => 'email', 'email' => ['sensitive' => false, 'text' => 'hello']]);
    }

    public function testWhatsAppHelpersAndSensitiveTemplateUseIdentifierEndpoint(): void
    {
        $client = $this->client(...array_fill(0, 3, new Response(201, [], '{"message_id":"m"}')));
        $client->sendWhatsAppText('wa', '+31600000000', 'Hello');
        $client->sendWhatsAppTemplate('wa', '+31600000000', ['message_template' => 'login', 'otp_code' => '01234']);
        $client->sendEmail('mail', 'a@example.com', ['sensitive' => true, 'email_template_id' => 'tpl', 'data' => ['code' => '01234']], 'login-attempt');
        $bodies = array_map(fn ($call) => json_decode((string) $call['request']->getBody(), true), $this->history);
        self::assertSame(['text', 'whatsapp_template', 'email'], array_column($bodies, 'type'));
        self::assertSame('01234', $bodies[1]['whatsapp_template']['otp_code']);
        self::assertTrue($bodies[2]['email']['sensitive']);
        self::assertSame('login-attempt', $this->history[2]['request']->getHeaderLine('Idempotency-Key'));
    }
}
