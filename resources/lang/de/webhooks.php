<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Webhook-Endpunkt',
        'plural_label' => 'Webhook-Endpunkte',
        'navigation_label' => 'Webhooks',
    ],

    'sections' => [
        'endpoint' => 'Endpunkt',
        'endpoint_description' => 'Wohin Ereignisse gesendet werden. Jede Anfrage ist ein signierter JSON-POST.',
        'events' => 'Ereignisse',
        'events_description' => 'Wählen Sie, welche Ereignisse dieser Endpunkt empfängt.',
        'headers' => 'Eigene Header',
        'headers_description' => 'Werden mit jeder Anfrage gesendet, z. B. ein Autorisierungstoken, das Ihr Empfänger erwartet. Verschlüsselt gespeichert.',
        'secret' => 'Signaturschlüssel',
        'secret_description' => 'Anfragen werden mit HMAC-SHA256 und diesem Schlüssel signiert. Rotieren Sie ihn über das Aktionsmenü.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Muss https verwenden und auf eine öffentliche Adresse auflösen.',
        'description' => 'Beschreibung',
        'description_placeholder' => 'Wofür dieser Endpunkt dient',
        'is_enabled' => 'Aktiviert',
        'all_events' => 'Alle Ereignisse',
        'all_events_help' => 'Jedes Ereignis empfangen, auch später hinzugefügte.',
        'selected_events' => 'Ausgewählte Ereignisse',
        'selected_events_help' => 'Nur die ausgewählten Ereignisse empfangen.',
        'events' => 'Ereignisse',
        'events_count' => '{1} :count Ereignis|[2,*] :count Ereignisse',
        'headers' => 'Header',
        'header_name' => 'Name',
        'header_value' => 'Wert',
        'add_header' => 'Header hinzufügen',
        'status' => 'Status',
        'consecutive_failures' => 'Fehler in Folge',
        'last_delivery_at' => 'Letzte Zustellung',
        'created_at' => 'Erstellt',
        'never' => 'Nie',
    ],

    'endpoint_states' => [
        'enabled' => 'Aktiviert',
        'disabled' => 'Deaktiviert',
        'auto_disabled' => 'Nach Fehlern deaktiviert',
    ],

    'statuses' => [
        'pending' => 'Ausstehend',
        'succeeded' => 'Erfolgreich',
        'failed' => 'Fehlgeschlagen',
        'skipped' => 'Übersprungen',
    ],

    'model_events' => [
        'created' => ':model erstellt',
        'updated' => ':model aktualisiert',
        'deleted' => ':model gelöscht',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Kopieren Sie jetzt Ihren Signaturschlüssel',
            'description' => 'Der Schlüssel wird nur dieses eine Mal angezeigt. Hinterlegen Sie ihn bei Ihrem Empfänger, um Signaturen zu prüfen.',
        ],
        'copied' => 'Schlüssel kopiert',
    ],

    'actions' => [
        'test' => [
            'label' => 'Testereignis senden',
            'modal' => [
                'heading' => 'Testereignis senden',
                'description' => 'Ein signiertes „ping“-Ereignis wird sofort an :url gesendet, und Sie sehen die Antwort.',
                'submit' => 'Senden',
            ],
            'notification' => [
                'success' => 'Der Endpunkt antwortete :status in :duration ms',
                'failed_status' => 'Der Endpunkt antwortete :status in :duration ms',
                'failed' => 'Das Testereignis konnte nicht zugestellt werden',
            ],
        ],
        'rotate' => [
            'label' => 'Schlüssel rotieren',
            'modal' => [
                'heading' => 'Signaturschlüssel rotieren',
                'description' => 'Ein neuer Schlüssel wird erzeugt und einmal angezeigt. Aktualisieren Sie Ihren Empfänger, bevor der alte nicht mehr signiert.',
                'submit' => 'Rotieren',
            ],
            'keep_previous' => 'Mit dem alten Schlüssel weiter signieren für',
            'grace' => [
                'none' => 'Nicht behalten',
                'hours' => '{1} :count Stunde|[2,*] :count Stunden',
            ],
            'notification' => 'Neuer Schlüssel erzeugt',
        ],
        'enable' => [
            'label' => 'Aktivieren',
            'notification' => 'Endpunkt aktiviert',
        ],
        'disable' => [
            'label' => 'Deaktivieren',
            'modal' => [
                'heading' => 'Diesen Endpunkt deaktivieren?',
                'description' => 'Es werden keine Ereignisse gesendet, bis Sie ihn wieder aktivieren. Geplante Wiederholungen werden übersprungen.',
            ],
            'notification' => 'Endpunkt deaktiviert',
        ],
        'replay' => [
            'label' => 'Erneut senden',
            'bulk_label' => 'Auswahl erneut senden',
            'modal' => [
                'heading' => 'Diese Zustellung erneut senden?',
                'description' => 'Das Ereignis wird mit derselben Ereignis-ID erneut gesendet, damit Empfänger Duplikate erkennen.',
            ],
            'notification' => 'Zustellung eingeplant',
            'bulk_notification' => '{0} Keine Zustellung konnte erneut gesendet werden|{1} :count Zustellung eingeplant|[2,*] :count Zustellungen eingeplant',
        ],
    ],

    'deliveries' => [
        'title' => 'Zustellungen',
        'label' => 'Zustellung',
        'plural_label' => 'Zustellungen',
        'test' => 'Test',
        'replay' => 'Erneut gesendet',
        'ping' => 'Ping (Test)',
        'request' => 'Anfrage',
        'response' => 'Antwort',
        'fields' => [
            'event' => 'Ereignis',
            'status' => 'Status',
            'response_status' => 'Antwort',
            'attempt' => 'Versuch',
            'duration' => 'Dauer',
            'error' => 'Fehler',
            'event_id' => 'Ereignis-ID',
            'created_at' => 'Datum',
            'sent_at' => 'Gesendet am',
            'next_retry_at' => 'Wiederholt am',
            'is_test' => 'Testereignisse',
            'headers' => 'Header',
            'body' => 'Inhalt',
        ],
        'empty' => [
            'heading' => 'Noch keine Zustellungen',
            'description' => 'Zustellungen erscheinen hier, sobald ein Ereignis gesendet wird. Senden Sie ein Testereignis, um den Endpunkt auszuprobieren.',
        ],
    ],

    'empty' => [
        'heading' => 'Keine Webhook-Endpunkte',
        'description' => 'Fügen Sie einen Endpunkt hinzu, um Ereignisse an eine andere Anwendung zu senden.',
    ],

    'url' => [
        'invalid' => 'Geben Sie eine gültige URL ein.',
        'too_long' => 'Die URL darf nicht länger als 2048 Zeichen sein.',
        'https_required' => 'Die URL muss https verwenden.',
        'credentials' => 'Die URL darf keinen Benutzernamen und kein Passwort enthalten.',
        'blocked_host' => 'Dieser Host darf keine Webhooks empfangen.',
        'private_address' => 'Die URL löst auf eine private, Loopback- oder reservierte Adresse auf.',
        'unresolvable' => 'Der Hostname konnte nicht aufgelöst werden.',
    ],

    'headers' => [
        'invalid' => 'Die Header sind ungültig.',
        'too_many' => 'Fügen Sie höchstens :max Header hinzu.',
        'reserved' => 'Der Header „:name“ ist ungültig oder reserviert.',
        'invalid_value' => 'Der Wert des Headers „:name“ ist ungültig.',
    ],

    'errors' => [
        'missing' => 'Der Endpunkt oder das Ereignis existiert nicht mehr.',
        'endpoint_disabled' => 'Der Endpunkt ist deaktiviert.',
        'request_failed' => 'Die Anfrage ist fehlgeschlagen.',
        'redirect' => 'Der Endpunkt antwortete mit einer Weiterleitung (:status); Weiterleitungen werden nicht verfolgt.',
        'status' => 'Der Endpunkt antwortete mit Status :status.',
        'timeout' => 'Zeitüberschreitung der Anfrage.',
        'connection' => 'Keine Verbindung zum Endpunkt möglich.',
        'auto_disabled' => 'Automatisch deaktiviert nach :count fehlgeschlagenen Zustellungen in Folge.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Ein Webhook-Endpunkt wurde deaktiviert',
            'body' => ':url ist :count-mal in Folge fehlgeschlagen und empfängt keine Ereignisse mehr. Beheben Sie das Problem und aktivieren Sie ihn wieder.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhooks',
        'endpoint_disabled' => 'Webhook-Endpunkt deaktiviert',
    ],
];
