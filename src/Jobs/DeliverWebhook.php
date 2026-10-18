<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Jobs;

use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Support\WebhookSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends one pending delivery. Retries are new deliveries queued with a delay by
 * `WebhookSender` (each attempt is logged), so the job itself is tried once.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $deliveryId) {}

    public function handle(WebhookSender $sender): void
    {
        $delivery = WebhookDelivery::query()->with(['endpoint', 'event'])->find($this->deliveryId);

        // Deleted (endpoint removed, pruned) or already sent: nothing to do.
        if ($delivery === null || ! $delivery->isPending()) {
            return;
        }

        $sender->send($delivery);
    }
}
