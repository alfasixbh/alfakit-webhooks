<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Actions;

use AlfaSix\Alfakit\Actions\BulkAction;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\WebhookManager;
use Illuminate\Support\Collection;

/**
 * Replays the selected deliveries (skipping the ones that can't be replayed).
 */
class ReplayWebhookDeliveriesBulkAction extends BulkAction
{
    public static function make(string $name = 'replayWebhookDeliveries'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('alfakit-webhooks::webhooks.actions.replay.bulk_label'));
        $this->icon('rotate-cw');
        $this->color('gray');
        $this->requiresConfirmation();
        $this->modalHeading(__('alfakit-webhooks::webhooks.actions.replay.bulk_label'));
        $this->modalDescription(__('alfakit-webhooks::webhooks.actions.replay.modal.description'));
        $this->deselectRecordsAfterCompletion();

        $this->action(static function (Collection $records): void {
            $count = 0;

            foreach ($records as $record) {
                if ($record instanceof WebhookDelivery && ReplayWebhookDeliveryAction::canReplay($record) && ReplayWebhookDeliveryAction::isAuthorizedFor($record)) {
                    app(WebhookManager::class)->replay($record);
                    $count++;
                }
            }

            Notification::make()
                ->success()
                ->title(trans_choice('alfakit-webhooks::webhooks.actions.replay.bulk_notification', $count, ['count' => $count]))
                ->send();
        });
    }
}
