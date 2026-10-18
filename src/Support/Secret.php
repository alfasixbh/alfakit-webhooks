<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

use Illuminate\Support\Str;

/**
 * Signing secrets: `whsec_` and 40 random characters (about 238 bits).
 */
final class Secret
{
    public const PREFIX = 'whsec_';

    public static function generate(): string
    {
        return self::PREFIX.Str::random(40);
    }

    /**
     * `whsec_••••••••Ab3d`: enough to tell secrets apart, never enough to use one.
     */
    public static function mask(?string $secret): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }

        return self::PREFIX.str_repeat('•', 8).mb_substr($secret, -4);
    }
}
