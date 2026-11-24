<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Tests;

final class EndpointCases
{
    public static function all(): array
    {
        return [
            'listChannels' => ['channels', 'list', 'GET', '/{workspace}/channels', 200],
            'showChannel' => ['channels', 'get', 'GET', '/{workspace}/channels/{channel}', 200],
            'listContacts' => ['contacts', 'list', 'GET', '/{workspace}/contacts', 200],
            'createContact' => ['contacts', 'create', 'POST', '/{workspace}/contacts', 201],
            'showContact' => ['contacts', 'get', 'GET', '/{workspace}/contacts/{contact}', 200],
            'updateContact' => ['contacts', 'update', 'PATCH', '/{workspace}/contacts/{contact}', 200],
            'putContactMetaFieldValue' => ['contacts', 'setMetaField', 'PUT', '/{workspace}/contacts/{contact}/meta-fields/{metaField}', 200],
            'addContactToList' => ['contacts', 'addToList', 'POST', '/{workspace}/contacts/{contact}/lists/{contactList}', 204],
            'removeContactFromList' => ['contacts', 'removeFromList', 'DELETE', '/{workspace}/contacts/{contact}/lists/{contactList}', 204],
            'listConversations' => ['conversations', 'list', 'GET', '/{workspace}/conversations', 200],
            'startConversation' => ['conversations', 'start', 'POST', '/{workspace}/conversations', 201],
            'showConversation' => ['conversations', 'get', 'GET', '/{workspace}/conversations/{conversation}', 200],
            'updateConversation' => ['conversations', 'update', 'PATCH', '/{workspace}/conversations/{conversation}', 200],
            'sendConversationMessage' => ['messages', 'sendInConversation', 'POST', '/{workspace}/conversations/{conversation}/messages', 201],
            'listMessages' => ['messages', 'list', 'GET', '/{workspace}/conversations/{conversation}/messages', 200],
            'listEmailTemplates' => ['emailTemplates', 'list', 'GET', '/{workspace}/email-templates', 200],
            'showEmailTemplate' => ['emailTemplates', 'get', 'GET', '/{workspace}/email-templates/{emailTemplate}', 200],
            'uploadFile' => ['files', 'upload', 'POST', '/{workspace}/files', 201],
            'showMessage' => ['messages', 'get', 'GET', '/{workspace}/conversations/{conversation}/messages/{message}', 200],
            'sendMessage' => ['messages', 'send', 'POST', '/{workspace}/messages', 201],
            'showTypingIndicator' => ['messages', 'typingIndicator', 'POST', '/{workspace}/conversations/{conversation}/typing-indicator', 204],
            'listMetaFields' => ['metaFields', 'list', 'GET', '/{workspace}/meta-fields', 200],
            'createMetaField' => ['metaFields', 'create', 'POST', '/{workspace}/meta-fields', 201],
            'showMetaField' => ['metaFields', 'get', 'GET', '/{workspace}/meta-fields/{metaField}', 200],
            'updateMetaField' => ['metaFields', 'update', 'PATCH', '/{workspace}/meta-fields/{metaField}', 200],
            'deleteMetaField' => ['metaFields', 'delete', 'DELETE', '/{workspace}/meta-fields/{metaField}', 204],
            'listWebhookEndpoints' => ['webhooks', 'list', 'GET', '/{workspace}/webhooks', 200],
            'createWebhookEndpoint' => ['webhooks', 'create', 'POST', '/{workspace}/webhooks', 201],
            'showWebhookEndpoint' => ['webhooks', 'get', 'GET', '/{workspace}/webhooks/{webhookSubscription}', 200],
            'updateWebhookEndpoint' => ['webhooks', 'update', 'PATCH', '/{workspace}/webhooks/{webhookSubscription}', 200],
            'deleteWebhookEndpoint' => ['webhooks', 'delete', 'DELETE', '/{workspace}/webhooks/{webhookSubscription}', 204],
            'listWhatsappAccounts' => ['whatsAppAccounts', 'list', 'GET', '/{workspace}/whatsapp-accounts', 200],
            'showWhatsappAccounts' => ['whatsAppAccounts', 'get', 'GET', '/{workspace}/whatsapp-accounts/{whatsappAccount}', 200],
            'listMessageTemplates' => ['whatsAppTemplates', 'list', 'GET', '/{workspace}/whatsapp-accounts/{whatsappAccount}/message-templates', 200],
            'createMessageTemplate' => ['whatsAppTemplates', 'create', 'POST', '/{workspace}/whatsapp-accounts/{whatsappAccount}/message-templates', 201],
            'showMessageTemplate' => ['whatsAppTemplates', 'get', 'GET', '/{workspace}/whatsapp-accounts/{whatsappAccount}/message-templates/{messageTemplate}', 200],
            'updateMessageTemplate' => ['whatsAppTemplates', 'update', 'PATCH', '/{workspace}/whatsapp-accounts/{whatsappAccount}/message-templates/{messageTemplate}', 200],
            'submitMessageTemplate' => ['whatsAppTemplates', 'submit', 'POST', '/{workspace}/whatsapp-accounts/{whatsappAccount}/message-templates/{messageTemplate}/submit', 202],
        ];
    }
}
