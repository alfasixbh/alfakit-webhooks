<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks;

use AlfaSix\Alfakit\AlfakitManager;
use AlfaSix\Alfakit\Mail\CoreMails;
use AlfaSix\Alfakit\Mail\MailDefinition;
use AlfaSix\Alfakit\Mail\Mails;
use AlfaSix\AlfakitWebhooks\Commands\PruneWebhookDeliveriesCommand;
use AlfaSix\AlfakitWebhooks\Contracts\ResolvesHosts;
use AlfaSix\AlfakitWebhooks\Notifications\EndpointDisabledMail;
use AlfaSix\AlfakitWebhooks\Support\DnsResolver;
use Illuminate\Support\ServiceProvider;

class AlfakitWebhooksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebhookManager::class);
        $this->app->bindIf(ResolvesHosts::class, DnsResolver::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'alfakit-webhooks');

        // The "endpoint disabled" email (`mailOnDisable()`), for the mail preview page and editable templates.
        Mails::register(
            MailDefinition::make(EndpointDisabledMail::TEMPLATE)
                ->label(static fn (): string => (string) __('alfakit-webhooks::webhooks.mail_templates.endpoint_disabled'))
                ->group(static fn (): string => (string) __('alfakit-webhooks::webhooks.mail_templates.group'))
                ->preview(static fn (): EndpointDisabledMail => new EndpointDisabledMail('https://hooks.example.com/orders', 15))
                ->editable(
                    mergeTags: static fn (): array => CoreMails::tags(['url', 'failures', 'app']),
                    subject: static fn (): string => (string) __('alfakit-webhooks::webhooks.notifications.disabled.title'),
                    body: static fn (): string => MailDefinition::paragraphs(
                        (string) __('alfakit-webhooks::webhooks.notifications.disabled.body', ['url' => '{{ url }}', 'count' => '{{ failures }}']),
                    ),
                ),
        );

        // Panels (and so the plugins' model listeners) are built when the AlfaKit manager is
        // resolved; make sure that happens in queue workers and with cached routes too.
        $this->app->booted(function (): void {
            $this->app->make(AlfakitManager::class)->getPanels();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([PruneWebhookDeliveriesCommand::class]);

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'alfakit-webhooks-migrations');

            $this->publishes([
                __DIR__.'/../resources/lang' => $this->app->langPath('vendor/alfakit-webhooks'),
            ], 'alfakit-webhooks-translations');
        }
    }
}
