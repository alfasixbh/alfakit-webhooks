<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'webhook endpoint',
        'plural_label' => 'webhook endpoints',
        'navigation_label' => 'Webhooks',
    ],

    'sections' => [
        'endpoint' => 'Endpoint',
        'endpoint_description' => 'Where events are sent. Each request is a signed JSON POST.',
        'events' => 'Events',
        'events_description' => 'Choose which events this endpoint receives.',
        'headers' => 'Custom headers',
        'headers_description' => 'Sent with every request, for example an authorization token your receiver expects. Stored encrypted.',
        'secret' => 'Signing secret',
        'secret_description' => 'Requests are signed with HMAC-SHA256 using this secret. Rotate it from the actions menu.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Must use https and resolve to a public address.',
        'description' => 'Description',
        'description_placeholder' => 'What this endpoint is for',
        'is_enabled' => 'Enabled',
        'all_events' => 'All events',
        'all_events_help' => 'Receive every event, including events added later.',
        'selected_events' => 'Selected events',
        'selected_events_help' => 'Receive only the events you pick.',
        'events' => 'Events',
        'events_count' => '{1} :count event|[2,*] :count events',
        'headers' => 'Headers',
        'header_name' => 'Name',
        'header_value' => 'Value',
        'add_header' => 'Add header',
        'status' => 'Status',
        'consecutive_failures' => 'Failures in a row',
        'last_delivery_at' => 'Last delivery',
        'created_at' => 'Created',
        'never' => 'Never',
    ],

    'endpoint_states' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'auto_disabled' => 'Disabled after failures',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'succeeded' => 'Succeeded',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
    ],

    'model_events' => [
        'created' => ':model created',
        'updated' => ':model updated',
        'deleted' => ':model deleted',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Copy your signing secret now',
            'description' => 'This is the only time the secret is shown. Store it with your receiver to verify signatures.',
        ],
        'copied' => 'Secret copied',
    ],

    'actions' => [
        'test' => [
            'label' => 'Send test event',
            'modal' => [
                'heading' => 'Send a test event',
                'description' => 'A signed "ping" event is sent to :url right away, and you will see its response.',
                'submit' => 'Send',
            ],
            'notification' => [
                'success' => 'The endpoint answered :status in :duration ms',
                'failed_status' => 'The endpoint answered :status in :duration ms',
                'failed' => 'The test event could not be delivered',
            ],
        ],
        'rotate' => [
            'label' => 'Rotate secret',
            'modal' => [
                'heading' => 'Rotate the signing secret',
                'description' => 'A new secret is generated and shown once. Update your receiver before the old one stops signing.',
                'submit' => 'Rotate',
            ],
            'keep_previous' => 'Keep signing with the old secret for',
            'grace' => [
                'none' => 'Don\'t keep it',
                'hours' => '{1} :count hour|[2,*] :count hours',
            ],
            'notification' => 'New secret generated',
        ],
        'enable' => [
            'label' => 'Enable',
            'notification' => 'Endpoint enabled',
        ],
        'disable' => [
            'label' => 'Disable',
            'modal' => [
                'heading' => 'Disable this endpoint?',
                'description' => 'No events are sent to it until you enable it again. Queued retries are skipped.',
            ],
            'notification' => 'Endpoint disabled',
        ],
        'replay' => [
            'label' => 'Replay',
            'bulk_label' => 'Replay selected',
            'modal' => [
                'heading' => 'Replay this delivery?',
                'description' => 'The event is sent again with the same event id, so receivers can recognise duplicates.',
            ],
            'notification' => 'Delivery queued',
            'bulk_notification' => '{0} No deliveries could be replayed|{1} :count delivery queued|[2,*] :count deliveries queued',
        ],
    ],

    'deliveries' => [
        'title' => 'Deliveries',
        'label' => 'delivery',
        'plural_label' => 'deliveries',
        'test' => 'Test',
        'replay' => 'Replay',
        'ping' => 'Ping (test)',
        'request' => 'Request',
        'response' => 'Response',
        'fields' => [
            'event' => 'Event',
            'status' => 'Status',
            'response_status' => 'Response',
            'attempt' => 'Attempt',
            'duration' => 'Duration',
            'error' => 'Error',
            'event_id' => 'Event id',
            'created_at' => 'Date',
            'sent_at' => 'Sent at',
            'next_retry_at' => 'Retried at',
            'is_test' => 'Test events',
            'headers' => 'Headers',
            'body' => 'Body',
        ],
        'empty' => [
            'heading' => 'No deliveries yet',
            'description' => 'Deliveries appear here when an event is sent. Send a test event to try the endpoint.',
        ],
    ],

    'empty' => [
        'heading' => 'No webhook endpoints',
        'description' => 'Add an endpoint to send events to another application.',
    ],

    'url' => [
        'invalid' => 'Enter a valid URL.',
        'too_long' => 'The URL may not be longer than 2048 characters.',
        'https_required' => 'The URL must use https.',
        'credentials' => 'The URL may not contain a user name or password.',
        'blocked_host' => 'This host may not receive webhooks.',
        'private_address' => 'The URL resolves to a private, loopback or reserved address.',
        'unresolvable' => 'The host name could not be resolved.',
    ],

    'headers' => [
        'invalid' => 'The headers are invalid.',
        'too_many' => 'Add at most :max headers.',
        'reserved' => 'The header ":name" is invalid or reserved.',
        'invalid_value' => 'The value of the header ":name" is invalid.',
    ],

    'errors' => [
        'missing' => 'The endpoint or the event no longer exists.',
        'endpoint_disabled' => 'The endpoint is disabled.',
        'request_failed' => 'The request failed.',
        'redirect' => 'The endpoint answered with a redirect (:status); redirects are not followed.',
        'status' => 'The endpoint answered with status :status.',
        'timeout' => 'The request timed out.',
        'connection' => 'Could not connect to the endpoint.',
        'auto_disabled' => 'Disabled automatically after :count failed deliveries in a row.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'A webhook endpoint was disabled',
            'body' => ':url failed :count times in a row and no longer receives events. Fix it, then enable it again.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhooks',
        'endpoint_disabled' => 'Webhook endpoint disabled',
    ],
];
