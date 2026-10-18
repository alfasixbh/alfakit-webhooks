<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

use AlfaSix\AlfakitWebhooks\Contracts\ResolvesHosts;
use AlfaSix\AlfakitWebhooks\Exceptions\UnsafeWebhookUrl;
use Illuminate\Support\Str;

/**
 * Server-side request forgery protection for endpoint URLs.
 *
 * A URL is accepted when it uses https (http only with `allowInsecureUrls()`), has no
 * credentials, its host isn't a blocked name (localhost, cloud metadata hosts, `*.internal`…)
 * and every address it resolves to is public: no private (RFC 1918, unique local),
 * loopback, link-local (including 169.254.169.254), carrier-grade NAT, multicast, reserved,
 * documentation or IPv4-mapped / NAT64 / 6to4 forms of those. Names are resolved again
 * right before each request and the connection is pinned to the checked address, so DNS
 * rebinding can't swap in an internal one.
 */
class UrlGuard
{
    /** @var array<int, string> */
    public const DEFAULT_BLOCKED_HOSTS = [
        'localhost',
        '*.localhost',
        'metadata',
        'metadata.google.internal',
        'metadata.goog',
        'instance-data',
        'instance-data.ec2.internal',
        '*.internal',
    ];

    /** @var array<int, string> */
    private const BLOCKED_IPV4_RANGES = [
        '0.0.0.0/8',        // "this" network
        '10.0.0.0/8',       // private
        '100.64.0.0/10',    // carrier-grade NAT
        '127.0.0.0/8',      // loopback
        '169.254.0.0/16',   // link-local, cloud metadata
        '172.16.0.0/12',    // private
        '192.0.0.0/24',     // IETF protocol assignments
        '192.0.2.0/24',     // documentation
        '192.88.99.0/24',   // 6to4 relay
        '192.168.0.0/16',   // private
        '198.18.0.0/15',    // benchmarking
        '198.51.100.0/24',  // documentation
        '203.0.113.0/24',   // documentation
        '224.0.0.0/4',      // multicast
        '240.0.0.0/4',      // reserved, broadcast
    ];

    /** @var array<int, string> */
    private const BLOCKED_IPV6_RANGES = [
        '::/128',           // unspecified
        '::1/128',          // loopback
        '100::/64',         // discard
        '2001::/23',        // IETF protocol assignments
        '2001:db8::/32',    // documentation
        'fc00::/7',         // unique local
        'fe80::/10',        // link-local
        'fec0::/10',        // site-local (deprecated)
        'ff00::/8',         // multicast
    ];

    /**
     * @param  array<int, string>  $allowedHosts  Hosts (or `*.example.com` patterns) that skip the address checks
     * @param  array<int, string>  $blockedHosts  Extra hosts (or patterns) that are always refused
     */
    public function __construct(
        protected ResolvesHosts $resolver,
        protected bool $allowsInsecureUrls = false,
        protected bool $allowsPrivateNetworks = false,
        protected array $allowedHosts = [],
        protected array $blockedHosts = [],
    ) {}

    /**
     * Check the URL and return the addresses its host resolves to (the IP itself for an IP
     * literal). Without `$resolve`, a host name is only checked syntactically.
     *
     *
     * @throws UnsafeWebhookUrl
     *
     * @return array{scheme: string, host: string, port: int, addresses: array<int, string>}
     */
    public function check(string $url, bool $resolve = true): array
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.invalid'), 'invalid');
        }

        $scheme = strtolower($parts['scheme']);

        if ($scheme !== 'https' && ! ($scheme === 'http' && $this->allowsInsecureUrls)) {
            throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.https_required'), 'scheme');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.credentials'), 'credentials');
        }

        $host = rtrim(strtolower($parts['host']), '.');
        $host = str_starts_with($host, '[') && str_ends_with($host, ']') ? substr($host, 1, -1) : $host;
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $result = ['scheme' => $scheme, 'host' => $host, 'port' => $port, 'addresses' => []];

        if ($this->matchesHost($host, $this->blockedHosts) || $this->matchesHost($host, self::DEFAULT_BLOCKED_HOSTS)) {
            throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.blocked_host'), 'host');
        }

        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        // Numeric or hex host names ("2130706433", "0x7f.1", "127.1") are IP addresses in
        // disguise for some resolvers: only real host names and plain IP literals pass.
        if (! $isIp && ! $this->isHostName($host)) {
            throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.invalid'), 'invalid');
        }

        $skipsAddressChecks = $this->allowsPrivateNetworks || $this->matchesHost($host, $this->allowedHosts);

        if ($isIp) {
            if (! $skipsAddressChecks && ! self::isPublicIp($host)) {
                throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.private_address'), 'address');
            }

            return [...$result, 'addresses' => [$host]];
        }

        if (! $resolve) {
            return $result;
        }

        $addresses = $this->resolver->resolve($host);

        if ($addresses === []) {
            throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.unresolvable'), 'dns');
        }

        if (! $skipsAddressChecks) {
            foreach ($addresses as $address) {
                if (! self::isPublicIp($address)) {
                    throw new UnsafeWebhookUrl(__('alfakit-webhooks::webhooks.url.private_address'), 'address');
                }
            }
        }

        return [...$result, 'addresses' => array_values($addresses)];
    }

    /**
     * Whether an address is publicly routable (IPv4-mapped, NAT64 and 6to4 IPv6 addresses
     * are judged by the IPv4 address they carry).
     */
    public static function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 4) {
            return ! self::inRanges($packed, self::BLOCKED_IPV4_RANGES);
        }

        // ::ffff:a.b.c.d (mapped), ::a.b.c.d (compatible, deprecated) and 64:ff9b::a.b.c.d (NAT64).
        $prefix = substr($packed, 0, 12);

        if ($prefix === str_repeat("\0", 10)."\xff\xff" || $prefix === str_repeat("\0", 12) || $prefix === "\x00\x64\xff\x9b".str_repeat("\0", 8)) {
            $embedded = substr($packed, 12, 4);

            // `::` and `::1` are handled by the IPv6 ranges below.
            if ($prefix !== str_repeat("\0", 12) || (ltrim($embedded, "\0") !== '' && $embedded !== "\0\0\0\1")) {
                return ! self::inRanges($embedded, self::BLOCKED_IPV4_RANGES);
            }
        }

        // 2002:AABB:CCDD::/48 (6to4) carries the IPv4 address AA.BB.CC.DD.
        if (str_starts_with($packed, "\x20\x02")) {
            return ! self::inRanges(substr($packed, 2, 4), self::BLOCKED_IPV4_RANGES);
        }

        return ! self::inRanges($packed, self::BLOCKED_IPV6_RANGES);
    }

    /**
     * @param  array<int, string>  $ranges
     */
    private static function inRanges(string $packed, array $ranges): bool
    {
        foreach ($ranges as $range) {
            [$subnet, $bits] = explode('/', $range);
            $subnetPacked = inet_pton($subnet);

            if ($subnetPacked === false || strlen($subnetPacked) !== strlen($packed)) {
                continue;
            }

            if (self::prefixMatches($packed, $subnetPacked, (int) $bits)) {
                return true;
            }
        }

        return false;
    }

    private static function prefixMatches(string $address, string $subnet, int $bits): bool
    {
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if (strncmp($address, $subnet, $bytes) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($address[$bytes]) & $mask) === (ord($subnet[$bytes]) & $mask);
    }

    private function isHostName(string $host): bool
    {
        if (strlen($host) > 253 || ! preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/', $host)) {
            return false;
        }

        // The last label must contain a letter: "10.0.0.1.", "0x7f", "127.1" are not names.
        $labels = explode('.', $host);
        $tld = (string) end($labels);

        return preg_match('/[a-z]/', $tld) === 1 && ! preg_match('/^0x[0-9a-f]*$/', $tld);
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function matchesHost(string $host, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = rtrim(strtolower(trim($pattern)), '.');

            if ($pattern === '') {
                continue;
            }

            if ($pattern === $host || (str_starts_with($pattern, '*.') && Str::endsWith($host, substr($pattern, 1)))) {
                return true;
            }
        }

        return false;
    }
}
