<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

/**
 * The Stripe-like signature scheme of every delivery.
 *
 * The signed content is `"{timestamp}.{body}"`, where the timestamp is the Unix time of the
 * request (also sent in the timestamp header) and the body is the raw JSON request body.
 * Each signing secret gives one `v1` entry, the lowercase hex HMAC-SHA256 of that content:
 *
 *     X-Webhook-Signature: t=1767225600,v1=5257a869e7…,v1=9a1c…
 *
 * There are two `v1` entries while a rotated secret is still valid.
 */
final class Signature
{
    public const SCHEME = 'v1';

    public static function compute(string $secret, int $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    /**
     * @param  array<int, string>  $secrets
     */
    public static function header(array $secrets, int $timestamp, string $body): string
    {
        $parts = ['t='.$timestamp];

        foreach ($secrets as $secret) {
            $parts[] = self::SCHEME.'='.self::compute($secret, $timestamp, $body);
        }

        return implode(',', $parts);
    }

    /**
     * Check a signature header as a receiver does: one `v1` entry must match and the
     * timestamp must be within the tolerance (seconds) of now, to stop replays.
     */
    public static function verify(string $header, string $body, string $secret, int $tolerance = 300, ?int $now = null): bool
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($key === self::SCHEME && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        if ($tolerance > 0 && abs(($now ?? time()) - $timestamp) > $tolerance) {
            return false;
        }

        $expected = self::compute($secret, $timestamp, $body);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
