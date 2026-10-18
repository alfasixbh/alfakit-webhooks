<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests;

use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models\WebhooksUser;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Testing\TestResponse;
use RuntimeException;

/**
 * Perform an Inertia GET as the renderer does.
 *
 * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
 */
function inertiaGet(string $url): TestResponse
{
    return \Pest\Laravel\get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);
}

function user(string $name = 'Ada Admin', ?string $role = null): WebhooksUser
{
    return WebhooksUser::query()->create(['name' => $name, 'email' => uniqid('user', true).'@example.com', 'password' => 'password', 'role' => $role]);
}

function team(string $name): Team
{
    return Team::query()->create(['name' => $name, 'slug' => strtolower($name)]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function endpoint(array $attributes = [], ?Team $team = null): WebhookEndpoint
{
    $endpoint = new WebhookEndpoint;
    $endpoint->forceFill([
        'url' => 'https://hooks.example.com/receive',
        'events' => ['order.paid'],
        ...$attributes,
    ]);
    $endpoint->setTenant($team);
    $endpoint->save();

    return $endpoint->refresh();
}

function plugin(string $panel = 'webhooks'): WebhooksPlugin
{
    $plugin = alfakit()->getPanel($panel)->getPlugin(WebhooksPlugin::ID);

    return $plugin instanceof WebhooksPlugin ? $plugin : throw new RuntimeException('The webhooks plugin is not registered.');
}

/**
 * @param  array<string, mixed>  $data
 * @return array<string, mixed>
 */
function json(mixed $data): array
{
    return is_string($data) ? (array) json_decode($data, true) : [];
}
