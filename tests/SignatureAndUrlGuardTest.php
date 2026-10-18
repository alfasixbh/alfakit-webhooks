<?php

declare(strict_types=1);

use AlfaSix\AlfakitWebhooks\Exceptions\UnsafeWebhookUrl;
use AlfaSix\AlfakitWebhooks\Support\Redactor;
use AlfaSix\AlfakitWebhooks\Support\Secret;
use AlfaSix\AlfakitWebhooks\Support\Signature;
use AlfaSix\AlfakitWebhooks\Support\UrlGuard;
use AlfaSix\AlfakitWebhooks\Tests\TestCase;

uses(TestCase::class);

require_once __DIR__.'/helpers.php';

function guard(bool $insecure = false, bool $private = false, array $allowed = [], array $blocked = []): UrlGuard
{
    return new UrlGuard(TestCase::current()->resolver, $insecure, $private, $allowed, $blocked);
}

function refused(UrlGuard $guard, string $url): ?string
{
    try {
        $guard->check($url);
    } catch (UnsafeWebhookUrl $exception) {
        return $exception->reason;
    }

    return null;
}

it('signs "{timestamp}.{body}" with HMAC-SHA256', function (): void {
    $body = '{"id":"evt_1","type":"order.paid"}';

    expect(Signature::compute('whsec_test', 1767225600, $body))->toBe(hash_hmac('sha256', '1767225600.'.$body, 'whsec_test'))
        ->and(Signature::header(['whsec_test'], 1767225600, $body))->toBe('t=1767225600,v1='.hash_hmac('sha256', '1767225600.'.$body, 'whsec_test'))
        ->and(Signature::header(['new', 'old'], 1, 'x'))->toBe('t=1,v1='.hash_hmac('sha256', '1.x', 'new').',v1='.hash_hmac('sha256', '1.x', 'old'));
});

it('verifies signatures like a receiver, with a timestamp tolerance', function (): void {
    $header = Signature::header(['whsec_a', 'whsec_b'], 1000, 'body');

    expect(Signature::verify($header, 'body', 'whsec_a', now: 1000))->toBeTrue()
        ->and(Signature::verify($header, 'body', 'whsec_b', now: 1100))->toBeTrue()
        ->and(Signature::verify($header, 'tampered', 'whsec_a', now: 1000))->toBeFalse()
        ->and(Signature::verify($header, 'body', 'whsec_wrong', now: 1000))->toBeFalse()
        ->and(Signature::verify($header, 'body', 'whsec_a', tolerance: 300, now: 1301))->toBeFalse()
        ->and(Signature::verify('v1=abc', 'body', 'whsec_a'))->toBeFalse()
        ->and(Signature::verify('garbage', 'body', 'whsec_a'))->toBeFalse();
});

it('generates prefixed random secrets and masks them', function (): void {
    $secret = Secret::generate();

    expect($secret)->toStartWith('whsec_')->toHaveLength(46)
        ->and(Secret::generate())->not->toBe($secret)
        ->and(Secret::mask($secret))->toBe('whsec_••••••••'.substr($secret, -4))
        ->and(Secret::mask($secret))->not->toContain(substr($secret, 6, 10));
});

it('accepts https URLs that resolve to public addresses', function (): void {
    expect(guard()->check('https://hooks.example.com/receive?x=1'))->toMatchArray([
        'scheme' => 'https', 'host' => 'hooks.example.com', 'port' => 443, 'addresses' => ['93.184.216.34'],
    ])
        ->and(guard()->check('https://receiver.test-hooks.io:8443/in')['port'])->toBe(8443)
        ->and(guard()->check('https://93.184.216.34/hook')['addresses'])->toBe(['93.184.216.34']);
});

it('requires https unless insecure URLs are allowed', function (): void {
    expect(refused(guard(), 'http://hooks.example.com'))->toBe('scheme')
        ->and(refused(guard(), 'ftp://hooks.example.com'))->toBe('scheme')
        ->and(refused(guard(), 'file:///etc/passwd'))->toBe('invalid')
        ->and(refused(guard(insecure: true), 'http://hooks.example.com'))->toBeNull()
        ->and(refused(guard(insecure: true), 'gopher://hooks.example.com'))->toBe('scheme');
});

it('blocks private, loopback, link-local and reserved IP literals', function (string $url): void {
    expect(refused(guard(), $url))->toBe('address');
})->with([
    'loopback' => 'https://127.0.0.1/hook',
    'loopback range' => 'https://127.10.0.1/hook',
    'private 10/8' => 'https://10.1.2.3/hook',
    'private 172.16/12' => 'https://172.20.0.1/hook',
    'private 192.168/16' => 'https://192.168.1.10/hook',
    'cloud metadata' => 'https://169.254.169.254/latest/meta-data',
    'carrier-grade NAT' => 'https://100.64.0.1/hook',
    'unspecified' => 'https://0.0.0.0/hook',
    'multicast' => 'https://224.0.0.1/hook',
    'broadcast' => 'https://255.255.255.255/hook',
    'IPv6 loopback' => 'https://[::1]/hook',
    'IPv6 unique local' => 'https://[fd00::1]/hook',
    'IPv6 link-local' => 'https://[fe80::1]/hook',
    'IPv4-mapped IPv6 loopback' => 'https://[::ffff:127.0.0.1]/hook',
    'IPv4-mapped IPv6 metadata' => 'https://[::ffff:a9fe:a9fe]/hook',
    'NAT64 private' => 'https://[64:ff9b::10.0.0.1]/hook',
    '6to4 private' => 'https://[2002:c0a8:0101::1]/hook',
]);

it('blocks host names that resolve to a private address, and metadata hosts', function (): void {
    expect(refused(guard(), 'https://internal.example.com/hook'))->toBe('address')
        ->and(refused(guard(), 'https://metadata.example.com/hook'))->toBe('address')
        // One private address among public ones is enough (DNS rebinding).
        ->and(refused(guard(), 'https://rebind.example.com/hook'))->toBe('address')
        ->and(refused(guard(), 'https://mapped.example.com/hook'))->toBe('address')
        ->and(refused(guard(), 'https://localhost/hook'))->toBe('host')
        ->and(refused(guard(), 'https://app.localhost/hook'))->toBe('host')
        ->and(refused(guard(), 'https://metadata.google.internal/computeMetadata/v1/'))->toBe('host')
        ->and(refused(guard(), 'https://anything.internal/hook'))->toBe('host')
        ->and(refused(guard(), 'https://unknown.example.com/hook'))->toBe('dns');
});

it('refuses numeric host names that some resolvers read as IP addresses', function (string $url): void {
    expect(refused(guard(), $url))->toBe('invalid');
})->with(['https://2130706433/hook', 'https://0x7f.1/hook', 'https://127.1/hook', 'https://017700000001/hook']);

it('refuses URLs with credentials, and blocked hosts', function (): void {
    expect(refused(guard(), 'https://user:pass@hooks.example.com/hook'))->toBe('credentials')
        ->and(refused(guard(blocked: ['*.example.com']), 'https://hooks.example.com'))->toBe('host')
        ->and(refused(guard(blocked: ['hooks.example.com']), 'https://example.com'))->toBeNull();
});

it('lets allowed hosts and allowPrivateNetworks() reach private addresses', function (): void {
    expect(refused(guard(allowed: ['internal.example.com']), 'https://internal.example.com/hook'))->toBeNull()
        ->and(refused(guard(allowed: ['*.example.com']), 'https://internal.example.com/hook'))->toBeNull()
        ->and(refused(guard(private: true), 'https://10.0.0.1/hook'))->toBeNull()
        // Metadata host names stay blocked.
        ->and(refused(guard(private: true), 'https://metadata.google.internal/'))->toBe('host');
});

it('judges IP addresses', function (): void {
    expect(UrlGuard::isPublicIp('8.8.8.8'))->toBeTrue()
        ->and(UrlGuard::isPublicIp('2606:4700::6810:84e5'))->toBeTrue()
        ->and(UrlGuard::isPublicIp('::ffff:8.8.8.8'))->toBeTrue()
        ->and(UrlGuard::isPublicIp('172.32.0.1'))->toBeTrue()
        ->and(UrlGuard::isPublicIp('172.31.255.255'))->toBeFalse()
        ->and(UrlGuard::isPublicIp('not an ip'))->toBeFalse();
});

it('redacts sensitive keys and headers and truncates bodies', function (): void {
    $redactor = new Redactor([...Redactor::DEFAULT_KEYS, 'ssn'], 40);

    expect($redactor->redactArray(['user' => ['name' => 'Ada', 'Password' => 'x', 'api-key' => 'k'], 'token' => 't']))
        ->toBe(['user' => ['name' => 'Ada', 'Password' => '[redacted]', 'api-key' => '[redacted]'], 'token' => '[redacted]'])
        ->and($redactor->redactHeaders(['Authorization' => 'Bearer x', 'X-Webhook-Signature' => 't=1,v1=a', 'Accept' => ['a', 'b']]))
        ->toBe(['Authorization' => '[redacted]', 'X-Webhook-Signature' => '[redacted]', 'Accept' => 'a, b'])
        ->and($redactor->redactBody(str_repeat('a', 100)))->toBe(str_repeat('a', 40).'…')
        ->and($redactor->redactBody('{"secret":"s"}'))->toContain('[redacted]')->not->toContain('"s"');
});
