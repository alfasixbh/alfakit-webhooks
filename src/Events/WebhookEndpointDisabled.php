<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Events;

use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when an endpoint is disabled after too many consecutive failures.
 */
class WebhookEndpointDisabled
{
    use Dispatchable;

    public function __construct(public readonly WebhookEndpoint $endpoint) {}
}
