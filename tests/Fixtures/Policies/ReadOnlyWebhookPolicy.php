<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests\Fixtures\Policies;

use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * May look at endpoints, never change them.
 */
class ReadOnlyWebhookPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, WebhookEndpoint $endpoint): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return false;
    }

    public function update(Authenticatable $user, WebhookEndpoint $endpoint): bool
    {
        return false;
    }

    public function delete(Authenticatable $user, WebhookEndpoint $endpoint): bool
    {
        return false;
    }
}
