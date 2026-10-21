<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Rules;

use AlfaSix\AlfakitWebhooks\Support\WebhookSender;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Custom headers: valid header names that don't replace the signature, content or host
 * headers, and values without line breaks.
 */
class ValidWebhookHeaders implements ValidationRule
{
    public const MAX_HEADERS = 20;

    public function __construct(protected ?WebhooksPlugin $plugin = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === []) {
            return;
        }

        if (! is_array($value)) {
            $fail(__('alfakit-webhooks::webhooks.headers.invalid'));

            return;
        }

        $value = self::fromRows($value);

        if (count($value) > self::MAX_HEADERS) {
            $fail(__('alfakit-webhooks::webhooks.headers.too_many', ['max' => self::MAX_HEADERS]));

            return;
        }

        $prefix = ($this->plugin ?? WebhooksPlugin::get())->getHeaderPrefix();

        foreach ($value as $name => $headerValue) {
            $name = (string) $name;

            if (! WebhookSender::isAllowedCustomHeader($name, $prefix)) {
                $fail(__('alfakit-webhooks::webhooks.headers.reserved', ['name' => $name]));

                return;
            }

            if (! is_scalar($headerValue) || preg_match('/[\r\n\0]/', (string) $headerValue) === 1 || strlen((string) $headerValue) > 4096) {
                $fail(__('alfakit-webhooks::webhooks.headers.invalid_value', ['name' => $name]));

                return;
            }
        }
    }

    /**
     * The renderers' KeyValue field sends its state as `[{key, value}]` rows (so keys can be
     * typed freely); the field saves them as a `name => value` map without the empty keys.
     * Validate that map, as it will be stored.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    public static function fromRows(array $value): array
    {
        if ($value === [] || ! array_is_list($value) || ! is_array($value[0])) {
            return $value;
        }

        $headers = [];

        foreach ($value as $row) {
            if (is_array($row) && is_scalar($row['key'] ?? null) && trim((string) $row['key']) !== '') {
                $headers[(string) $row['key']] = $row['value'] ?? null;
            }
        }

        return $headers;
    }
}
