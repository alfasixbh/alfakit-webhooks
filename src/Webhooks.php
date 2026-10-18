<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks;

use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\Testing\WebhooksFake;
use Illuminate\Support\Facades\Facade;

/**
 * ```php
 * Webhooks::dispatch('order.paid', ['id' => $order->id, 'total' => $order->total]);
 * Webhooks::dispatch('invoice.sent', $invoice->toArray(), tenant: $invoice->team);
 * ```
 *
 * @method static WebhookEvent|null dispatch(string $event, array<mixed>|\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|\JsonSerializable $payload = [], \Illuminate\Database\Eloquent\Model|false|null $tenant = null, ?string $id = null)
 * @method static WebhookDelivery sendTest(WebhookEndpoint $endpoint)
 * @method static WebhookDelivery replay(WebhookDelivery $delivery)
 * @method static array<string, array{label: string, description: string|null}> events()
 * @method static \Illuminate\Database\Eloquent\Collection<int, WebhookEndpoint> endpointsFor(string $event, ?\Illuminate\Database\Eloquent\Model $tenant)
 * @method static void assertDispatched(string $event, ?\Closure $callback = null)
 * @method static void assertDispatchedTimes(string $event, int $times = 1)
 * @method static void assertNotDispatched(string $event, ?\Closure $callback = null)
 * @method static void assertNothingDispatched()
 *
 * @see WebhookManager
 * @see WebhooksFake
 */
class Webhooks extends Facade
{
    /**
     * Record dispatched events instead of sending them, for your application's tests.
     */
    public static function fake(): WebhooksFake
    {
        $fake = new WebhooksFake(static::getFacadeRoot() instanceof WebhookManager ? static::getFacadeRoot() : app(WebhookManager::class));

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return WebhookManager::class;
    }
}
