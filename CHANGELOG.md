# Changelog

All notable changes to `alfasix/alfakit-webhooks` are documented in this file.

## Unreleased

- Initial release: `WebhooksPlugin` with an event registry (`events()`, `modelEvents()`), the `Webhooks` facade (`dispatch()`, `sendTest()`, `replay()`, `fake()`), a `WebhookEndpointResource` (URL, events, encrypted custom headers and signing secret shown once, enable / disable, tenant-owned endpoints) with a deliveries log relation manager (status, response, duration, attempt, redacted request / response, replay and bulk replay), `SendTestWebhookAction` and `RotateWebhookSecretAction` (grace period with dual signatures), queued deliveries signed with HMAC-SHA256 (`t=…,v1=…`), exponential backoff retries, auto-disable after consecutive failures with a database notification, SSRF protection (https, private / loopback / link-local / metadata blocking, DNS re-check and connection pinning, no redirects), `WebhookDeliverySucceeded` / `WebhookDeliveryFailed` / `WebhookEndpointDisabled` events and the `alfakit-webhooks:prune` command.
