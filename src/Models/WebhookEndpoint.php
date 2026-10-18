<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Models;

use AlfaSix\AlfakitWebhooks\Support\Secret;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A URL that receives the events it subscribes to. The signing secret and the custom
 * headers are encrypted with the application key.
 *
 * @property int $id
 * @property string|null $tenant_type
 * @property string|null $tenant_id
 * @property string|null $created_by_type
 * @property string|null $created_by_id
 * @property string $url
 * @property string|null $description
 * @property bool $is_enabled
 * @property array<int, string> $events
 * @property string $secret
 * @property string|null $previous_secret
 * @property Carbon|null $previous_secret_expires_at
 * @property array<string, string>|null $headers
 * @property int $consecutive_failures
 * @property Carbon|null $disabled_at
 * @property string|null $disabled_reason
 * @property Carbon|null $last_delivery_at
 * @property Carbon|null $last_success_at
 * @property Carbon|null $last_failure_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookEndpoint extends Model
{
    /** Subscribes to every event. */
    public const ALL_EVENTS = '*';

    protected $table = 'alfakit_webhook_endpoints';

    protected $fillable = ['url', 'description', 'is_enabled', 'events', 'headers'];

    protected $hidden = ['secret', 'previous_secret', 'headers'];

    protected $attributes = [
        'is_enabled' => true,
        'consecutive_failures' => 0,
        'events' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'events' => 'array',
            'secret' => 'encrypted',
            'previous_secret' => 'encrypted',
            'previous_secret_expires_at' => 'datetime',
            'headers' => 'encrypted:array',
            'consecutive_failures' => 'integer',
            'disabled_at' => 'datetime',
            'last_delivery_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $endpoint): void {
            if (blank($endpoint->getAttribute('secret'))) {
                $endpoint->setAttribute('secret', Secret::generate());
            }
        });
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    /** @return MorphTo<Model, $this> */
    public function tenant(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    // ---------------------------------------------------------------- Tenancy

    public function setTenant(?Model $tenant): static
    {
        $this->setAttribute('tenant_type', $tenant?->getMorphClass());
        $this->setAttribute('tenant_id', $tenant === null ? null : (string) $tenant->getKey());

        return $this;
    }

    public function isOwnedByTenant(?Model $tenant): bool
    {
        if ($tenant === null) {
            return $this->tenant_type === null;
        }

        return $this->tenant_type === $tenant->getMorphClass() && (string) $this->tenant_id === (string) $tenant->getKey();
    }

    /**
     * The endpoints owned by this tenant (null: the endpoints without a tenant).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOwnedBy(Builder $query, ?Model $tenant): Builder
    {
        return static::constrainToTenant($query, $tenant);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainToTenant(Builder $query, ?Model $tenant): Builder
    {
        if ($tenant === null) {
            return $query->whereNull('tenant_type');
        }

        return $query->where('tenant_type', $tenant->getMorphClass())->where('tenant_id', (string) $tenant->getKey());
    }

    // ---------------------------------------------------------------- Events

    public function subscribesTo(string $event): bool
    {
        $events = $this->events;

        return in_array(self::ALL_EVENTS, $events, true) || in_array($event, $events, true);
    }

    public function subscribesToAllEvents(): bool
    {
        return in_array(self::ALL_EVENTS, $this->events, true);
    }

    // ---------------------------------------------------------------- Secret

    /**
     * Replace the signing secret and return the new one. With `$keepPreviousUntil`, the
     * old secret keeps signing (as a second `v1` signature) until then, so receivers can
     * switch without dropping events.
     */
    public function rotateSecret(?CarbonInterface $keepPreviousUntil = null): string
    {
        $secret = Secret::generate();

        if ($keepPreviousUntil !== null && $keepPreviousUntil->isFuture()) {
            $this->setAttribute('previous_secret', $this->secret);
            $this->setAttribute('previous_secret_expires_at', $keepPreviousUntil);
        } else {
            $this->setAttribute('previous_secret', null);
            $this->setAttribute('previous_secret_expires_at', null);
        }

        $this->setAttribute('secret', $secret);
        $this->save();

        return $secret;
    }

    /**
     * The secrets that sign a request now: the current one, then the previous one while it
     * is still valid.
     *
     * @return array<int, string>
     */
    public function getSigningSecrets(): array
    {
        $secrets = [$this->secret];
        $expiresAt = $this->previous_secret_expires_at;

        if (filled($this->previous_secret) && $expiresAt !== null && $expiresAt->isFuture()) {
            $secrets[] = (string) $this->previous_secret;
        }

        return $secrets;
    }

    public function getMaskedSecret(): string
    {
        return Secret::mask($this->secret);
    }

    // ---------------------------------------------------------------- State

    /**
     * Disabled by the plugin after too many consecutive failures (a reason is stored),
     * rather than by a user.
     */
    public function isAutoDisabled(): bool
    {
        return ! $this->is_enabled && filled($this->disabled_reason);
    }

    public function enable(): void
    {
        $this->forceFill([
            'is_enabled' => true,
            'consecutive_failures' => 0,
            'disabled_at' => null,
            'disabled_reason' => null,
        ])->save();
    }

    public function disable(?string $reason = null): void
    {
        $this->forceFill([
            'is_enabled' => false,
            'disabled_at' => now(),
            'disabled_reason' => $reason,
        ])->save();
    }
}
