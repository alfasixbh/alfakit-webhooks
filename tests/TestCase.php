<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Tests;

use AlfaSix\Alfakit\Tests\TestCase as AlfakitTestCase;
use AlfaSix\AlfakitWebhooks\AlfakitWebhooksServiceProvider;
use AlfaSix\AlfakitWebhooks\Contracts\ResolvesHosts;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Models\WebhooksUser;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Panels\WebhooksPanelProvider;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Panels\WebhooksTenantPanelProvider;
use AlfaSix\AlfakitWebhooks\Tests\Fixtures\Support\FakeResolver;
use Illuminate\Support\Facades\Http;
use LogicException;
use Pest\TestSuite;

abstract class TestCase extends AlfakitTestCase
{
    public FakeResolver $resolver;

    /** The status `receive()` answers with; change it between requests. */
    public int $responseStatus = 200;

    public string $responseBody = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new FakeResolver;
        $this->app->instance(ResolvesHosts::class, $this->resolver);

        // No test may reach the network.
        Http::preventStrayRequests();
    }

    /**
     * Fake the receiving end: every request gets `$responseStatus` / `$responseBody`.
     */
    public function receive(int $status = 200, string $body = ''): void
    {
        $this->responseStatus = $status;
        $this->responseBody = $body;

        Http::fake(fn () => Http::response($this->responseBody, $this->responseStatus));
    }

    public static function current(): static
    {
        $test = TestSuite::getInstance()->test;

        if (! $test instanceof static) {
            throw new LogicException('No test of this suite is running.');
        }

        return $test;
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AlfakitWebhooksServiceProvider::class,
            WebhooksPanelProvider::class,
            WebhooksTenantPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', WebhooksUser::class);
        $app['config']->set('queue.default', 'sync');
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }
}
