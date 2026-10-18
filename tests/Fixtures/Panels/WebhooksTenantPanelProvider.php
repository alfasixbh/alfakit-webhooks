<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests\Fixtures\Panels;

use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\PanelProvider;
use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;

class WebhooksTenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('webhooks-team')
            ->path('webhooks-team')
            ->login()
            ->middleware(['web'])
            ->tenant(Team::class, slugAttribute: 'slug')
            ->plugin(
                WebhooksPlugin::make()
                    ->events(['order.paid' => 'Order paid', 'order.refunded' => 'Order refunded'])
                    ->backoff(jitter: false),
            );
    }
}
