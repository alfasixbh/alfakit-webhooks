<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Testing;

use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\WebhookManager;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use PHPUnit\Framework\Assert;

/**
 * `Webhooks::fake()`: records `dispatch()` calls and sends nothing.
 *
 * ```php
 * Webhooks::fake();
 * // …
 * Webhooks::assertDispatched('order.paid', fn (array $payload) => $payload['id'] === $order->id);
 * ```
 */
class WebhooksFake extends WebhookManager
{
    /** @var array<int, array{event: string, payload: array<mixed>, tenant: Model|false|null}> */
    protected array $dispatched = [];

    public function __construct(protected WebhookManager $manager)
    {
        // Model events of the registered plugins are recorded too.
        $this->plugins = $manager->plugins;
        $this->observed = $manager->observed;
    }

    public function dispatch(string $event, array|Arrayable|JsonSerializable $payload = [], Model|false|null $tenant = null, ?string $id = null): ?WebhookEvent
    {
        $this->dispatched[] = ['event' => $event, 'payload' => $this->normalizePayload($payload), 'tenant' => $tenant];

        return null;
    }

    public function sendTest(WebhookEndpoint $endpoint): WebhookDelivery
    {
        return $this->manager->sendTest($endpoint);
    }

    public function events(): array
    {
        return $this->manager->events();
    }

    public function endpointsFor(string $event, ?Model $tenant): Collection
    {
        return $this->manager->endpointsFor($event, $tenant);
    }

    /**
     * @param  Closure(array<mixed>, Model|false|null): bool|null  $callback
     */
    public function assertDispatched(string $event, ?Closure $callback = null): void
    {
        Assert::assertNotEmpty($this->matching($event, $callback), "The webhook event [{$event}] was not dispatched.");
    }

    public function assertDispatchedTimes(string $event, int $times = 1): void
    {
        $count = count($this->matching($event));

        Assert::assertSame($times, $count, "The webhook event [{$event}] was dispatched {$count} times instead of {$times}.");
    }

    /**
     * @param  Closure(array<mixed>, Model|false|null): bool|null  $callback
     */
    public function assertNotDispatched(string $event, ?Closure $callback = null): void
    {
        Assert::assertEmpty($this->matching($event, $callback), "The webhook event [{$event}] was dispatched.");
    }

    public function assertNothingDispatched(): void
    {
        Assert::assertEmpty($this->dispatched, count($this->dispatched).' webhook events were dispatched.');
    }

    /**
     * @return array<int, array{event: string, payload: array<mixed>, tenant: Model|false|null}>
     */
    public function dispatched(?string $event = null): array
    {
        return $event === null ? $this->dispatched : $this->matching($event);
    }

    /**
     * @return array<int, array{event: string, payload: array<mixed>, tenant: Model|false|null}>
     */
    protected function matching(string $event, ?Closure $callback = null): array
    {
        return array_values(array_filter(
            $this->dispatched,
            static fn (array $dispatched): bool => $dispatched['event'] === $event && ($callback === null || $callback($dispatched['payload'], $dispatched['tenant'])),
        ));
    }
}
