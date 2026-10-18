<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'webhook-endpoint',
        'plural_label' => 'webhook-endpoints',
        'navigation_label' => 'Webhooks',
    ],

    'sections' => [
        'endpoint' => 'Endpoint',
        'endpoint_description' => 'Waar gebeurtenissen naartoe worden gestuurd. Elk verzoek is een ondertekende JSON-POST.',
        'events' => 'Gebeurtenissen',
        'events_description' => 'Kies welke gebeurtenissen dit endpoint ontvangt.',
        'headers' => 'Aangepaste headers',
        'headers_description' => 'Worden met elk verzoek meegestuurd, bijvoorbeeld een autorisatietoken dat je ontvanger verwacht. Versleuteld opgeslagen.',
        'secret' => 'Ondertekeningsgeheim',
        'secret_description' => 'Verzoeken worden met HMAC-SHA256 en dit geheim ondertekend. Vernieuw het via het actiemenu.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Moet https gebruiken en naar een openbaar adres verwijzen.',
        'description' => 'Omschrijving',
        'description_placeholder' => 'Waar dit endpoint voor is',
        'is_enabled' => 'Ingeschakeld',
        'all_events' => 'Alle gebeurtenissen',
        'all_events_help' => 'Elke gebeurtenis ontvangen, ook later toegevoegde.',
        'selected_events' => 'Gekozen gebeurtenissen',
        'selected_events_help' => 'Alleen de gekozen gebeurtenissen ontvangen.',
        'events' => 'Gebeurtenissen',
        'events_count' => '{1} :count gebeurtenis|[2,*] :count gebeurtenissen',
        'headers' => 'Headers',
        'header_name' => 'Naam',
        'header_value' => 'Waarde',
        'add_header' => 'Header toevoegen',
        'status' => 'Status',
        'consecutive_failures' => 'Fouten op rij',
        'last_delivery_at' => 'Laatste aflevering',
        'created_at' => 'Aangemaakt',
        'never' => 'Nooit',
    ],

    'endpoint_states' => [
        'enabled' => 'Ingeschakeld',
        'disabled' => 'Uitgeschakeld',
        'auto_disabled' => 'Uitgeschakeld na fouten',
    ],

    'statuses' => [
        'pending' => 'In afwachting',
        'succeeded' => 'Geslaagd',
        'failed' => 'Mislukt',
        'skipped' => 'Overgeslagen',
    ],

    'model_events' => [
        'created' => ':model aangemaakt',
        'updated' => ':model bijgewerkt',
        'deleted' => ':model verwijderd',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Kopieer nu je ondertekeningsgeheim',
            'description' => 'Het geheim wordt alleen deze keer getoond. Bewaar het bij je ontvanger om handtekeningen te controleren.',
        ],
        'copied' => 'Geheim gekopieerd',
    ],

    'actions' => [
        'test' => [
            'label' => 'Testgebeurtenis versturen',
            'modal' => [
                'heading' => 'Een testgebeurtenis versturen',
                'description' => 'Er wordt meteen een ondertekende "ping"-gebeurtenis naar :url gestuurd en je ziet het antwoord.',
                'submit' => 'Versturen',
            ],
            'notification' => [
                'success' => 'Het endpoint antwoordde :status in :duration ms',
                'failed_status' => 'Het endpoint antwoordde :status in :duration ms',
                'failed' => 'De testgebeurtenis kon niet worden afgeleverd',
            ],
        ],
        'rotate' => [
            'label' => 'Geheim vernieuwen',
            'modal' => [
                'heading' => 'Het ondertekeningsgeheim vernieuwen',
                'description' => 'Er wordt een nieuw geheim gemaakt dat één keer wordt getoond. Werk je ontvanger bij voordat het oude niet meer ondertekent.',
                'submit' => 'Vernieuwen',
            ],
            'keep_previous' => 'Blijf ondertekenen met het oude geheim gedurende',
            'grace' => [
                'none' => 'Niet bewaren',
                'hours' => '{1} :count uur|[2,*] :count uur',
            ],
            'notification' => 'Nieuw geheim gemaakt',
        ],
        'enable' => [
            'label' => 'Inschakelen',
            'notification' => 'Endpoint ingeschakeld',
        ],
        'disable' => [
            'label' => 'Uitschakelen',
            'modal' => [
                'heading' => 'Dit endpoint uitschakelen?',
                'description' => 'Er worden geen gebeurtenissen naartoe gestuurd tot je het weer inschakelt. Geplande nieuwe pogingen worden overgeslagen.',
            ],
            'notification' => 'Endpoint uitgeschakeld',
        ],
        'replay' => [
            'label' => 'Opnieuw versturen',
            'bulk_label' => 'Selectie opnieuw versturen',
            'modal' => [
                'heading' => 'Deze aflevering opnieuw versturen?',
                'description' => 'De gebeurtenis wordt opnieuw verstuurd met hetzelfde gebeurtenis-id, zodat ontvangers dubbele herkennen.',
            ],
            'notification' => 'Aflevering in de wachtrij',
            'bulk_notification' => '{0} Geen enkele aflevering kon opnieuw worden verstuurd|{1} :count aflevering in de wachtrij|[2,*] :count afleveringen in de wachtrij',
        ],
    ],

    'deliveries' => [
        'title' => 'Afleveringen',
        'label' => 'aflevering',
        'plural_label' => 'afleveringen',
        'test' => 'Test',
        'replay' => 'Opnieuw',
        'ping' => 'Ping (test)',
        'request' => 'Verzoek',
        'response' => 'Antwoord',
        'fields' => [
            'event' => 'Gebeurtenis',
            'status' => 'Status',
            'response_status' => 'Antwoord',
            'attempt' => 'Poging',
            'duration' => 'Duur',
            'error' => 'Fout',
            'event_id' => 'Gebeurtenis-id',
            'created_at' => 'Datum',
            'sent_at' => 'Verstuurd op',
            'next_retry_at' => 'Opnieuw geprobeerd op',
            'is_test' => 'Testgebeurtenissen',
            'headers' => 'Headers',
            'body' => 'Inhoud',
        ],
        'empty' => [
            'heading' => 'Nog geen afleveringen',
            'description' => 'Afleveringen verschijnen hier zodra een gebeurtenis wordt verstuurd. Verstuur een testgebeurtenis om het endpoint te proberen.',
        ],
    ],

    'empty' => [
        'heading' => 'Geen webhook-endpoints',
        'description' => 'Voeg een endpoint toe om gebeurtenissen naar een andere applicatie te sturen.',
    ],

    'url' => [
        'invalid' => 'Voer een geldige URL in.',
        'too_long' => 'De URL mag niet langer zijn dan 2048 tekens.',
        'https_required' => 'De URL moet https gebruiken.',
        'credentials' => 'De URL mag geen gebruikersnaam of wachtwoord bevatten.',
        'blocked_host' => 'Deze host mag geen webhooks ontvangen.',
        'private_address' => 'De URL verwijst naar een privé-, loopback- of gereserveerd adres.',
        'unresolvable' => 'De hostnaam kon niet worden opgezocht.',
    ],

    'headers' => [
        'invalid' => 'De headers zijn ongeldig.',
        'too_many' => 'Voeg maximaal :max headers toe.',
        'reserved' => 'De header ":name" is ongeldig of gereserveerd.',
        'invalid_value' => 'De waarde van de header ":name" is ongeldig.',
    ],

    'errors' => [
        'missing' => 'Het endpoint of de gebeurtenis bestaat niet meer.',
        'endpoint_disabled' => 'Het endpoint is uitgeschakeld.',
        'request_failed' => 'Het verzoek is mislukt.',
        'redirect' => 'Het endpoint antwoordde met een doorverwijzing (:status); doorverwijzingen worden niet gevolgd.',
        'status' => 'Het endpoint antwoordde met status :status.',
        'timeout' => 'Het verzoek duurde te lang.',
        'connection' => 'Kon geen verbinding maken met het endpoint.',
        'auto_disabled' => 'Automatisch uitgeschakeld na :count mislukte afleveringen op rij.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Een webhook-endpoint is uitgeschakeld',
            'body' => ':url is :count keer op rij mislukt en ontvangt geen gebeurtenissen meer. Los het op en schakel het weer in.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhooks',
        'endpoint_disabled' => 'Webhook-endpoint uitgeschakeld',
    ],
];
