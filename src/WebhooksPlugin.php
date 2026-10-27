<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks;

use AlfaSix\Alfakit\Contracts\Plugin;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Support\Concerns\EvaluatesClosures;
use AlfaSix\Alfakit\Support\ModelLabel;
use AlfaSix\AlfakitWebhooks\Contracts\ResolvesHosts;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource;
use AlfaSix\AlfakitWebhooks\Support\Redactor;
use AlfaSix\AlfakitWebhooks\Support\UrlGuard;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Outgoing webhooks managed in the panel.
 *
 * ```php
 * $panel->plugin(
 *     WebhooksPlugin::make()
 *         ->events([
 *             'order.paid' => 'Order paid',
 *             'order.refunded' => 'Order refunded',
 *         ])
 *         ->modelEvents([Customer::class])           // customer.created, customer.updated, customer.deleted
 *         ->retries(5)
 *         ->disableAfterConsecutiveFailures(15)
 *         ->authorize(fn (User $user): bool => $user->is_admin),
 * );
 *
 * Webhooks::dispatch('order.paid', ['order' => $order->only('id', 'total')]);
 * ```
 */
class WebhooksPlugin implements Plugin
{
    use EvaluatesClosures;

    public const ID = 'webhooks';

    /** The event of the "Send test event" action, sent whatever the endpoint subscribes to. */
    public const PING_EVENT = 'ping';

    /** @var array<int, string> */
    public const DEFAULT_MODEL_EVENTS = ['created', 'updated', 'deleted'];

    /** @var class-string<WebhookEndpointResource> */
    protected string $resource = WebhookEndpointResource::class;

    protected string|Closure|null $label = null;

    protected string|Closure|null $pluralLabel = null;

    protected string|Closure|null $navigationLabel = null;

    protected string|Closure|null $navigationGroup = null;

    protected string|Closure|null $navigationIcon = 'webhook';

    protected int|Closure|null $navigationSort = null;

    protected bool|Closure $shouldRegisterNavigation = true;

    protected bool|Closure|null $authorizeUsing = null;

    /** @var array<string, array{label: string|Closure|null, description: string|Closure|null}> */
    protected array $events = [];

    /** @var array<class-string<Model>, array{events: array<int, string>, prefix: string, tenant: Closure|null}> */
    protected array $modelEvents = [];

    protected bool|Closure $isScopedToTenant = true;

    protected bool $globalEndpointsReceiveTenantEvents = true;

    protected int $retries = 5;

    protected int $backoffBase = 60;

    protected float $backoffMultiplier = 4.0;

    protected int $backoffMax = 21600;

    protected bool $backoffJitter = true;

    protected ?Closure $backoffUsing = null;

    protected int $timeout = 10;

    protected int $connectTimeout = 5;

    protected ?int $disableAfterConsecutiveFailures = 15;

    protected ?Closure $notifyOnDisableUsing = null;

    protected bool|Closure $mailsOnDisable = false;

    protected ?string $queue = null;

    protected ?string $connection = null;

    protected bool|Closure|null $allowsInsecureUrls = null;

    protected bool|Closure $allowsPrivateNetworks = false;

    /** @var array<int, string> */
    protected array $allowedHosts = [];

    /** @var array<int, string> */
    protected array $blockedHosts = [];

    /** @var array<int, string> */
    protected array $redactedKeys = Redactor::DEFAULT_KEYS;

    protected int $maxLoggedBodySize = 16384;

    protected string $headerPrefix = 'X-Webhook';

    protected ?string $userAgent = null;

    protected int $pruneAfterDays = 30;

    public static function make(): static
    {
        return new static;
    }

    /**
     * The plugin registered on the current panel; outside a panel request (queue workers,
     * console), the first panel's plugin; a plugin with the defaults when there is none.
     */
    public static function get(): static
    {
        $panel = alfakit()->getCurrentPanel();

        if ($panel !== null && $panel->hasPlugin(self::ID)) {
            $plugin = $panel->getPlugin(self::ID);

            if ($plugin instanceof static) {
                return $plugin;
            }
        }

        foreach (alfakit()->getPanels() as $candidate) {
            if ($candidate->hasPlugin(self::ID)) {
                $plugin = $candidate->getPlugin(self::ID);

                if ($plugin instanceof static) {
                    return $plugin;
                }
            }
        }

        return static::make();
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([$this->getResourceClass()]);

        app(WebhookManager::class)->registerPlugin($this);
    }

    public function boot(Panel $panel): void {}

    protected function getEvaluationIdentifier(): string
    {
        return 'plugin';
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        $panel = alfakit()->getCurrentPanel() ?? alfakit()->getDefaultPanel();

        return match ($parameterName) {
            'panel' => [$panel],
            'user' => [$panel?->auth()->user()],
            'tenant' => [alfakit()->getTenant()],
            default => [],
        };
    }

    // ---------------------------------------------------------------- Resource

    /**
     * Replace the endpoints resource with your own subclass of `WebhookEndpointResource`.
     *
     * @param  class-string<WebhookEndpointResource>  $resource
     */
    public function resourceClass(string $resource): static
    {
        if (! is_a($resource, WebhookEndpointResource::class, true)) {
            throw new InvalidArgumentException("The webhooks resource [{$resource}] must extend [".WebhookEndpointResource::class.'].');
        }

        $this->resource = $resource;

        return $this;
    }

    /**
     * @return class-string<WebhookEndpointResource>
     */
    public function getResourceClass(): string
    {
        return $this->resource;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return (string) ($this->evaluate($this->label) ?? __('alfakit-webhooks::webhooks.resource.label'));
    }

    public function pluralLabel(string|Closure|null $label): static
    {
        $this->pluralLabel = $label;

        return $this;
    }

    public function getPluralLabel(): string
    {
        return (string) ($this->evaluate($this->pluralLabel) ?? __('alfakit-webhooks::webhooks.resource.plural_label'));
    }

    public function navigationLabel(string|Closure|null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): string
    {
        return (string) ($this->evaluate($this->navigationLabel) ?? __('alfakit-webhooks::webhooks.resource.navigation_label'));
    }

    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        $group = $this->evaluate($this->navigationGroup);

        return is_string($group) ? $group : null;
    }

    public function navigationIcon(string|Closure|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        $icon = $this->evaluate($this->navigationIcon);

        return is_string($icon) ? $icon : null;
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        $sort = $this->evaluate($this->navigationSort);

        return is_int($sort) ? $sort : null;
    }

    public function registerNavigation(bool|Closure $condition = true): static
    {
        $this->shouldRegisterNavigation = $condition;

        return $this;
    }

    public function shouldRegisterNavigation(): bool
    {
        return (bool) $this->evaluate($this->shouldRegisterNavigation);
    }

    // ---------------------------------------------------------------- Authorisation

    /**
     * Who may manage webhooks: `fn (User $user): bool => $user->is_admin`. Without it, the
     * policy of `WebhookEndpoint` decides (`viewAny`, `create`, `update`, `delete`), and
     * everyone may when there is no policy.
     */
    public function authorize(bool|Closure|null $callback = true): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function hasAuthorization(): bool
    {
        return $this->authorizeUsing !== null;
    }

    public function isAuthorized(): bool
    {
        return $this->evaluateUserAuthorization($this->authorizeUsing);
    }

    // ---------------------------------------------------------------- Events

    /**
     * The events endpoints can subscribe to: `['order.paid' => 'Order paid']`, or a list of
     * names (labelled from the name). Adds to the events already registered. Labels and
     * descriptions may be closures, resolved when they are shown, so they can be translated
     * in the user's language: `['order.paid' => fn () => __('webhooks.order_paid')]`.
     *
     * @param  array<int|string, string|Closure|array{label?: string|Closure|null, description?: string|Closure|null}>  $events
     */
    public function events(array $events): static
    {
        foreach ($events as $name => $definition) {
            if (is_int($name)) {
                $this->event((string) (is_string($definition) ? $definition : ''));

                continue;
            }

            if (is_array($definition)) {
                $this->event($name, $definition['label'] ?? null, $definition['description'] ?? null);

                continue;
            }

            $this->event($name, $definition instanceof Closure ? $definition : (string) $definition);
        }

        return $this;
    }

    public function event(string $name, string|Closure|null $label = null, string|Closure|null $description = null): static
    {
        $name = trim($name);

        if ($name === '' || $name === WebhookEndpoint::ALL_EVENTS || $name === self::PING_EVENT) {
            throw new InvalidArgumentException("[{$name}] can't be used as a webhook event name.");
        }

        $this->events[$name] = ['label' => $label, 'description' => $description];

        return $this;
    }

    /**
     * Send `{prefix}.created`, `{prefix}.updated` and `{prefix}.deleted` events when these
     * models change. The prefix defaults to the snake-cased class name (`order_item`); the
     * payload is `toWebhookPayload(string $event)` when the model defines it, else
     * `toArray()` (hidden attributes stay hidden). The tenant is `$tenant($model)`, else
     * the model's `getWebhookTenant()`, else the panel's current tenant.
     *
     * @param  class-string<Model>|array<int, class-string<Model>>  $models
     * @param  array<int, string>  $events
     */
    public function modelEvents(string|array $models, array $events = self::DEFAULT_MODEL_EVENTS, ?string $prefix = null, ?Closure $tenant = null): static
    {
        foreach ((array) $models as $model) {
            if (! is_a($model, Model::class, true)) {
                throw new InvalidArgumentException("[{$model}] is not an Eloquent model.");
            }

            $unknown = array_diff($events, self::DEFAULT_MODEL_EVENTS);

            if ($unknown !== []) {
                throw new InvalidArgumentException('Model webhook events must be created, updated or deleted; got ['.implode(', ', $unknown).'].');
            }

            $this->modelEvents[$model] = [
                'events' => array_values($events),
                'prefix' => $prefix ?? Str::snake(class_basename($model)),
                'tenant' => $tenant,
            ];
        }

        return $this;
    }

    /**
     * @return array<class-string<Model>, array{events: array<int, string>, prefix: string, tenant: Closure|null}>
     */
    public function getModelEvents(): array
    {
        return $this->modelEvents;
    }

    /**
     * Every subscribable event: name => label and description.
     *
     * @return array<string, array{label: string, description: string|null}>
     */
    public function getEvents(): array
    {
        $events = [];

        foreach ($this->events as $name => $event) {
            $label = $this->evaluate($event['label']);
            $description = $this->evaluate($event['description']);

            $events[$name] = [
                'label' => is_string($label) && $label !== '' ? $label : Str::headline(str_replace('.', ' ', $name)),
                'description' => is_string($description) && $description !== '' ? $description : null,
            ];
        }

        foreach ($this->modelEvents as $model => $definition) {
            foreach ($definition['events'] as $event) {
                $name = $definition['prefix'].'.'.$event;
                $events[$name] ??= [
                    // The model's label in the user's language (the app's `models` lang group).
                    'label' => (string) __('alfakit-webhooks::webhooks.model_events.'.$event, ['model' => Str::ucfirst(ModelLabel::singular($model))]),
                    'description' => null,
                ];
            }
        }

        return $events;
    }

    // ---------------------------------------------------------------- Tenancy

    /**
     * On panels with tenancy, each tenant manages its own endpoints, and they only receive
     * that tenant's events (default).
     */
    public function scopeToTenant(bool|Closure $condition = true): static
    {
        $this->isScopedToTenant = $condition;

        return $this;
    }

    public function isScopedToTenant(): bool
    {
        return (bool) $this->evaluate($this->isScopedToTenant);
    }

    /**
     * Endpoints without a tenant (created on a panel without tenancy) receive the events of
     * every tenant too (default). Turn it off to send them only events without a tenant.
     */
    public function globalEndpointsReceiveTenantEvents(bool $condition = true): static
    {
        $this->globalEndpointsReceiveTenantEvents = $condition;

        return $this;
    }

    public function doGlobalEndpointsReceiveTenantEvents(): bool
    {
        return $this->globalEndpointsReceiveTenantEvents;
    }

    // ---------------------------------------------------------------- Delivery

    /**
     * How many times a failed delivery is retried (5 by default: 6 attempts in all).
     */
    public function retries(int $times): static
    {
        $this->retries = max(0, $times);

        return $this;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function getMaxAttempts(): int
    {
        return $this->retries + 1;
    }

    /**
     * Exponential backoff between attempts: `$base * $multiplier ** ($attempt - 1)` seconds,
     * at most `$max`, plus up to 10% random jitter. The default waits 1 min, 4 min, 16 min,
     * ~1 h and ~4 h.
     */
    public function backoff(int $base = 60, float $multiplier = 4.0, int $max = 21600, bool $jitter = true): static
    {
        $this->backoffBase = max(0, $base);
        $this->backoffMultiplier = max(1.0, $multiplier);
        $this->backoffMax = max(0, $max);
        $this->backoffJitter = $jitter;

        return $this;
    }

    /**
     * Compute the delay yourself: `fn (int $attempt): int => [10, 60, 300][$attempt - 1] ?? 600`.
     * `$attempt` is the attempt that just failed (1 for the first).
     */
    public function backoffUsing(?Closure $callback): static
    {
        $this->backoffUsing = $callback;

        return $this;
    }

    /**
     * Seconds to wait before retrying after attempt number `$attempt` failed.
     */
    public function getBackoffDelay(int $attempt): int
    {
        if ($this->backoffUsing !== null) {
            return max(0, (int) $this->evaluate($this->backoffUsing, ['attempt' => $attempt]));
        }

        $delay = (int) min($this->backoffMax, $this->backoffBase * ($this->backoffMultiplier ** max(0, $attempt - 1)));

        if ($this->backoffJitter && $delay > 0) {
            $delay += random_int(0, max(0, intdiv($delay, 10)));
        }

        return $delay;
    }

    /**
     * Seconds to wait for a response (10 by default) and for the connection (5).
     */
    public function timeout(int $seconds, ?int $connectTimeout = null): static
    {
        $this->timeout = max(1, $seconds);

        if ($connectTimeout !== null) {
            $this->connectTimeout = max(1, $connectTimeout);
        }

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getConnectTimeout(): int
    {
        return min($this->connectTimeout, $this->timeout);
    }

    /**
     * Disable an endpoint after this many failed attempts in a row (15 by default); null
     * never disables. The users of `notifyOnDisable()` get a database notification.
     */
    public function disableAfterConsecutiveFailures(?int $failures): static
    {
        $this->disableAfterConsecutiveFailures = $failures === null ? null : max(1, $failures);

        return $this;
    }

    public function getDisableAfterConsecutiveFailures(): ?int
    {
        return $this->disableAfterConsecutiveFailures;
    }

    /**
     * Who is told when an endpoint is disabled: `fn (WebhookEndpoint $endpoint) => User::where('is_admin', true)->get()`.
     * By default, the user who created the endpoint.
     */
    public function notifyOnDisable(?Closure $recipients): static
    {
        $this->notifyOnDisableUsing = $recipients;

        return $this;
    }

    /**
     * Also email the `notifyOnDisable()` users (in the AlfaKit mail theme) when an
     * endpoint is disabled; by default they only get a database notification.
     */
    public function mailOnDisable(bool|Closure $condition = true): static
    {
        $this->mailsOnDisable = $condition;

        return $this;
    }

    public function shouldMailOnDisable(): bool
    {
        return (bool) $this->evaluate($this->mailsOnDisable);
    }

    /**
     * @return array<int, Authenticatable>
     */
    public function getDisableNotificationRecipients(WebhookEndpoint $endpoint): array
    {
        if ($this->notifyOnDisableUsing !== null) {
            $recipients = $this->evaluate($this->notifyOnDisableUsing, ['endpoint' => $endpoint, 'record' => $endpoint], [WebhookEndpoint::class => $endpoint]);
        } else {
            $recipients = $endpoint->createdBy()->first();
        }

        $recipients = $recipients instanceof Authenticatable ? [$recipients] : (is_iterable($recipients) ? $recipients : []);
        $users = [];

        foreach ($recipients as $recipient) {
            if ($recipient instanceof Authenticatable) {
                $users[] = $recipient;
            }
        }

        return $users;
    }

    /**
     * The queue (and connection) of delivery jobs.
     */
    public function queue(?string $queue, ?string $connection = null): static
    {
        $this->queue = $queue;
        $this->connection = $connection;

        return $this;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    // ---------------------------------------------------------------- Requests

    /**
     * The prefix of the headers every delivery sends (`X-Webhook-Id`, `-Event`,
     * `-Timestamp`, `-Signature`, `-Attempt`).
     */
    public function headerPrefix(string $prefix): static
    {
        $prefix = trim($prefix, " -\t");

        if ($prefix === '' || preg_match('/^[A-Za-z0-9-]+$/', $prefix) !== 1) {
            throw new InvalidArgumentException("[{$prefix}] is not a valid header prefix.");
        }

        $this->headerPrefix = $prefix;

        return $this;
    }

    public function getHeaderPrefix(): string
    {
        return $this->headerPrefix;
    }

    public function userAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent ?? 'AlfaKit-Webhooks/1.0';
    }

    // ---------------------------------------------------------------- SSRF protection

    /**
     * Accept http:// URLs. By default only in the `local` environment.
     */
    public function allowInsecureUrls(bool|Closure|null $condition = true): static
    {
        $this->allowsInsecureUrls = $condition;

        return $this;
    }

    public function allowsInsecureUrls(): bool
    {
        $allowed = $this->evaluate($this->allowsInsecureUrls);

        return $allowed === null ? app()->isLocal() : (bool) $allowed;
    }

    /**
     * Accept private, loopback and link-local addresses (e.g. `->allowPrivateNetworks(app()->isLocal())`
     * to reach `*.test` sites while developing). Never turn it on in production.
     */
    public function allowPrivateNetworks(bool|Closure $condition = true): static
    {
        $this->allowsPrivateNetworks = $condition;

        return $this;
    }

    public function allowsPrivateNetworks(): bool
    {
        return (bool) $this->evaluate($this->allowsPrivateNetworks);
    }

    /**
     * Hosts (or `*.example.com` patterns) that may resolve to private addresses, such as an
     * internal service you trust.
     *
     * @param  array<int, string>  $hosts
     */
    public function allowedHosts(array $hosts): static
    {
        $this->allowedHosts = array_values($hosts);

        return $this;
    }

    /**
     * Hosts (or patterns) that are always refused, on top of localhost and the cloud
     * metadata hosts.
     *
     * @param  array<int, string>  $hosts
     */
    public function blockedHosts(array $hosts): static
    {
        $this->blockedHosts = array_values($hosts);

        return $this;
    }

    public function makeUrlGuard(): UrlGuard
    {
        return new UrlGuard(
            app(ResolvesHosts::class),
            $this->allowsInsecureUrls(),
            $this->allowsPrivateNetworks(),
            $this->allowedHosts,
            $this->blockedHosts,
        );
    }

    // ---------------------------------------------------------------- Deliveries log

    /**
     * More keys whose values are masked in the logged bodies and headers (passwords,
     * tokens, API keys, authorization and cookie headers… are masked already).
     *
     * @param  array<int, string>  $keys
     */
    public function redact(array $keys): static
    {
        $this->redactedKeys = array_values(array_unique([...$this->redactedKeys, ...$keys]));

        return $this;
    }

    /**
     * How many bytes of each request and response body the log keeps (16 KB by default).
     */
    public function maxLoggedBodySize(int $bytes): static
    {
        $this->maxLoggedBodySize = max(0, $bytes);

        return $this;
    }

    public function makeRedactor(): Redactor
    {
        return new Redactor($this->redactedKeys, $this->maxLoggedBodySize);
    }

    /**
     * How long `alfakit-webhooks:prune` keeps deliveries (30 days by default).
     */
    public function pruneAfterDays(int $days): static
    {
        $this->pruneAfterDays = max(1, $days);

        return $this;
    }

    public function getPruneAfterDays(): int
    {
        return $this->pruneAfterDays;
    }
}
