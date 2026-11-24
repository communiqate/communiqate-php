<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Webhooks;

enum WebhookEvent: string
{
    case MessagesInbound = 'messages.inbound';
    case MessagesOutbound = 'messages.outbound';
    case MessagesStatusUpdated = 'messages.status_updated';
    case TemplatesCreated = 'templates.created';
    case TemplatesStatusChanged = 'templates.status_changed';
    case TemplatesCategoryChanged = 'templates.category_changed';
    case TemplatesDeleted = 'templates.deleted';
}
