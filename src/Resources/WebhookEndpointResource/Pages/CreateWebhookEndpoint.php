<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages;

use AlfaSix\Alfakit\Resources\Pages\CreateRecord;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource;
use AlfaSix\AlfakitWebhooks\Support\SecretReveal;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates the endpoint for the current tenant with a generated signing secret, then opens
 * its edit page, where the secret is shown once.
 */
class CreateWebhookEndpoint extends CreateRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    public static function getResource(): string
    {
        return WebhookEndpointResource::getPlugin()->getResourceClass();
    }

    public static function canCreateAnother(): bool
    {
        // The new secret is shown on the endpoint's edit page.
        return false;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return WebhookEndpointResource::normalizeFormData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $endpoint = new WebhookEndpoint;
        $endpoint->fill($data);
        $endpoint->setTenant(static::getResource()::getOwningTenant());

        $user = $this->getPanel()->auth()->user();

        if ($user instanceof Model) {
            $endpoint->setAttribute('created_by_type', $user->getMorphClass());
            $endpoint->setAttribute('created_by_id', (string) $user->getKey());
        }

        $endpoint->save();

        return $endpoint;
    }

    protected function afterCreate(): void
    {
        $endpoint = $this->getCreatedRecord();

        if ($endpoint instanceof WebhookEndpoint) {
            SecretReveal::flash($endpoint, $endpoint->secret);
        }
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->getCreatedRecord();
        $resource = static::getResource();

        return $record !== null && $resource::canEdit($record)
            ? $resource::getUrl('edit', ['record' => $record])
            : $resource::getUrl();
    }
}
