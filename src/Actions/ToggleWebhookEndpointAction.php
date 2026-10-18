<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Actions;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Model;

/**
 * Enables or disables the endpoint. Enabling resets the consecutive failures count (and
 * clears an automatic disable).
 */
class ToggleWebhookEndpointAction extends Action
{
    public static function make(string $name = 'toggleWebhookEndpoint'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $isEnabled = static fn (?Model $record): bool => $record instanceof WebhookEndpoint && $record->is_enabled;

        $this->label(fn (?Model $record): string => $isEnabled($record)
            ? __('alfakit-webhooks::webhooks.actions.disable.label')
            : __('alfakit-webhooks::webhooks.actions.enable.label'));
        $this->icon(fn (?Model $record): string => $isEnabled($record) ? 'power-off' : 'power');
        $this->color(fn (?Model $record): string => $isEnabled($record) ? 'danger' : 'success');
        $this->requiresConfirmation(fn (?Model $record): bool => $isEnabled($record));
        $this->modalHeading(__('alfakit-webhooks::webhooks.actions.disable.modal.heading'));
        $this->modalDescription(__('alfakit-webhooks::webhooks.actions.disable.modal.description'));
        $this->modalSubmitActionLabel(__('alfakit-webhooks::webhooks.actions.disable.label'));

        $this->authorize(static fn (?Model $record): bool => $record instanceof WebhookEndpoint && WebhooksPlugin::get()->getResourceClass()::canEdit($record));

        $this->action(static function (Model $record): void {
            if (! $record instanceof WebhookEndpoint) {
                return;
            }

            if ($record->is_enabled) {
                $record->disable();
                Notification::make()->success()->title(__('alfakit-webhooks::webhooks.actions.disable.notification'))->send();

                return;
            }

            $record->enable();
            Notification::make()->success()->title(__('alfakit-webhooks::webhooks.actions.enable.notification'))->send();
        });
    }
}
