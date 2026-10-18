<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models;

use AlfaSix\Alfakit\Tests\Fixtures\Models\TenantUser;

class WebhooksUser extends TenantUser
{
    protected $table = 'users';
}
