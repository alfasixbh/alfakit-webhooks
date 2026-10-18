<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Notifications;

use AlfaSix\Alfakit\Mail\Concerns\UsesEditableTemplate;
use AlfaSix\Alfakit\Mail\MailMessage;
use AlfaSix\Alfakit\Mail\MailTheme;
use AlfaSix\Alfakit\Support\Credit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to the `notifyOnDisable()` users when an endpoint is disabled after failing
 * (`WebhooksPlugin::mailOnDisable()`), in the AlfaKit mail theme and editable
 * (`webhooks.endpoint-disabled`).
 */
class EndpointDisabledMail extends Notification
{
    use Queueable;
    use UsesEditableTemplate;

    public const TEMPLATE = 'webhooks.endpoint-disabled';

    public function __construct(
        public readonly string $endpointUrl,
        public readonly int $failures,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): BaseMailMessage
    {
        return $this->templateMailMessage(Credit::addToMail((new MailMessage)
            ->error()
            ->subject(__('alfakit-webhooks::webhooks.notifications.disabled.title'))
            ->greeting(__('alfakit-webhooks::webhooks.notifications.disabled.title'))
            ->line(__('alfakit-webhooks::webhooks.notifications.disabled.body', ['url' => $this->endpointUrl, 'count' => $this->failures]))), Credit::emailLine());
    }

    public function templateKey(): string
    {
        return self::TEMPLATE;
    }

    public function templateData(): array
    {
        return [
            'url' => $this->endpointUrl,
            'failures' => (string) $this->failures,
            'app' => MailTheme::brand()->name,
        ];
    }
}
