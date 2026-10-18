<?php

declare(strict_types=1);

use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages\CreateWebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages\ListWebhookEndpoints;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models\Order;
use AlfaSix\AlfakitWebhooks\Tests\TestCase;
use AlfaSix\AlfakitWebhooks\Webhooks;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function AlfaSix\AlfakitWebhooks\Tests\endpoint;
use function AlfaSix\AlfakitWebhooks\Tests\inertiaGet;
use function AlfaSix\AlfakitWebhooks\Tests\plugin;
use function AlfaSix\AlfakitWebhooks\Tests\team;
use function AlfaSix\AlfakitWebhooks\Tests\user;
use function Pest\Laravel\actingAs;

uses(TestCase::class);

require_once __DIR__.'/helpers.php';

/**
 * Alpha and Beta; Ada belongs to Alpha only.
 *
 * @return array{alpha: AlfaSix\Alfakit\Tests\Fixtures\Models\Team, beta: AlfaSix\Alfakit\Tests\Fixtures\Models\Team, ada: AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models\WebhooksUser}
 */
function whTeams(): array
{
    $alpha = team('Alpha');
    $beta = team('Beta');
    $ada = user('Ada');
    $ada->teams()->attach($alpha);

    return compact('alpha', 'beta', 'ada');
}

it('lists and opens only the endpoints of the current tenant', function (): void {
    ['alpha' => $alpha, 'beta' => $beta, 'ada' => $ada] = whTeams();
    actingAs($ada);
    $mine = endpoint(['url' => 'https://example.com/alpha'], $alpha);
    $theirs = endpoint(['url' => 'https://example.com/beta'], $beta);
    $global = endpoint(['url' => 'https://example.com/global']);

    alfakit()->testPage(ListWebhookEndpoints::class, ['tenant' => $alpha], panel: 'webhooks-team')
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs, $global]);

    inertiaGet('/webhooks-team/alpha/webhooks/'.$mine->id.'/edit')->assertOk();
    inertiaGet('/webhooks-team/alpha/webhooks/'.$theirs->id.'/edit')->assertNotFound();
    inertiaGet('/webhooks-team/alpha/webhooks/'.$global->id.'/edit')->assertNotFound();
    // Not a member of Beta.
    inertiaGet('/webhooks-team/beta/webhooks')->assertNotFound();
});

it('creates endpoints for the current tenant', function (): void {
    ['alpha' => $alpha, 'ada' => $ada] = whTeams();
    actingAs($ada);

    alfakit()->testPage(CreateWebhookEndpoint::class, ['tenant' => $alpha], panel: 'webhooks-team')
        ->fillForm(['url' => 'https://hooks.example.com/alpha', 'subscription' => 'selected', 'events' => ['order.paid']])
        ->call('create')
        ->assertHasNoFormErrors();

    $endpoint = WebhookEndpoint::query()->sole();

    expect($endpoint->isOwnedByTenant($alpha))->toBeTrue()
        ->and($endpoint->tenant_type)->toBe($alpha->getMorphClass())
        ->and($endpoint->tenant?->is($alpha))->toBeTrue();
});

it("sends tenant events only to that tenant's endpoints (and to global ones)", function (): void {
    ['alpha' => $alpha, 'beta' => $beta] = whTeams();
    Http::fake();
    endpoint(['url' => 'https://example.com/alpha'], $alpha);
    endpoint(['url' => 'https://example.com/beta'], $beta);
    endpoint(['url' => 'https://example.com/global']);

    $event = Webhooks::dispatch('order.paid', ['id' => 1], tenant: $alpha);

    expect($event?->tenant_id)->toBe((string) $alpha->id);

    $urls = collect(Http::recorded())->map(fn (array $pair): string => $pair[0]->url())->sort()->values()->all();

    expect($urls)->toBe(['https://example.com/alpha', 'https://example.com/global']);
});

it('sends events without a tenant to global endpoints only', function (): void {
    ['alpha' => $alpha] = whTeams();
    Http::fake();
    endpoint(['url' => 'https://example.com/alpha'], $alpha);
    endpoint(['url' => 'https://example.com/global']);

    Webhooks::dispatch('order.paid', ['id' => 1], tenant: false);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.com/global');
});

it('can keep global endpoints away from tenant events', function (): void {
    ['alpha' => $alpha] = whTeams();
    Http::fake();
    plugin()->globalEndpointsReceiveTenantEvents(false);
    endpoint(['url' => 'https://example.com/alpha'], $alpha);
    endpoint(['url' => 'https://example.com/global']);

    Webhooks::dispatch('order.paid', ['id' => 1], tenant: $alpha);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.com/alpha');
});

it('uses the current tenant of a panel request, and the model tenant for model events', function (): void {
    ['alpha' => $alpha, 'beta' => $beta] = whTeams();
    Http::fake();
    plugin()->globalEndpointsReceiveTenantEvents(false);
    endpoint(['url' => 'https://example.com/alpha', 'events' => ['order.paid', 'order.created']], $alpha);
    endpoint(['url' => 'https://example.com/beta', 'events' => ['order.paid', 'order.created']], $beta);

    alfakit()->setTenant($beta);
    Webhooks::dispatch('order.paid', ['id' => 1]);
    alfakit()->setTenant(null);

    // The order belongs to Alpha, whatever the current tenant.
    Order::query()->create(['number' => 'A-1', 'team_id' => $alpha->id]);

    $sent = collect(Http::recorded())->map(fn (array $pair): array => [$pair[0]->url(), $pair[0]->header('X-Webhook-Event')[0]])->all();

    expect($sent)->toBe([
        ['https://example.com/beta', 'order.paid'],
        ['https://example.com/alpha', 'order.created'],
    ]);
});

it('sends test events with the endpoint tenant', function (): void {
    ['alpha' => $alpha] = whTeams();
    Http::fake();

    $delivery = Webhooks::sendTest(endpoint([], $alpha));

    expect($delivery->event?->tenant_id)->toBe((string) $alpha->id)
        ->and(WebhookEvent::query()->sole()->tenant_type)->toBe($alpha->getMorphClass());
});

it("never exposes another tenant's deliveries through the endpoint's relation manager", function (): void {
    ['alpha' => $alpha, 'beta' => $beta, 'ada' => $ada] = whTeams();
    Http::fake();
    actingAs($ada);
    $mine = endpoint(['url' => 'https://example.com/alpha'], $alpha);
    $theirs = endpoint(['url' => 'https://example.com/beta'], $beta);

    Webhooks::dispatch('order.paid', ['secret' => 'beta-only'], tenant: $beta);
    Webhooks::dispatch('order.paid', ['id' => 'alpha'], tenant: $alpha);

    $page = inertiaGet('/webhooks-team/alpha/webhooks/'.$mine->id.'/edit')->assertOk()->json('props.page');

    expect(json_encode($page))->not->toContain('beta-only')
        ->and(WebhookDelivery::query()->where('endpoint_id', $theirs->id)->count())->toBe(1);
});

it('follows the endpoint policy when the plugin has no authorize() callback', function (): void {
    ['alpha' => $alpha, 'ada' => $ada] = whTeams();
    Illuminate\Support\Facades\Gate::policy(WebhookEndpoint::class, AlfaSix\AlfakitWebhooks\Tests\Fixtures\Policies\ReadOnlyWebhookPolicy::class);
    actingAs($ada);
    Http::fake();
    $endpoint = endpoint([], $alpha);
    Webhooks::dispatch('order.paid', ['id' => 1], tenant: $alpha);
    $delivery = WebhookDelivery::query()->sole();

    alfakit()->testPage(ListWebhookEndpoints::class, ['tenant' => $alpha], panel: 'webhooks-team')
        ->assertCanSeeTableRecords([$endpoint])
        ->assertActionHidden('create')
        ->assertTableActionHidden(AlfaSix\AlfakitWebhooks\Actions\SendTestWebhookAction::class, $endpoint)
        ->assertTableActionHidden(AlfaSix\AlfakitWebhooks\Actions\ToggleWebhookEndpointAction::class, $endpoint);

    inertiaGet('/webhooks-team/alpha/webhooks/create')->assertForbidden();
    inertiaGet('/webhooks-team/alpha/webhooks/'.$endpoint->id.'/edit')->assertForbidden();

    alfakit()->setCurrentPanel(alfakit()->getPanel('webhooks-team'));

    expect(AlfaSix\AlfakitWebhooks\Actions\ReplayWebhookDeliveryAction::isAuthorizedFor($delivery))->toBeFalse();
});
