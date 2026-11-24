<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces;

/** Flat operationId methods retained for existing applications. */
trait DirectOperations
{
    public function listChannels(array $query = []): array
    {
        return $this->channels()->list($query);
    }

    public function iterateChannels(array $query = []): \Generator
    {
        return $this->channels()->iterate($query);
    }

    public function showChannel(string $channel, array $query = []): array
    {
        return $this->channels()->get($channel, $query);
    }

    public function listContacts(array $query = []): array
    {
        return $this->contacts()->list($query);
    }

    public function iterateContacts(array $query = []): \Generator
    {
        return $this->contacts()->iterate($query);
    }

    public function createContact(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->contacts()->create($body, $idempotencyKey, $query);
    }

    public function showContact(string $contact, array $query = []): array
    {
        return $this->contacts()->get($contact, $query);
    }

    public function updateContact(string $contact, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->contacts()->update($contact, $body, $query);
    }

    public function putContactMetaFieldValue(string $contact, string $metaField, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->contacts()->setMetaField($contact, $metaField, $body, $query);
    }

    public function addContactToList(string $contact, string $contactList, ?string $idempotencyKey = null, array $query = []): void
    {
        $this->contacts()->addToList($contact, $contactList, $idempotencyKey, $query);
    }

    public function removeContactFromList(string $contact, string $contactList, array $query = []): void
    {
        $this->contacts()->removeFromList($contact, $contactList, $query);
    }

    public function listConversations(array $query = []): array
    {
        return $this->conversations()->list($query);
    }

    public function iterateConversations(array $query = []): \Generator
    {
        return $this->conversations()->iterate($query);
    }

    public function startConversation(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->conversations()->start($body, $idempotencyKey, $query);
    }

    public function showConversation(string $conversation, array $query = []): array
    {
        return $this->conversations()->get($conversation, $query);
    }

    public function updateConversation(string $conversation, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->conversations()->update($conversation, $body, $query);
    }

    /** @deprecated Use sendMessage() with an explicit recipient. */
    public function sendConversationMessage(string $conversation, #[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->messages()->sendInConversation($conversation, $body, $idempotencyKey, $query);
    }

    public function listMessages(string $conversation, array $query = []): array
    {
        return $this->messages()->list($conversation, $query);
    }

    public function iterateMessages(string $conversation, array $query = []): \Generator
    {
        return $this->messages()->iterate($conversation, $query);
    }

    public function showMessage(string $conversation, string $message, array $query = []): array
    {
        return $this->messages()->get($conversation, $message, $query);
    }

    public function sendMessage(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->messages()->send($body, $idempotencyKey, $query);
    }

    public function showTypingIndicator(string $conversation, #[\SensitiveParameter] array $body = [], ?string $idempotencyKey = null, array $query = []): void
    {
        $this->messages()->typingIndicator($conversation, $body, $idempotencyKey, $query);
    }

    public function listEmailTemplates(array $query = []): array
    {
        return $this->emailTemplates()->list($query);
    }

    public function iterateEmailTemplates(array $query = []): \Generator
    {
        return $this->emailTemplates()->iterate($query);
    }

    public function showEmailTemplate(string $emailTemplate, array $query = []): array
    {
        return $this->emailTemplates()->get($emailTemplate, $query);
    }

    public function uploadFile(string $filePath, ?string $filename = null, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->files()->upload($filePath, $filename, $idempotencyKey, $query);
    }

    public function listMetaFields(array $query = []): array
    {
        return $this->metaFields()->list($query);
    }

    public function iterateMetaFields(array $query = []): \Generator
    {
        return $this->metaFields()->iterate($query);
    }

    public function createMetaField(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->metaFields()->create($body, $idempotencyKey, $query);
    }

    public function showMetaField(string $metaField, array $query = []): array
    {
        return $this->metaFields()->get($metaField, $query);
    }

    public function updateMetaField(string $metaField, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->metaFields()->update($metaField, $body, $query);
    }

    public function deleteMetaField(string $metaField, array $query = []): void
    {
        $this->metaFields()->delete($metaField, $query);
    }

    public function listWebhookEndpoints(array $query = []): array
    {
        return $this->webhooks()->list($query);
    }

    public function iterateWebhookEndpoints(array $query = []): \Generator
    {
        return $this->webhooks()->iterate($query);
    }

    public function createWebhookEndpoint(#[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->webhooks()->create($body, $idempotencyKey, $query);
    }

    public function showWebhookEndpoint(string $webhookSubscription, array $query = []): array
    {
        return $this->webhooks()->get($webhookSubscription, $query);
    }

    public function updateWebhookEndpoint(string $webhookSubscription, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->webhooks()->update($webhookSubscription, $body, $query);
    }

    public function deleteWebhookEndpoint(string $webhookSubscription, array $query = []): void
    {
        $this->webhooks()->delete($webhookSubscription, $query);
    }

    public function listWhatsappAccounts(array $query = []): array
    {
        return $this->whatsAppAccounts()->list($query);
    }

    public function iterateWhatsappAccounts(array $query = []): \Generator
    {
        return $this->whatsAppAccounts()->iterate($query);
    }

    public function showWhatsappAccounts(string $whatsappAccount, array $query = []): array
    {
        return $this->whatsAppAccounts()->get($whatsappAccount, $query);
    }

    public function listMessageTemplates(string $whatsappAccount, array $query = []): array
    {
        return $this->whatsAppTemplates()->list($whatsappAccount, $query);
    }

    public function iterateMessageTemplates(string $whatsappAccount, array $query = []): \Generator
    {
        return $this->whatsAppTemplates()->iterate($whatsappAccount, $query);
    }

    public function createMessageTemplate(string $whatsappAccount, #[\SensitiveParameter] array $body, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->whatsAppTemplates()->create($whatsappAccount, $body, $idempotencyKey, $query);
    }

    public function showMessageTemplate(string $whatsappAccount, string $messageTemplate, array $query = []): array
    {
        return $this->whatsAppTemplates()->get($whatsappAccount, $messageTemplate, $query);
    }

    public function updateMessageTemplate(string $whatsappAccount, string $messageTemplate, #[\SensitiveParameter] array $body, array $query = []): array
    {
        return $this->whatsAppTemplates()->update($whatsappAccount, $messageTemplate, $body, $query);
    }

    public function submitMessageTemplate(string $whatsappAccount, string $messageTemplate, ?string $idempotencyKey = null, array $query = []): array
    {
        return $this->whatsAppTemplates()->submit($whatsappAccount, $messageTemplate, $idempotencyKey, $query);
    }
}
