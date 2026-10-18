<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Contracts;

/**
 * Resolves a host name to its IP addresses (the SSRF guard checks every one of them). Bind
 * your own implementation to change how names are resolved.
 */
interface ResolvesHosts
{
    /**
     * @return array<int, string> IPv4 and IPv6 addresses; empty when the name doesn't resolve
     */
    public function resolve(string $host): array;
}
