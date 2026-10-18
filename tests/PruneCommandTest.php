<?php

declare(strict_types=1);

use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\Tests\TestCase;
use AlfaSix\AlfakitWebhooks\Webhooks;
use Illuminate\Support\Facades\Http;

use function AlfaSix\AlfakitWebhooks\Tests\endpoint;
use function AlfaSix\AlfakitWebhooks\Tests\plugin;
use function Pest\Laravel\artisan;

uses(TestCase::class);

require_once __DIR__.'/helpers.php';

it('prunes deliveries and events older than the retention period', function (): void {
    Http::fake();
    endpoint();

    $this->travelTo(now()->subDays(40));
    Webhooks::dispatch('order.paid', ['id' => 'old']);
    $this->travelBack();

    $this->travelTo(now()->subDays(10));
    Webhooks::dispatch('order.paid', ['id' => 'recent']);
    $this->travelBack();

    // A pending delivery is kept, its job is still queued.
    WebhookDelivery::query()->oldest('id')->first()?->replicate()->fill(['status' => DeliveryStatus::Pending])->forceFill(['created_at' => now()->subDays(50)])->save();

    artisan('alfakit-webhooks:prune')->assertSuccessful()->expectsOutputToContain('Pruned 1 webhook deliveries');

    expect(WebhookDelivery::query()->count())->toBe(2)
        ->and(WebhookEvent::query()->count())->toBe(2);

    artisan('alfakit-webhooks:prune', ['--days' => 5])->assertSuccessful();

    expect(WebhookDelivery::query()->pluck('status')->all())->toBe([DeliveryStatus::Pending]);

    plugin()->pruneAfterDays(90);
    expect(plugin()->getPruneAfterDays())->toBe(90);
});
