<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Models;

use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One attempt to send an event to an endpoint. A retry or a replay is a new row, so the
 * log keeps the history of every attempt. The request and response bodies are stored
 * redacted and truncated, for display only.
 *
 * @property int $id
 * @property int $endpoint_id
 * @property string $event_id
 * @property int $attempt
 * @property DeliveryStatus $status
 * @property bool $is_test
 * @property int|null $replay_of_id
 * @property string|null $url
 * @property int|null $response_status
 * @property int|null $duration_ms
 * @property array<string, string>|null $request_headers
 * @property string|null $request_body
 * @property array<string, string>|null $response_headers
 * @property string|null $response_body
 * @property string|null $error
 * @property Carbon|null $next_retry_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WebhookEndpoint|null $endpoint
 * @property-read WebhookEvent|null $event
 */
class WebhookDelivery extends Model
{
    protected $table = 'alfakit_webhook_deliveries';

    protected $guarded = [];

    protected $attributes = [
        'attempt' => 1,
        'status' => 'pending',
        'is_test' => false,
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'status' => DeliveryStatus::class,
            'is_test' => 'boolean',
            'response_status' => 'integer',
            'duration_ms' => 'integer',
            'request_headers' => 'array',
            'response_headers' => 'array',
            'next_retry_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WebhookEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }

    /** @return BelongsTo<WebhookEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'event_id');
    }

    /** @return BelongsTo<self, $this> */
    public function replayOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replay_of_id');
    }

    public function isPending(): bool
    {
        return $this->status === DeliveryStatus::Pending;
    }

    public function succeeded(): bool
    {
        return $this->status === DeliveryStatus::Succeeded;
    }

    public function failed(): bool
    {
        return $this->status === DeliveryStatus::Failed;
    }
}
