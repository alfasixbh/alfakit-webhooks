<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Events;

use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after each attempt that got a 2xx response.
 */
class WebhookDeliverySucceeded
{
    use Dispatchable;

    public function __construct(public readonly WebhookDelivery $delivery) {}
}
