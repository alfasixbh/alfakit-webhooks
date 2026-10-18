<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests\Fixtures\Support;

use AlfaSix\AlfakitWebhooks\Contracts\ResolvesHosts;

/**
 * DNS for the tests: no network access, predictable answers.
 */
class FakeResolver implements ResolvesHosts
{
    /** @var array<string, array<int, string>> */
    public array $hosts = [
        'example.com' => ['93.184.216.34'],
        'hooks.example.com' => ['93.184.216.34'],
        'receiver.test-hooks.io' => ['2606:4700::6810:84e5'],
        'internal.example.com' => ['10.0.0.5'],
        'metadata.example.com' => ['169.254.169.254'],
        'rebind.example.com' => ['93.184.216.34', '127.0.0.1'],
        'mapped.example.com' => ['::ffff:127.0.0.1'],
    ];

    /** @var array<int, string> */
    public array $lookups = [];

    public function resolve(string $host): array
    {
        $this->lookups[] = $host;

        return $this->hosts[$host] ?? [];
    }
}
