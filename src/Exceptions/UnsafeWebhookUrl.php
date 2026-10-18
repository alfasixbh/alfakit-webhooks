<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Exceptions;

use RuntimeException;

/**
 * The URL may not receive webhooks: not https, a private, loopback, link-local or
 * metadata address, a blocked host, or a name that doesn't resolve.
 */
class UnsafeWebhookUrl extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason = 'blocked')
    {
        parent::__construct($message);
    }
}
