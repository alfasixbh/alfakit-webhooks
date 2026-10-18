<?php

declare(strict_types=1);

use AlfaSix\AlfakitWebhooks\Actions\ReplayWebhookDeliveriesBulkAction;
use AlfaSix\AlfakitWebhooks\Actions\ReplayWebhookDeliveryAction;
use AlfaSix\AlfakitWebhooks\Actions\RotateWebhookSecretAction;
use AlfaSix\AlfakitWebhooks\Actions\SendTestWebhookAction;
use AlfaSix\AlfakitWebhooks\Actions\ToggleWebhookEndpointAction;
use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages\CreateWebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages\EditWebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages\ListWebhookEndpoints;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\RelationManagers\DeliveriesRelationManager;
use AlfaSix\AlfakitWebhooks\Support\SecretReveal;
use AlfaSix\AlfakitWebhooks\Tests\TestCase;
use AlfaSix\AlfakitWebhooks\Webhooks;
use Illuminate\Support\Facades\Http;

use function AlfaSix\AlfakitWebhooks\Tests\endpoint;
use function AlfaSix\AlfakitWebhooks\Tests\inertiaGet;
use function AlfaSix\AlfakitWebhooks\Tests\plugin;
use function AlfaSix\AlfakitWebhooks\Tests\user;
use function Pest\Laravel\actingAs;

uses(TestCase::class);

require_once __DIR__.'/helpers.php';

beforeEach(function (): void {
    alfakit()->setCurrentPanel(alfakit()->getPanel('webhooks'));
});

it('registers the endpoints resource with the plugin options', function (): void {
    expect(alfakit()->getPanel('webhooks')->getResources())->toContain(WebhookEndpointResource::class)
        ->and(WebhookEndpointResource::getSlug())->toBe('webhooks')
        ->and(WebhookEndpointResource::getNavigationGroup())->toBe('Developers')
        ->and(WebhookEndpointResource::getNavigationIcon())->toBe('webhook')
        ->and(WebhookEndpointResource::getNavigationLabel())->toBe('Webhooks');

    plugin()->navigationLabel('Integrations')->navigationSort(3)->label('hook')->pluralLabel('hooks');

    expect(WebhookEndpointResource::getNavigationLabel())->toBe('Integrations')
        ->and(WebhookEndpointResource::getNavigationSort())->toBe(3)
        ->and(WebhookEndpointResource::getPluralModelLabel())->toBe('hooks');
});

it('lists endpoints with their state', function (): void {
    actingAs(user());
    $enabled = endpoint(['description' => 'Billing system']);
    $off = endpoint(['url' => 'https://example.com/off', 'is_enabled' => false]);

    alfakit()->testPage(ListWebhookEndpoints::class, panel: 'webhooks')
        ->assertOk()
        ->assertCanSeeTableRecords([$enabled, $off])
        ->assertTableColumnFormattedStateSet('state', __('alfakit-webhooks::webhooks.endpoint_states.enabled'), $enabled)
        ->assertTableColumnFormattedStateSet('state', __('alfakit-webhooks::webhooks.endpoint_states.disabled'), $off)
        ->filterTable('is_enabled', true)
        ->assertCanSeeTableRecords([$enabled])
        ->assertCanNotSeeTableRecords([$off]);
});

it('creates an endpoint with a generated secret, shown once on its edit page', function (): void {
    $admin = actingAs(user());

    alfakit()->testPage(CreateWebhookEndpoint::class, panel: 'webhooks')
        ->fillForm([
            'url' => 'https://hooks.example.com/receive',
            'description' => 'CRM',
            'subscription' => 'selected',
            'events' => ['order.paid', 'order.refunded'],
            'headers' => ['X-Api-Key' => 'abc'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $endpoint = WebhookEndpoint::query()->sole();

    expect($endpoint->events)->toBe(['order.paid', 'order.refunded'])
        ->and($endpoint->headers)->toBe(['X-Api-Key' => 'abc'])
        ->and($endpoint->secret)->toStartWith('whsec_')
        ->and($endpoint->tenant_type)->toBeNull()
        ->and($endpoint->created_by_id)->toBe((string) auth()->id())
        // Encrypted at rest.
        ->and(DB::table('alfakit_webhook_endpoints')->value('secret'))->not->toContain('whsec_')
        ->and(DB::table('alfakit_webhook_endpoints')->value('headers'))->not->toContain('abc');

    // The secret is flashed for the next request (the edit page) only.
    session()->flash('alfakit-webhooks.secret.'.$endpoint->id, $endpoint->secret);
    $page = inertiaGet('/webhooks/webhooks/'.$endpoint->id.'/edit')->assertOk()->json('props.page');

    expect(json_encode($page))->toContain($endpoint->secret);

    session()->forget('alfakit-webhooks.secret.'.$endpoint->id);
    $again = inertiaGet('/webhooks/webhooks/'.$endpoint->id.'/edit')->assertOk()->json('props.page');

    expect(json_encode($again))->not->toContain($endpoint->secret)->toContain(substr($endpoint->secret, -4));
});

it('flashes the new secret after creating and redirects to the edit page', function (): void {
    actingAs(user());

    alfakit()->testPage(CreateWebhookEndpoint::class, panel: 'webhooks')
        ->fillForm(['url' => 'https://hooks.example.com/receive', 'subscription' => 'all'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(WebhookEndpointResource::getUrl('edit', ['record' => WebhookEndpoint::query()->sole()], panel: alfakit()->getPanel('webhooks')));

    expect(WebhookEndpoint::query()->sole()->events)->toBe([WebhookEndpoint::ALL_EVENTS]);
});

it('validates the URL against the SSRF guard, the events and the headers', function (array $data, array $errors): void {
    actingAs(user());

    alfakit()->testPage(CreateWebhookEndpoint::class, panel: 'webhooks')
        ->fillForm(['url' => 'https://hooks.example.com/receive', 'subscription' => 'selected', 'events' => ['order.paid'], ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);

    expect(WebhookEndpoint::query()->count())->toBe(0);
})->with([
    'no url' => [['url' => ''], ['url' => 'required']],
    'http' => [['url' => 'http://hooks.example.com/receive'], ['url']],
    'loopback' => [['url' => 'https://127.0.0.1/hook'], ['url']],
    'private dns' => [['url' => 'https://internal.example.com/hook'], ['url']],
    'metadata' => [['url' => 'https://169.254.169.254/latest'], ['url']],
    'unresolvable' => [['url' => 'https://nowhere.example.com/hook'], ['url']],
    'no event' => [['events' => []], ['events']],
    'unknown event' => [['events' => ['secret.event']], ['events.0']],
    'reserved header' => [['headers' => ['X-Webhook-Signature' => 'forged']], ['headers']],
    'host header' => [['headers' => ['Host' => 'internal']], ['headers']],
    'bad header name' => [['headers' => ['Bad Header' => 'x']], ['headers']],
]);

it('accepts http URLs in the local environment or with allowInsecureUrls()', function (): void {
    actingAs(user());

    expect(plugin()->allowsInsecureUrls())->toBeFalse();

    app()->detectEnvironment(fn (): string => 'local');
    expect(plugin()->allowsInsecureUrls())->toBeTrue();
    app()->detectEnvironment(fn (): string => 'testing');

    plugin()->allowInsecureUrls();

    alfakit()->testPage(CreateWebhookEndpoint::class, panel: 'webhooks')
        ->fillForm(['url' => 'http://hooks.example.com/receive', 'subscription' => 'all'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(WebhookEndpoint::query()->count())->toBe(1);
});

it('edits an endpoint, keeps its headers and re-enables it after an automatic disable', function (): void {
    actingAs(user());
    $endpoint = endpoint(['headers' => ['X-Api-Key' => 'abc'], 'events' => ['order.paid']]);
    $endpoint->forceFill(['consecutive_failures' => 5])->disable('Disabled automatically');

    alfakit()->testPage(EditWebhookEndpoint::class, ['record' => $endpoint], panel: 'webhooks')
        ->assertFormSet(['url' => $endpoint->url, 'subscription' => 'selected', 'events' => ['order.paid'], 'headers' => ['X-Api-Key' => 'abc'], 'is_enabled' => false])
        ->fillForm(['is_enabled' => true, 'subscription' => 'all'])
        ->call('save')
        ->assertHasNoFormErrors();

    $endpoint->refresh();

    expect($endpoint->is_enabled)->toBeTrue()
        ->and($endpoint->events)->toBe([WebhookEndpoint::ALL_EVENTS])
        ->and($endpoint->consecutive_failures)->toBe(0)
        ->and($endpoint->disabled_reason)->toBeNull()
        ->and($endpoint->headers)->toBe(['X-Api-Key' => 'abc']);
});

it('rotates the secret, keeping the old one for the grace period, and reveals the new one', function (): void {
    actingAs(user());
    $endpoint = endpoint();
    $old = $endpoint->secret;

    $page = alfakit()->testPage(EditWebhookEndpoint::class, ['record' => $endpoint], panel: 'webhooks')
        ->callAction(RotateWebhookSecretAction::class, ['keep_previous_for' => '24'])
        ->assertHasNoActionErrors()
        ->assertNotified(__('alfakit-webhooks::webhooks.actions.rotate.notification'));

    $endpoint->refresh();

    expect($endpoint->secret)->not->toBe($old)
        ->and($endpoint->previous_secret)->toBe($old)
        ->and($endpoint->previous_secret_expires_at?->isFuture())->toBeTrue()
        ->and($endpoint->previous_secret_expires_at?->isAfter(now()->addHours(23)))->toBeTrue()
        // The reloaded page shows the new secret once.
        ->and(json_encode($page->getPage()))->toContain($endpoint->secret)->not->toContain($old);

    // A secret that isn't the endpoint's current one is never revealed.
    session()->flash('alfakit-webhooks.secret.'.$endpoint->id, $old);
    expect(SecretReveal::get($endpoint))->toBeNull();
});

it('sends a test event from the edit page and shows the response', function (): void {
    actingAs(user());
    TestCase::current()->receive(200, '{"received":true}');
    $endpoint = endpoint();

    alfakit()->testPage(EditWebhookEndpoint::class, ['record' => $endpoint], panel: 'webhooks')
        ->callAction(SendTestWebhookAction::class)
        ->assertNotified(__('alfakit-webhooks::webhooks.actions.test.notification.success', ['status' => 200, 'duration' => WebhookDelivery::query()->sole()->duration_ms]));

    Http::assertSent(fn ($request): bool => $request->header('X-Webhook-Event')[0] === 'ping');

    TestCase::current()->receive(500, 'Nope');

    alfakit()->testPage(EditWebhookEndpoint::class, ['record' => $endpoint], panel: 'webhooks')
        ->callAction(SendTestWebhookAction::class)
        ->assertNotified(__('alfakit-webhooks::webhooks.actions.test.notification.failed_status', ['status' => 500, 'duration' => WebhookDelivery::query()->latest('id')->first()?->duration_ms]));
});

it('disables and enables an endpoint from the table', function (): void {
    actingAs(user());
    $endpoint = endpoint();

    alfakit()->testPage(ListWebhookEndpoints::class, panel: 'webhooks')
        ->callTableAction(ToggleWebhookEndpointAction::class, $endpoint);

    expect($endpoint->refresh()->is_enabled)->toBeFalse()
        ->and($endpoint->isAutoDisabled())->toBeFalse();

    alfakit()->testPage(ListWebhookEndpoints::class, panel: 'webhooks')
        ->callTableAction(ToggleWebhookEndpointAction::class, $endpoint);

    expect($endpoint->refresh()->is_enabled)->toBeTrue();
});

it('shows the deliveries log of the endpoint, filters it and replays a delivery', function (): void {
    actingAs(user());
    plugin()->retries(0);
    $endpoint = endpoint(['events' => ['order.paid', 'order.refunded']]);
    $other = endpoint(['url' => 'https://example.com/other', 'events' => ['order.refunded']]);

    TestCase::current()->receive(500);
    Webhooks::dispatch('order.paid', ['id' => 1]);
    TestCase::current()->receive(200);
    Webhooks::dispatch('order.refunded', ['id' => 2]);

    $failed = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->where('status', DeliveryStatus::Failed)->sole();
    $succeeded = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->where('status', DeliveryStatus::Succeeded)->sole();
    $foreign = WebhookDelivery::query()->where('endpoint_id', $other->id)->sole();

    alfakit()->testPage(EditWebhookEndpoint::class, ['record' => $endpoint], panel: 'webhooks')
        ->relationManager(DeliveriesRelationManager::class)
        ->assertCanSeeTableRecords([$failed, $succeeded])
        ->assertCanNotSeeTableRecords([$foreign])
        ->filterTable('status', [DeliveryStatus::Failed->value])
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$succeeded])
        ->resetTableFilters()
        ->filterTable('event', 'order.refunded')
        ->assertCanNotSeeTableRecords([$failed])
        ->resetTableFilters()
        ->callTableAction(ReplayWebhookDeliveryAction::class, $failed)
        ->assertNotified(__('alfakit-webhooks::webhooks.actions.replay.notification'));

    $replay = WebhookDelivery::query()->where('replay_of_id', $failed->id)->sole();

    expect($replay->status)->toBe(DeliveryStatus::Succeeded)
        ->and($replay->event_id)->toBe($failed->event_id);
});

it('replays selected deliveries in bulk', function (): void {
    actingAs(user());
    plugin()->retries(0);
    TestCase::current()->receive(500);
    $endpoint = endpoint();

    Webhooks::dispatch('order.paid', ['id' => 1]);
    Webhooks::dispatch('order.paid', ['id' => 2]);
    $deliveries = WebhookDelivery::query()->get();

    TestCase::current()->receive(200);

    alfakit()->testPage(EditWebhookEndpoint::class, ['record' => $endpoint], panel: 'webhooks')
        ->relationManager(DeliveriesRelationManager::class)
        ->callTableBulkAction(ReplayWebhookDeliveriesBulkAction::class, $deliveries);

    expect(WebhookDelivery::query()->whereNotNull('replay_of_id')->where('status', DeliveryStatus::Succeeded)->count())->toBe(2);
});

it('forbids users the authorize() callback rejects', function (): void {
    actingAs(user('Vic Viewer', role: 'viewer'));
    $endpoint = endpoint();

    inertiaGet('/webhooks/webhooks')->assertForbidden();
    inertiaGet('/webhooks/webhooks/create')->assertForbidden();
    inertiaGet('/webhooks/webhooks/'.$endpoint->id.'/edit')->assertForbidden();

    expect(WebhookEndpointResource::canViewAny())->toBeFalse()
        ->and(WebhookEndpointResource::canEdit($endpoint))->toBeFalse();
});

it('hides the replay of test deliveries and of disabled endpoints', function (): void {
    Http::fake();
    $endpoint = endpoint();
    $test = Webhooks::sendTest($endpoint);

    expect(ReplayWebhookDeliveryAction::canReplay($test))->toBeFalse();

    Webhooks::dispatch('order.paid', ['id' => 1]);
    $delivery = WebhookDelivery::query()->where('is_test', false)->sole();

    expect(ReplayWebhookDeliveryAction::canReplay($delivery))->toBeTrue();

    $endpoint->disable();

    expect(ReplayWebhookDeliveryAction::canReplay($delivery->refresh()))->toBeFalse();
});
