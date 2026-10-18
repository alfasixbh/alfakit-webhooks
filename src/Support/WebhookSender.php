<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Support;

use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Events\WebhookDeliveryFailed;
use AlfaSix\AlfakitWebhooks\Events\WebhookDeliverySucceeded;
use AlfaSix\AlfakitWebhooks\Events\WebhookEndpointDisabled;
use AlfaSix\AlfakitWebhooks\Exceptions\UnsafeWebhookUrl;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Models\WebhookEvent;
use AlfaSix\AlfakitWebhooks\Notifications\EndpointDisabledMail;
use AlfaSix\AlfakitWebhooks\WebhookManager;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Sends one delivery attempt: checks the URL against the SSRF guard (resolving DNS and
 * pinning the connection to the checked address), signs the body, posts it without
 * following redirects, logs the redacted request and response, then updates the
 * endpoint's failure count (disabling it past the threshold) and queues the next attempt.
 */
class WebhookSender
{
    /** Headers a custom header may not replace. */
    public const RESERVED_HEADERS = ['host', 'content-type', 'content-length', 'transfer-encoding', 'connection', 'user-agent', 'expect', 'te', 'upgrade', 'cookie', 'proxy-authorization'];

    public function __construct(protected WebhookManager $manager) {}

    public function send(WebhookDelivery $delivery): WebhookDelivery
    {
        $endpoint = $delivery->endpoint;
        $event = $delivery->event;

        if ($endpoint === null || $event === null) {
            $delivery->forceFill(['status' => DeliveryStatus::Skipped, 'error' => __('alfakit-webhooks::webhooks.errors.missing')])->save();

            return $delivery;
        }

        if (! $delivery->is_test && ! $endpoint->is_enabled) {
            $delivery->forceFill(['status' => DeliveryStatus::Skipped, 'error' => __('alfakit-webhooks::webhooks.errors.endpoint_disabled')])->save();

            return $delivery;
        }

        $plugin = WebhooksPlugin::get();
        $redactor = $plugin->makeRedactor();
        $body = $event->toJsonBody();
        $timestamp = now()->getTimestamp();
        $headers = $this->headers($plugin, $endpoint, $event, $delivery, $timestamp, $body);

        $delivery->forceFill([
            'url' => $endpoint->url,
            'sent_at' => now(),
            'request_headers' => $redactor->redactHeaders($headers),
            'request_body' => $redactor->redactBody($body),
        ]);

        $retryable = true;
        $response = null;
        $error = null;
        $startedAt = hrtime(true);

        try {
            $target = $plugin->makeUrlGuard()->check($endpoint->url);
            $response = $this->post($plugin, $endpoint->url, $target, $headers, $body);
        } catch (UnsafeWebhookUrl $exception) {
            $error = $exception->getMessage();
            // A name that doesn't resolve may be a temporary DNS problem; a blocked URL isn't.
            $retryable = $exception->reason === 'dns';
        } catch (ConnectionException $exception) {
            $error = $this->connectionError($exception);
        } catch (Throwable $exception) {
            report($exception);
            $error = __('alfakit-webhooks::webhooks.errors.request_failed');
        }

        $duration = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $succeeded = $response !== null && $response->successful();

        if ($response !== null && ! $succeeded) {
            $error = $response->redirect()
                ? __('alfakit-webhooks::webhooks.errors.redirect', ['status' => $response->status()])
                : __('alfakit-webhooks::webhooks.errors.status', ['status' => $response->status()]);
        }

        $delivery->forceFill([
            'status' => $succeeded ? DeliveryStatus::Succeeded : DeliveryStatus::Failed,
            'response_status' => $response?->status(),
            'duration_ms' => $duration,
            'response_headers' => $response === null ? null : $redactor->redactHeaders($response->headers()),
            'response_body' => $response === null ? null : $redactor->redactBody($response->body()),
            'error' => $error,
        ])->save();

        if ($delivery->is_test) {
            return $delivery;
        }

        $succeeded ? $this->recordSuccess($endpoint, $delivery) : $this->recordFailure($plugin, $endpoint, $delivery, $retryable);

        return $delivery;
    }

    /**
     * The signed request headers: custom headers first, then the reserved ones.
     *
     * @return array<string, string>
     */
    public function headers(WebhooksPlugin $plugin, WebhookEndpoint $endpoint, WebhookEvent $event, WebhookDelivery $delivery, int $timestamp, string $body): array
    {
        $prefix = $plugin->getHeaderPrefix();
        $headers = [];

        foreach ($endpoint->headers ?? [] as $name => $value) {
            if (! self::isAllowedCustomHeader((string) $name, $prefix)) {
                continue;
            }

            $headers[(string) $name] = str_replace(["\r", "\n", "\0"], '', (string) $value);
        }

        return [
            ...$headers,
            'Content-Type' => 'application/json',
            'User-Agent' => $plugin->getUserAgent(),
            $prefix.'-Id' => $event->id,
            $prefix.'-Event' => $event->name,
            $prefix.'-Timestamp' => (string) $timestamp,
            $prefix.'-Signature' => Signature::header($endpoint->getSigningSecrets(), $timestamp, $body),
            $prefix.'-Attempt' => (string) $delivery->attempt,
        ];
    }

    public static function isAllowedCustomHeader(string $name, string $prefix): bool
    {
        $lower = strtolower(trim($name));

        return $lower !== ''
            && preg_match("/^[A-Za-z0-9!#$%&'*+.^_`|~-]+$/", $name) === 1
            && ! in_array($lower, self::RESERVED_HEADERS, true)
            && ! str_starts_with($lower, strtolower($prefix).'-');
    }

    /**
     * @param  array{scheme: string, host: string, port: int, addresses: array<int, string>}  $target
     * @param  array<string, string>  $headers
     */
    protected function post(WebhooksPlugin $plugin, string $url, array $target, array $headers, string $body): Response
    {
        $request = Http::withHeaders($headers)
            ->timeout($plugin->getTimeout())
            ->connectTimeout($plugin->getConnectTimeout())
            ->withoutRedirecting()
            ->withBody($body, 'application/json');

        // Pin the connection to the address that was just checked (no DNS rebinding).
        $address = $target['addresses'][0] ?? null;

        if ($address !== null && filter_var($target['host'], FILTER_VALIDATE_IP) === false && defined('CURLOPT_RESOLVE')) {
            $pinned = str_contains($address, ':') ? '['.$address.']' : $address;
            $request->withOptions(['curl' => [CURLOPT_RESOLVE => [$target['host'].':'.$target['port'].':'.$pinned]]]);
        }

        return $request->post($url);
    }

    protected function connectionError(ConnectionException $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'cURL error 28') || stripos($message, 'timed out') !== false) {
            return (string) __('alfakit-webhooks::webhooks.errors.timeout');
        }

        return (string) __('alfakit-webhooks::webhooks.errors.connection');
    }

    protected function recordSuccess(WebhookEndpoint $endpoint, WebhookDelivery $delivery): void
    {
        $endpoint->forceFill([
            'consecutive_failures' => 0,
            'last_delivery_at' => now(),
            'last_success_at' => now(),
        ])->save();

        event(new WebhookDeliverySucceeded($delivery));
    }

    protected function recordFailure(WebhooksPlugin $plugin, WebhookEndpoint $endpoint, WebhookDelivery $delivery, bool $retryable): void
    {
        WebhookEndpoint::query()->whereKey($endpoint->getKey())->increment('consecutive_failures', 1, [
            'last_delivery_at' => now(),
            'last_failure_at' => now(),
        ]);

        $endpoint->refresh();

        $threshold = $plugin->getDisableAfterConsecutiveFailures();

        if ($threshold !== null && $endpoint->is_enabled && $endpoint->consecutive_failures >= $threshold) {
            $this->disable($plugin, $endpoint);
        }

        $willRetry = $retryable && $endpoint->is_enabled && $delivery->attempt < $plugin->getMaxAttempts();

        if ($willRetry) {
            $delay = $plugin->getBackoffDelay($delivery->attempt);
            $delivery->forceFill(['next_retry_at' => now()->addSeconds($delay)])->save();

            $event = $delivery->event;

            if ($event !== null) {
                $next = $this->manager->newDelivery($endpoint, $event, attempt: $delivery->attempt + 1);
                $next->forceFill(['replay_of_id' => $delivery->replay_of_id])->save();
                $this->manager->queue($next, $delay);
            }
        }

        event(new WebhookDeliveryFailed($delivery, $willRetry));
    }

    protected function disable(WebhooksPlugin $plugin, WebhookEndpoint $endpoint): void
    {
        $endpoint->disable((string) __('alfakit-webhooks::webhooks.errors.auto_disabled', ['count' => $endpoint->consecutive_failures]));

        $recipients = $plugin->getDisableNotificationRecipients($endpoint);

        if ($recipients !== []) {
            Notification::make()
                ->danger()
                ->icon('webhook')
                ->title(__('alfakit-webhooks::webhooks.notifications.disabled.title'))
                ->body(__('alfakit-webhooks::webhooks.notifications.disabled.body', ['url' => $endpoint->url, 'count' => $endpoint->consecutive_failures]))
                ->sendToDatabase($recipients);

            if ($plugin->shouldMailOnDisable()) {
                NotificationFacade::send(
                    array_values(array_filter($recipients, static fn (object $recipient): bool => method_exists($recipient, 'routeNotificationFor'))),
                    new EndpointDisabledMail($endpoint->url, (int) $endpoint->consecutive_failures),
                );
            }
        }

        event(new WebhookEndpointDisabled($endpoint));
    }
}
