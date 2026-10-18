<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;

/**
 * Shows a new signing secret once: it is flashed to the session when an endpoint is
 * created or its secret rotated, and the edit page shows it on the next request only.
 */
final class SecretReveal
{
    public static function flash(WebhookEndpoint $endpoint, string $secret): void
    {
        if (app()->bound('session')) {
            session()->flash(self::key($endpoint), $secret);
        }
    }

    public static function get(?WebhookEndpoint $endpoint): ?string
    {
        if ($endpoint === null || ! $endpoint->exists || ! app()->bound('session')) {
            return null;
        }

        $secret = session()->get(self::key($endpoint));

        // Only the endpoint's current secret is ever shown.
        return is_string($secret) && hash_equals($endpoint->secret, $secret) ? $secret : null;
    }

    private static function key(WebhookEndpoint $endpoint): string
    {
        return 'alfakit-webhooks.secret.'.$endpoint->getKey();
    }
}
