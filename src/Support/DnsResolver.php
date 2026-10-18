<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

use AlfaSix\AlfakitWebhooks\Contracts\ResolvesHosts;

/**
 * Resolves A and AAAA records with the system resolver.
 */
class DnsResolver implements ResolvesHosts
{
    public function resolve(string $host): array
    {
        $addresses = [];

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (is_array($records)) {
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;

                if (is_string($ip)) {
                    $addresses[] = $ip;
                }
            }
        }

        if ($addresses === []) {
            $fallback = @gethostbynamel($host);

            if (is_array($fallback)) {
                $addresses = $fallback;
            }
        }

        return array_values(array_unique($addresses));
    }
}
