<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks;

use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Jobs\DeliverWebhook;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\Support\WebhookSender;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Dispatches events to the endpoints that subscribe to them (`Webhooks::dispatch()`),
 * sends test events and replays deliveries.
 */
class WebhookManager
{
    /** @var array<int, WebhooksPlugin> */
    protected array $plugins = [];

    /** @var array<string, true> `Model@event` listeners already registered */
    protected array $observed = [];

    public function registerPlugin(WebhooksPlugin $plugin): void
    {
        $this->plugins[spl_object_id($plugin)] = $plugin;

        foreach ($plugin->getModelEvents() as $model => $definition) {
            foreach ($definition['events'] as $event) {
                $key = $model.'@'.$event;

                if (isset($this->observed[$key])) {
                    continue;
                }

                $this->observed[$key] = true;

                $model::{$event}(static function (Model $record) use ($event): void {
                    app(WebhookManager::class)->dispatchModelEvent($record, $event);
                });
            }
        }
    }

    public function plugin(): WebhooksPlugin
    {
        return WebhooksPlugin::get();
    }

    /**
     * Every event the registered plugins define: name => label and description.
     *
     * @return array<string, array{label: string, description: string|null}>
     */
    public function events(): array
    {
        $events = [];

        foreach ($this->plugins as $plugin) {
            $events = [...$plugin->getEvents(), ...$events];
        }

        return $events;
    }

    /**
     * Send an event to every enabled endpoint that subscribes to it. The deliveries are
     * queued (after the current database transaction commits). Returns the stored event,
     * or null when no endpoint wanted it.
     *
     * `$tenant`: the tenant whose endpoints receive the event; null uses the panel's current
     * tenant, `false` sends it without a tenant (to endpoints without a tenant only).
     *
     * @param  array<mixed>|Arrayable<array-key, mixed>|JsonSerializable  $payload
     */
    public function dispatch(string $event, array|Arrayable|JsonSerializable $payload = [], Model|false|null $tenant = null, ?string $id = null): ?WebhookEvent
    {
        $event = trim($event);

        if ($event === '' || $event === WebhookEndpoint::ALL_EVENTS) {
            throw new InvalidArgumentException("[{$event}] is not a valid webhook event name.");
        }

        $tenant = $tenant === false ? null : ($tenant ?? alfakit()->getTenant());
        $endpoints = $this->endpointsFor($event, $tenant);

        if ($endpoints->isEmpty()) {
            return null;
        }

        $webhookEvent = $this->storeEvent($event, $this->normalizePayload($payload), $tenant, $id);

        foreach ($endpoints as $endpoint) {
            $this->queue($this->newDelivery($endpoint, $webhookEvent));
        }

        return $webhookEvent;
    }

    /**
     * The enabled endpoints that receive this event for this tenant.
     *
     * @return Collection<int, WebhookEndpoint>
     */
    public function endpointsFor(string $event, ?Model $tenant): Collection
    {
        $plugin = $this->plugin();

        return WebhookEndpoint::query()
            ->where('is_enabled', true)
            ->where(static function (Builder $query) use ($tenant, $plugin): void {
                if ($tenant === null) {
                    $query->whereNull('tenant_type');

                    return;
                }

                $query->where(static fn (Builder $query) => $query->ownedBy($tenant));

                if ($plugin->doGlobalEndpointsReceiveTenantEvents()) {
                    $query->orWhereNull('tenant_type');
                }
            })
            ->orderBy('id')
            ->get()
            ->filter(static fn (WebhookEndpoint $endpoint): bool => $endpoint->subscribesTo($event))
            ->values();
    }

    /**
     * Called by the model listeners of `WebhooksPlugin::modelEvents()`.
     */
    public function dispatchModelEvent(Model $model, string $event): ?WebhookEvent
    {
        $definition = $this->findModelDefinition($model);

        if ($definition === null || ! in_array($event, $definition['events'], true)) {
            return null;
        }

        $changes = $event === 'updated' ? array_diff(array_keys($model->getChanges()), [$model->getUpdatedAtColumn()]) : [];

        if ($event === 'updated' && $changes === []) {
            return null;
        }

        $object = method_exists($model, 'toWebhookPayload') ? $model->toWebhookPayload($event) : $model->toArray();
        $data = ['object' => $object instanceof Arrayable ? $object->toArray() : (array) $object];

        if ($event === 'updated') {
            $visible = array_diff($changes, $model->getHidden());
            $data['previous_attributes'] = array_intersect_key($model->getPrevious(), array_flip($visible));
        }

        return $this->dispatch($definition['prefix'].'.'.$event, $data, $this->resolveModelTenant($model, $definition['tenant']));
    }

    /**
     * Send a `ping` event to the endpoint now (even when it is disabled) and return the
     * delivery with the response. Test deliveries are never retried and don't count
     * towards auto-disabling.
     */
    public function sendTest(WebhookEndpoint $endpoint): WebhookDelivery
    {
        $event = $this->storeEvent(WebhooksPlugin::PING_EVENT, [
            'message' => 'This is a test event sent from the webhooks settings.',
            'endpoint' => ['id' => $endpoint->getKey(), 'url' => $endpoint->url],
        ], $endpoint->tenant_type === null ? null : $endpoint->tenant()->first());

        $delivery = $this->newDelivery($endpoint, $event, isTest: true);

        return app(WebhookSender::class)->send($delivery);
    }

    /**
     * Send the event of this delivery again (same event id) as a new delivery, queued.
     */
    public function replay(WebhookDelivery $delivery): WebhookDelivery
    {
        $endpoint = $delivery->endpoint;
        $event = $delivery->event;

        if ($endpoint === null || $event === null) {
            throw new InvalidArgumentException('The delivery has no endpoint or event any more.');
        }

        $replay = $this->newDelivery($endpoint, $event, replayOf: $delivery);
        $this->queue($replay);

        return $replay;
    }

    /**
     * Queue a pending delivery.
     */
    public function queue(WebhookDelivery $delivery, int $delay = 0): void
    {
        $plugin = $this->plugin();

        $job = DeliverWebhook::dispatch($delivery->getKey())->afterCommit();

        if ($plugin->getConnection() !== null) {
            $job->onConnection($plugin->getConnection());
        }

        if ($plugin->getQueue() !== null) {
            $job->onQueue($plugin->getQueue());
        }

        if ($delay > 0) {
            $job->delay(now()->addSeconds($delay));
        }
    }

    public function newDelivery(WebhookEndpoint $endpoint, WebhookEvent $event, int $attempt = 1, bool $isTest = false, ?WebhookDelivery $replayOf = null): WebhookDelivery
    {
        return WebhookDelivery::query()->create([
            'endpoint_id' => $endpoint->getKey(),
            'event_id' => $event->getKey(),
            'attempt' => $attempt,
            'status' => DeliveryStatus::Pending,
            'is_test' => $isTest,
            'replay_of_id' => $replayOf?->getKey(),
            'url' => $endpoint->url,
        ]);
    }

    /**
     * @param  array<mixed>  $payload
     */
    protected function storeEvent(string $name, array $payload, ?Model $tenant, ?string $id = null): WebhookEvent
    {
        $event = new WebhookEvent;
        $event->forceFill([
            'id' => $id ?? WebhookEvent::newId(),
            'name' => $name,
            'tenant_type' => $tenant?->getMorphClass(),
            'tenant_id' => $tenant === null ? null : (string) $tenant->getKey(),
            'payload' => $payload,
        ])->save();

        return $event;
    }

    /**
     * @param  array<mixed>|Arrayable<array-key, mixed>|JsonSerializable  $payload
     * @return array<mixed>
     */
    protected function normalizePayload(array|Arrayable|JsonSerializable $payload): array
    {
        if ($payload instanceof Arrayable) {
            return $payload->toArray();
        }

        if ($payload instanceof JsonSerializable) {
            $serialized = $payload->jsonSerialize();

            return is_array($serialized) ? $serialized : ['value' => $serialized];
        }

        return $payload;
    }

    /**
     * @return array{events: array<int, string>, prefix: string, tenant: Closure|null}|null
     */
    protected function findModelDefinition(Model $model): ?array
    {
        foreach ($this->plugins as $plugin) {
            foreach ($plugin->getModelEvents() as $class => $definition) {
                if ($model instanceof $class) {
                    return $definition;
                }
            }
        }

        return null;
    }

    protected function resolveModelTenant(Model $model, ?Closure $resolver): Model|false|null
    {
        if ($resolver !== null) {
            $tenant = $resolver($model);

            return $tenant instanceof Model ? $tenant : false;
        }

        if (method_exists($model, 'getWebhookTenant')) {
            $tenant = $model->getWebhookTenant();

            return $tenant instanceof Model ? $tenant : false;
        }

        return null;
    }
}
