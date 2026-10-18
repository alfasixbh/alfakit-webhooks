<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One dispatched event. Its id (`evt_…`) is sent with every delivery and every retry or
 * replay of it, so receivers can ignore duplicates.
 *
 * @property string $id
 * @property string $name
 * @property string|null $tenant_type
 * @property string|null $tenant_id
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 */
class WebhookEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'alfakit_webhook_events';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $event): void {
            if (blank($event->getKey())) {
                $event->setAttribute('id', static::newId());
            }
        });
    }

    public static function newId(): string
    {
        return 'evt_'.Str::lower((string) Str::ulid());
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'event_id');
    }

    /**
     * The JSON body every delivery of this event sends (identical across attempts, so
     * signatures and idempotency keys stay meaningful).
     *
     * @return array{id: string, type: string, created_at: string|null, data: array<string, mixed>}
     */
    public function toEnvelope(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->name,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'data' => $this->payload,
        ];
    }

    public function toJsonBody(): string
    {
        return (string) json_encode($this->toEnvelope(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
