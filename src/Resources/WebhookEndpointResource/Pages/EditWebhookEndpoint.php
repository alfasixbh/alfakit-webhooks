<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages;

use AlfaSix\Alfakit\Actions\ActionGroup;
use AlfaSix\Alfakit\Actions\DeleteAction;
use AlfaSix\Alfakit\Resources\Pages\EditRecord;
use AlfaSix\AlfakitWebhooks\Actions\RotateWebhookSecretAction;
use AlfaSix\AlfakitWebhooks\Actions\SendTestWebhookAction;
use AlfaSix\AlfakitWebhooks\Actions\ToggleWebhookEndpointAction;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource;
use Illuminate\Database\Eloquent\Model;

/**
 * The endpoint's settings, its deliveries log (relation manager) and the test, rotate
 * secret, enable / disable and delete actions.
 */
class EditWebhookEndpoint extends EditRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    public static function getResource(): string
    {
        return WebhookEndpointResource::getPlugin()->getResourceClass();
    }

    protected function getHeaderActions(): array
    {
        return [
            SendTestWebhookAction::make(),
            ActionGroup::make([
                RotateWebhookSecretAction::make(),
                ToggleWebhookEndpointAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // Hidden from serialisation (encrypted), so filled explicitly for the editor.
        if ($record instanceof WebhookEndpoint) {
            $data['headers'] = $record->headers ?? [];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return WebhookEndpointResource::normalizeFormData($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $wasEnabled = $record instanceof WebhookEndpoint && $record->is_enabled;

        $record->fill($data);

        // Turning the endpoint back on clears an automatic disable.
        if ($record instanceof WebhookEndpoint && ! $wasEnabled && $record->is_enabled) {
            $record->forceFill(['consecutive_failures' => 0, 'disabled_at' => null, 'disabled_reason' => null]);
        }

        // Turning it off by hand records when.
        if ($record instanceof WebhookEndpoint && $wasEnabled && ! $record->is_enabled) {
            $record->forceFill(['disabled_at' => now(), 'disabled_reason' => null]);
        }

        $record->save();

        return $record;
    }
}
