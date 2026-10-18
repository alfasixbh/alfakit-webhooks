<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Commands;

use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Console\Command;

/**
 * Deletes deliveries older than the retention period, then the events no delivery
 * references any more. Schedule it:
 *
 * ```php
 * Schedule::command('alfakit-webhooks:prune')->daily();
 * ```
 */
class PruneWebhookDeliveriesCommand extends Command
{
    protected $signature = 'alfakit-webhooks:prune
        {--days= : Keep the deliveries of the last N days (default: the plugin\'s pruneAfterDays(), 30)}';

    protected $description = 'Delete old webhook deliveries and events';

    public function handle(): int
    {
        $option = $this->option('days');
        $days = is_numeric($option) ? max(1, (int) $option) : WebhooksPlugin::get()->getPruneAfterDays();
        $cutoff = now()->subDays($days);

        $deliveries = 0;

        // Pending deliveries are kept: their job is still queued.
        WebhookDelivery::query()
            ->where('created_at', '<', $cutoff)
            ->where('status', '!=', DeliveryStatus::Pending->value)
            ->chunkById(500, static function ($chunk) use (&$deliveries): void {
                $deliveries += WebhookDelivery::query()->whereKey($chunk->modelKeys())->delete();
            });

        $events = WebhookEvent::query()
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('deliveries')
            ->delete();

        $this->components->info("Pruned {$deliveries} webhook deliveries and {$events} events older than {$days} days.");

        return self::SUCCESS;
    }
}
