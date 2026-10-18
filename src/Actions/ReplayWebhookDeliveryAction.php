<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Actions;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\WebhookManager;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Model;

/**
 * Sends the delivery's event again, with the same event id, as a new queued delivery.
 */
class ReplayWebhookDeliveryAction extends Action
{
    public static function make(string $name = 'replayWebhookDelivery'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('alfakit-webhooks::webhooks.actions.replay.label'));
        $this->icon('rotate-cw');
        $this->color('gray');
        $this->requiresConfirmation();
        $this->modalHeading(__('alfakit-webhooks::webhooks.actions.replay.modal.heading'));
        $this->modalDescription(__('alfakit-webhooks::webhooks.actions.replay.modal.description'));
        $this->modalSubmitActionLabel(__('alfakit-webhooks::webhooks.actions.replay.label'));

        $this->visible(static fn (?Model $record): bool => $record instanceof WebhookDelivery && self::canReplay($record));
        $this->authorize(static fn (?Model $record): bool => $record instanceof WebhookDelivery && self::isAuthorizedFor($record));

        $this->action(static function (Model $record): void {
            if (! $record instanceof WebhookDelivery || ! self::canReplay($record)) {
                return;
            }

            app(WebhookManager::class)->replay($record);

            Notification::make()->success()->title(__('alfakit-webhooks::webhooks.actions.replay.notification'))->send();
        });
    }

    /**
     * Test events and pending deliveries can't be replayed, nor deliveries of a disabled
     * endpoint.
     */
    public static function canReplay(WebhookDelivery $delivery): bool
    {
        return ! $delivery->is_test
            && ! $delivery->isPending()
            && $delivery->endpoint?->is_enabled === true
            && $delivery->event_id !== '';
    }

    public static function isAuthorizedFor(WebhookDelivery $delivery): bool
    {
        $endpoint = $delivery->endpoint;

        return $endpoint !== null && WebhooksPlugin::get()->getResourceClass()::canEdit($endpoint);
    }
}
