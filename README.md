# AlfaKit Webhooks

<p><picture><source media="(prefers-color-scheme: dark)" srcset="../../../art/alfasix/alfa-six-micro-white.svg"><img src="../../../art/alfasix/alfa-six-micro-navy.svg" alt="" width="24" height="18" align="top"></picture> <sub>by <a href="https://alfasixbh.com">Alfa Six</a></sub></p>

Outgoing webhooks managed in an [AlfaKit](https://alfakit.dev) panel. Your users add endpoints and pick the events they want; your code dispatches events; the plugin delivers them queued, signed with HMAC-SHA256, retried with exponential backoff, logged (redacted) with replay, and protected against SSRF. It renders in React, Vue and Svelte with AlfaKit's built-in components and has no dependencies beyond Laravel.

## Installation

```bash
composer require alfasix/alfakit-webhooks
php artisan vendor:publish --tag=alfakit-webhooks-migrations
php artisan migrate
```

Run a queue worker, and schedule `Schedule::command('alfakit-webhooks:prune')->daily();`.

## Register

```php
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;

$panel->plugin(
    WebhooksPlugin::make()
        ->events([
            'order.paid' => 'Order paid',
            'order.refunded' => 'Order refunded',
        ])
        ->modelEvents([Customer::class]) // customer.created, customer.updated, customer.deleted
        ->authorize(fn (User $user): bool => $user->is_admin),
);
```

## Dispatch

```php
use AlfaSix\AlfakitWebhooks\Webhooks;

Webhooks::dispatch('order.paid', ['order' => $order->only(['id', 'total'])]);
```

## Verify (receiver)

```php
[$t, $v1] = /* parse "t=…,v1=…" from the X-Webhook-Signature header */;
$valid = abs(time() - $t) <= 300
    && hash_equals(hash_hmac('sha256', $t.'.'.$request->getContent(), $secret), $v1);
```

The [documentation](https://alfakit.dev/docs/plugins/webhooks) covers every option, the signature scheme with PHP and Node.js receivers, retries, auto-disable, tenancy, authorisation and the SSRF protection.

## License

MIT. See [LICENSE.md](LICENSE.md).
