<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models;

use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $table = 'webhooks_orders';

    protected $guarded = [];

    protected $hidden = ['internal_note'];

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getWebhookTenant(): ?Team
    {
        return $this->team;
    }
}
