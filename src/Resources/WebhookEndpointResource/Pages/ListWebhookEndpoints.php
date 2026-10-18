<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages;

use AlfaSix\Alfakit\Actions\CreateAction;
use AlfaSix\Alfakit\Resources\Pages\ListRecords;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource;

class ListWebhookEndpoints extends ListRecords
{
    protected static string $resource = WebhookEndpointResource::class;

    /**
     * The resource registered by the plugin (a subclass given to `resourceClass()`).
     */
    public static function getResource(): string
    {
        return WebhookEndpointResource::getPlugin()->getResourceClass();
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->icon('plus')];
    }
}
