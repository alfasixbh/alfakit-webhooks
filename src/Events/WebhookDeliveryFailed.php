<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Events;

use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after each attempt that failed (`$willRetry`: another attempt is queued).
 */
class WebhookDeliveryFailed
{
    use Dispatchable;

    public function __construct(public readonly WebhookDelivery $delivery, public readonly bool $willRetry = false) {}
}
