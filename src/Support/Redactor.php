<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

/**
 * Masks sensitive values before request and response bodies and headers are stored in
 * the deliveries log, and truncates long bodies. Keys are matched case-insensitively,
 * ignoring `-` and `_` (`api_key`, `Api-Key` and `APIKEY` are the same key).
 */
class Redactor
{
    public const MASK = '[redacted]';

    /** @var array<int, string> */
    public const DEFAULT_KEYS = [
        'password', 'password_confirmation', 'current_password', 'secret', 'client_secret',
        'token', 'access_token', 'refresh_token', 'id_token', 'api_key', 'apikey', 'api_token',
        'authorization', 'proxy_authorization', 'cookie', 'set_cookie', 'x_api_key',
        'card_number', 'cvc', 'cvv', 'ssn', 'private_key',
    ];

    /** @var array<string, true> */
    protected array $keys = [];

    /**
     * @param  array<int, string>  $keys
     */
    public function __construct(array $keys = self::DEFAULT_KEYS, protected int $maxLength = 16384)
    {
        foreach ($keys as $key) {
            $this->keys[self::normalize($key)] = true;
        }
    }

    public function isSensitive(string $key): bool
    {
        return isset($this->keys[self::normalize($key)]);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = self::MASK;
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    public function redactHeaders(array $headers): array
    {
        $redacted = [];

        foreach ($headers as $name => $value) {
            $value = is_array($value) ? implode(', ', array_map(static fn (mixed $item): string => is_scalar($item) ? (string) $item : '', $value)) : (is_scalar($value) ? (string) $value : '');
            $redacted[(string) $name] = $this->isSensitive((string) $name) || str_contains(strtolower((string) $name), 'signature') ? self::MASK : $value;
        }

        return $redacted;
    }

    /**
     * Redact a body: JSON bodies key by key, anything else as is. Then truncate it.
     */
    public function redactBody(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return $body;
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $body = (string) json_encode($this->redactArray($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return $this->truncate($body);
    }

    public function truncate(string $value): string
    {
        // Binary responses would break JSON columns and the log UI.
        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if ($this->maxLength <= 0 || strlen($value) <= $this->maxLength) {
            return $value;
        }

        return mb_strcut($value, 0, $this->maxLength, 'UTF-8').'…';
    }

    private static function normalize(string $key): string
    {
        return str_replace(['-', '_', ' '], '', strtolower($key));
    }
}
