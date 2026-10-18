<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Actions;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\WebhookManager;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Sends a signed `ping` event to the endpoint right away and shows its response (status,
 * time and body) in a notification. The attempt is logged with the deliveries, marked as a
 * test; it is never retried and never counts towards auto-disabling.
 */
class SendTestWebhookAction extends Action
{
    public static function make(string $name = 'sendTestWebhook'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('alfakit-webhooks::webhooks.actions.test.label'));
        $this->icon('send');
        $this->color('gray');
        $this->requiresConfirmation();
        $this->modalIcon('send');
        $this->modalHeading(__('alfakit-webhooks::webhooks.actions.test.modal.heading'));
        $this->modalDescription(fn (?Model $record): string => __('alfakit-webhooks::webhooks.actions.test.modal.description', [
            'url' => $record instanceof WebhookEndpoint ? $record->url : '',
        ]));
        $this->modalSubmitActionLabel(__('alfakit-webhooks::webhooks.actions.test.modal.submit'));

        $this->authorize(static fn (?Model $record): bool => $record instanceof WebhookEndpoint && WebhooksPlugin::get()->getResourceClass()::canEdit($record));

        $this->action(static function (Model $record): void {
            if (! $record instanceof WebhookEndpoint) {
                return;
            }

            self::notify(app(WebhookManager::class)->sendTest($record));
        });
    }

    public static function notify(WebhookDelivery $delivery): void
    {
        $body = trim((string) $delivery->response_body);
        $excerpt = $body === '' ? null : Str::limit(preg_replace('/\s+/', ' ', $body) ?? '', 280);

        if ($delivery->succeeded()) {
            Notification::make()
                ->success()
                ->title(__('alfakit-webhooks::webhooks.actions.test.notification.success', [
                    'status' => $delivery->response_status,
                    'duration' => $delivery->duration_ms,
                ]))
                ->body($excerpt)
                ->send();

            return;
        }

        $title = $delivery->response_status !== null
            ? __('alfakit-webhooks::webhooks.actions.test.notification.failed_status', ['status' => $delivery->response_status, 'duration' => $delivery->duration_ms])
            : __('alfakit-webhooks::webhooks.actions.test.notification.failed');

        Notification::make()
            ->danger()
            ->title($title)
            ->body(trim(implode(' — ', array_filter([$delivery->error, $excerpt]))))
            ->persistent()
            ->send();
    }
}
