<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Rules;

use AlfaSix\AlfakitWebhooks\Exceptions\UnsafeWebhookUrl;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The URL passes the plugin's SSRF guard (https, no credentials, not a blocked host, and
 * resolving to public addresses only). It is checked again before every delivery.
 */
class SafeWebhookUrl implements ValidationRule
{
    public function __construct(protected ?WebhooksPlugin $plugin = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (strlen($value) > 2048) {
            $fail(__('alfakit-webhooks::webhooks.url.too_long'));

            return;
        }

        try {
            ($this->plugin ?? WebhooksPlugin::get())->makeUrlGuard()->check($value);
        } catch (UnsafeWebhookUrl $exception) {
            $fail($exception->getMessage());
        }
    }
}
