<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests\Fixtures\Panels;

use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\PanelProvider;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models\Order;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Model;

class WebhooksPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('webhooks')
            ->path('webhooks')
            ->login()
            ->middleware(['web'])
            ->databaseNotifications()
            ->plugin(
                WebhooksPlugin::make()
                    ->events([
                        'order.paid' => 'Order paid',
                        'order.refunded' => ['label' => 'Order refunded', 'description' => 'A refund was issued.'],
                        'invoice.sent',
                    ])
                    ->modelEvents([Order::class])
                    ->backoff(jitter: false)
                    ->disableAfterConsecutiveFailures(5)
                    ->navigationGroup('Developers')
                    // Viewers may not manage webhooks.
                    ->authorize(fn (?Model $user): bool => $user !== null && $user->getAttribute('role') !== 'viewer'),
            );
    }
}
