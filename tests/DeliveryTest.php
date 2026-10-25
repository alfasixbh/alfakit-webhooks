<?php

declare(strict_types=1);

use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Events\WebhookDeliveryFailed;
use AlfaSix\AlfakitWebhooks\Events\WebhookEndpointDisabled;
use AlfaSix\AlfakitWebhooks\Jobs\DeliverWebhook;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\Support\Signature;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models\Order;
use AlfaSix\AlfakitWebhooks\Tests\TestCase;
use AlfaSix\AlfakitWebhooks\WebhookManager;
use AlfaSix\AlfakitWebhooks\Webhooks;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function AlfaSix\AlfakitWebhooks\Tests\endpoint;
use function AlfaSix\AlfakitWebhooks\Tests\plugin;
use function AlfaSix\AlfakitWebhooks\Tests\user;

uses(TestCase::class);

require_once __DIR__.'/helpers.php';

it('sends a signed JSON POST to every subscribed endpoint', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $paid = endpoint(['headers' => ['Authorization' => 'Bearer receiver-token']]);
    $all = endpoint(['url' => 'https://example.com/all', 'events' => [WebhookEndpoint::ALL_EVENTS]]);
    endpoint(['url' => 'https://example.com/refunds', 'events' => ['order.refunded']]);
    endpoint(['url' => 'https://example.com/off', 'events' => ['order.paid'], 'is_enabled' => false]);

    $event = Webhooks::dispatch('order.paid', ['id' => 42, 'total' => 1999]);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event?->id)->toStartWith('evt_');

    Http::assertSentCount(2);

    Http::assertSent(function (Request $request) use ($paid, $event): bool {
        if ($request->url() !== $paid->url) {
            return false;
        }

        $body = $request->body();
        $timestamp = (int) $request->header('X-Webhook-Timestamp')[0];

        expect(json_decode($body, true))->toMatchArray(['id' => $event?->id, 'type' => 'order.paid', 'data' => ['id' => 42, 'total' => 1999]])
            ->and($request->method())->toBe('POST')
            ->and($request->header('Content-Type')[0])->toBe('application/json')
            ->and($request->header('X-Webhook-Id')[0])->toBe($event?->id)
            ->and($request->header('X-Webhook-Event')[0])->toBe('order.paid')
            ->and($request->header('X-Webhook-Attempt')[0])->toBe('1')
            ->and($request->header('Authorization')[0])->toBe('Bearer receiver-token')
            ->and(Signature::verify($request->header('X-Webhook-Signature')[0], $body, $paid->secret))->toBeTrue()
            ->and($request->header('X-Webhook-Signature')[0])->toBe(Signature::header([$paid->secret], $timestamp, $body));

        return true;
    });

    $delivery = WebhookDelivery::query()->where('endpoint_id', $paid->id)->sole();

    expect($delivery->status)->toBe(DeliveryStatus::Succeeded)
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->duration_ms)->toBeInt()
        ->and($delivery->request_headers['Authorization'])->toBe('[redacted]')
        ->and($delivery->request_headers['X-Webhook-Signature'])->toBe('[redacted]')
        ->and($delivery->response_body)->toContain('"ok": true')
        ->and($paid->refresh()->last_success_at)->not->toBeNull()
        ->and(WebhookDelivery::query()->where('endpoint_id', $all->id)->sole()->succeeded())->toBeTrue();
});

// docs: "`ping` and `*` are reserved" — `ping` is the test event of sendTest().
it('refuses to dispatch the reserved ping and * event names', function (string $name): void {
    Http::fake();
    endpoint(['url' => 'https://example.com/all', 'events' => [WebhookEndpoint::ALL_EVENTS]]);

    expect(fn () => Webhooks::dispatch($name, ['x' => 1]))->toThrow(InvalidArgumentException::class)
        ->and(WebhookEvent::query()->count())->toBe(0);

    Http::assertNothingSent();
})->with(['ping', '*', ' ping ']);

it('stores nothing when no endpoint subscribes', function (): void {
    Http::fake();
    endpoint(['events' => ['order.refunded']]);

    expect(Webhooks::dispatch('order.paid', ['id' => 1]))->toBeNull()
        ->and(WebhookEvent::query()->count())->toBe(0);

    Http::assertNothingSent();
});

it('queues deliveries on the configured queue after the transaction commits', function (): void {
    Queue::fake();
    plugin()->queue('webhooks', 'database');
    endpoint();

    Webhooks::dispatch('order.paid', ['id' => 1]);

    Queue::assertPushedOn('webhooks', DeliverWebhook::class, fn (DeliverWebhook $job): bool => $job->connection === 'database' && $job->afterCommit === true);
});

it('retries failures with exponential backoff, one logged attempt each', function (): void {
    Queue::fake();
    Http::fake(['*' => Http::response('Server error', 500)]);
    $endpoint = endpoint();
    plugin()->retries(3)->backoff(base: 10, multiplier: 3, max: 60, jitter: false)->disableAfterConsecutiveFailures(null);

    Webhooks::dispatch('order.paid', ['id' => 1]);

    $delays = [];

    // Run the queued jobs one after the other, as a worker would.
    for ($i = 0; $i < 10; $i++) {
        $jobs = Queue::pushed(DeliverWebhook::class)->filter(fn (DeliverWebhook $job): bool => WebhookDelivery::query()->find($job->deliveryId)?->isPending() === true);

        if ($jobs->isEmpty()) {
            break;
        }

        foreach ($jobs as $job) {
            $delays[] = $job->delay === null ? 0 : (int) round(now()->diffInSeconds($job->delay, true));
            app()->call([$job, 'handle']);
        }
    }

    $attempts = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->orderBy('id')->get();

    expect($attempts)->toHaveCount(4)
        ->and($attempts->pluck('attempt')->all())->toBe([1, 2, 3, 4])
        ->and($attempts->every(fn (WebhookDelivery $delivery): bool => $delivery->failed() && $delivery->response_status === 500))->toBeTrue()
        ->and($attempts->pluck('event_id')->unique())->toHaveCount(1)
        ->and($delays)->toBe([0, 10, 30, 60])
        ->and($attempts[0]->next_retry_at)->not->toBeNull()
        ->and($attempts[3]->next_retry_at)->toBeNull()
        ->and($attempts[0]->error)->toContain('500')
        ->and($endpoint->refresh()->consecutive_failures)->toBe(4);

    Http::assertSentCount(4);
    Http::assertSent(fn (Request $request): bool => $request->header('X-Webhook-Attempt')[0] === '4');
});

it('computes backoff delays with jitter and a custom strategy', function (): void {
    $plugin = plugin()->backoff(base: 100, multiplier: 2, max: 1000, jitter: true);

    expect($plugin->getBackoffDelay(1))->toBeGreaterThanOrEqual(100)->toBeLessThanOrEqual(110)
        ->and($plugin->getBackoffDelay(3))->toBeGreaterThanOrEqual(400)->toBeLessThanOrEqual(440)
        ->and($plugin->getBackoffDelay(10))->toBeGreaterThanOrEqual(1000)->toBeLessThanOrEqual(1100);

    $plugin->backoffUsing(fn (int $attempt): int => [5, 50][$attempt - 1] ?? 500);

    expect($plugin->getBackoffDelay(1))->toBe(5)
        ->and($plugin->getBackoffDelay(2))->toBe(50)
        ->and($plugin->getBackoffDelay(7))->toBe(500);
});

it('does not follow redirects, even to a private address', function (): void {
    Http::fake([
        'hooks.example.com/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin']),
        '127.0.0.1/*' => Http::response('internal', 200),
    ]);
    plugin()->retries(0);
    $endpoint = endpoint();

    Webhooks::dispatch('order.paid', ['id' => 1]);

    Http::assertSentCount(1);
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '127.0.0.1'));

    $delivery = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->sole();

    expect($delivery->status)->toBe(DeliveryStatus::Failed)
        ->and($delivery->response_status)->toBe(302)
        ->and($delivery->error)->toContain('redirect');
});

it('re-checks the URL at send time and never sends to an address that now resolves privately', function (): void {
    Http::fake();
    plugin()->retries(3);
    $endpoint = endpoint(['url' => 'https://hooks.example.com/receive']);

    // DNS rebinding: the name pointed to a public address when the endpoint was saved.
    TestCase::current()->resolver->hosts['hooks.example.com'] = ['10.0.0.8'];

    Webhooks::dispatch('order.paid', ['id' => 1]);

    Http::assertNothingSent();

    $delivery = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->sole();

    expect($delivery->status)->toBe(DeliveryStatus::Failed)
        ->and($delivery->response_status)->toBeNull()
        ->and($delivery->error)->toBe(__('alfakit-webhooks::webhooks.url.private_address'))
        // A blocked address is permanent: no retry.
        ->and(WebhookDelivery::query()->count())->toBe(1)
        ->and(TestCase::current()->resolver->lookups)->toContain('hooks.example.com');
});

it('records connection errors and timeouts as failures', function (): void {
    Http::fake(fn () => throw new Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out'));
    plugin()->retries(0);
    $endpoint = endpoint();

    Webhooks::dispatch('order.paid', ['id' => 1]);

    $delivery = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->sole();

    expect($delivery->status)->toBe(DeliveryStatus::Failed)
        ->and($delivery->error)->toBe(__('alfakit-webhooks::webhooks.errors.timeout'));
});

it('disables an endpoint after consecutive failures and notifies its creator', function (): void {
    Event::fake([WebhookEndpointDisabled::class, WebhookDeliveryFailed::class]);
    Http::fake(['*' => Http::response('nope', 503)]);
    $admin = user();
    $endpoint = endpoint(['created_by_type' => $admin->getMorphClass(), 'created_by_id' => (string) $admin->id]);
    plugin()->retries(0)->disableAfterConsecutiveFailures(3);

    foreach (range(1, 3) as $i) {
        Webhooks::dispatch('order.paid', ['id' => $i]);
    }

    $endpoint->refresh();

    expect($endpoint->is_enabled)->toBeFalse()
        ->and($endpoint->isAutoDisabled())->toBeTrue()
        ->and($endpoint->disabled_reason)->toContain('3')
        ->and($endpoint->consecutive_failures)->toBe(3);

    Event::assertDispatched(WebhookEndpointDisabled::class, fn (WebhookEndpointDisabled $event): bool => $event->endpoint->is($endpoint));
    Event::assertDispatchedTimes(WebhookDeliveryFailed::class, 3);

    $notification = DatabaseNotification::query()->sole();

    expect($notification->notifiable_id)->toBe($admin->id)
        ->and(json_encode($notification->data))->toContain('hooks.example.com');

    // Disabled: further events are not sent.
    Webhooks::dispatch('order.paid', ['id' => 4]);
    Http::assertSentCount(3);
});

it('resets the failure count on success and notifies the users of notifyOnDisable()', function (): void {
    $admins = [user('Grace'), user('Linus')];
    plugin()->retries(0)->disableAfterConsecutiveFailures(2)->notifyOnDisable(fn (WebhookEndpoint $endpoint): array => $admins);
    $endpoint = endpoint();

    Http::fakeSequence()->push('', 500)->push('', 200)->push('', 500)->push('', 500);

    Webhooks::dispatch('order.paid', ['id' => 1]);
    expect($endpoint->refresh()->consecutive_failures)->toBe(1);

    Webhooks::dispatch('order.paid', ['id' => 2]);
    expect($endpoint->refresh()->consecutive_failures)->toBe(0);

    Webhooks::dispatch('order.paid', ['id' => 3]);
    Webhooks::dispatch('order.paid', ['id' => 4]);

    expect($endpoint->refresh()->is_enabled)->toBeFalse()
        ->and(DatabaseNotification::query()->count())->toBe(2);
});

it('skips queued attempts of an endpoint disabled in the meantime', function (): void {
    Queue::fake();
    $endpoint = endpoint();
    Webhooks::dispatch('order.paid', ['id' => 1]);
    $endpoint->disable();

    Http::fake();
    Queue::pushed(DeliverWebhook::class)->each(fn (DeliverWebhook $job) => app()->call([$job, 'handle']));

    Http::assertNothingSent();
    expect(WebhookDelivery::query()->sole()->status)->toBe(DeliveryStatus::Skipped);
});

it('signs with the previous secret too while a rotation grace period lasts', function (): void {
    Http::fake();
    $endpoint = endpoint();
    $old = $endpoint->secret;
    $new = $endpoint->rotateSecret(now()->addHour());

    Webhooks::dispatch('order.paid', ['id' => 1]);

    Http::assertSent(function (Request $request) use ($old, $new): bool {
        $signature = $request->header('X-Webhook-Signature')[0];

        return Signature::verify($signature, $request->body(), $new)
            && Signature::verify($signature, $request->body(), $old)
            && substr_count($signature, 'v1=') === 2;
    });

    $this->travel(2)->hours();
    $endpoint->refresh()->rotateSecret();

    expect($endpoint->getSigningSecrets())->toHaveCount(1)->not->toContain($old, $new);
});

it('replays a delivery with the same event id', function (): void {
    Http::fakeSequence()->push('', 500)->push('', 200);
    plugin()->retries(0);
    $endpoint = endpoint();

    $event = Webhooks::dispatch('order.paid', ['id' => 1]);
    $failed = WebhookDelivery::query()->sole();

    $replay = app(WebhookManager::class)->replay($failed);

    expect($replay->refresh()->status)->toBe(DeliveryStatus::Succeeded)
        ->and($replay->event_id)->toBe($event?->id)
        ->and($replay->replay_of_id)->toBe($failed->id)
        ->and($replay->attempt)->toBe(1)
        ->and($endpoint->refresh()->consecutive_failures)->toBe(0);

    Http::assertSent(fn (Request $request): bool => $request->header('X-Webhook-Id')[0] === $event?->id);
});

it('sends a test ping right away, even to a disabled endpoint, without counting failures', function (): void {
    Http::fake(['*' => Http::response('{"received":true}', 202)]);
    $endpoint = endpoint(['is_enabled' => false, 'events' => ['order.refunded']]);

    $delivery = Webhooks::sendTest($endpoint);

    expect($delivery->is_test)->toBeTrue()
        ->and($delivery->status)->toBe(DeliveryStatus::Succeeded)
        ->and($delivery->response_status)->toBe(202)
        ->and($delivery->event?->name)->toBe('ping');

    Http::fake(['*' => Http::response('', 500)]);
    Webhooks::sendTest($endpoint);

    expect($endpoint->refresh()->consecutive_failures)->toBe(0)
        ->and(WebhookDelivery::query()->count())->toBe(2);
});

it('sends model events with the model payload and changed attributes', function (): void {
    Http::fake();
    endpoint(['events' => ['order.created', 'order.updated', 'order.deleted']]);

    $order = Order::query()->create(['number' => 'A-1', 'total' => 100, 'internal_note' => 'secret']);
    $order->update(['total' => 150]);
    $order->touch();
    $order->delete();

    $sent = collect(Http::recorded())->map(fn (array $pair): array => json_decode($pair[0]->body(), true));

    expect($sent->pluck('type')->all())->toBe(['order.created', 'order.updated', 'order.deleted'])
        ->and($sent[0]['data']['object'])->toMatchArray(['number' => 'A-1', 'total' => 100])
        ->and($sent[0]['data']['object'])->not->toHaveKey('internal_note')
        ->and($sent[1]['data']['previous_attributes'])->toBe(['total' => 100])
        ->and($sent[1]['data']['object']['total'])->toBe(150);
});

it('lists registered events with labels, including model events', function (): void {
    expect(plugin()->getEvents())->toMatchArray([
        'order.paid' => ['label' => 'Order paid', 'description' => null],
        'order.refunded' => ['label' => 'Order refunded', 'description' => 'A refund was issued.'],
        'invoice.sent' => ['label' => 'Invoice Sent', 'description' => null],
        'order.created' => ['label' => 'Order created', 'description' => null],
    ])->and(Webhooks::events())->toHaveKey('order.deleted');
});

it('resolves closure event labels in the current language', function (): void {
    plugin()->events([
        'order.shipped' => fn (): string => app()->getLocale() === 'de' ? 'Bestellung versandt' : 'Order shipped',
        'order.returned' => ['label' => fn (): string => 'Order returned', 'description' => fn (): string => 'The parcel came back.'],
    ]);

    app()->setLocale('de');

    expect(plugin()->getEvents())->toMatchArray([
        'order.shipped' => ['label' => 'Bestellung versandt', 'description' => null],
        'order.returned' => ['label' => 'Order returned', 'description' => 'The parcel came back.'],
        // Built-in model event labels are translated too.
        'order.created' => ['label' => 'Order erstellt', 'description' => null],
    ]);

    app()->setLocale('en');
});

it('fakes dispatching in application tests', function (): void {
    Http::fake();
    endpoint(['events' => [WebhookEndpoint::ALL_EVENTS]]);
    $fake = Webhooks::fake();

    Webhooks::dispatch('order.paid', ['id' => 7]);
    Order::query()->create(['number' => 'B-2']);

    $fake->assertDispatched('order.paid', fn (array $payload): bool => $payload['id'] === 7);
    $fake->assertDispatched('order.created');
    $fake->assertNotDispatched('order.refunded');
    $fake->assertDispatchedTimes('order.paid');
    Http::assertNothingSent();
});

it('gives the event id a whole row in the delivery details', function (): void {
    $grid = AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\RelationManagers\DeliveriesRelationManager::deliveryDetails()[0]->getChildComponents()[0];
    $entry = collect($grid->getChildComponents())->first(fn ($component) => $component->getName() === 'event_id');

    expect($entry->getColumnSpan())->toContain('full');
});
