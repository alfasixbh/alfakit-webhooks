<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'endpoint webhook',
        'plural_label' => 'endpoint webhook',
        'navigation_label' => 'Webhook',
    ],

    'sections' => [
        'endpoint' => 'Endpoint',
        'endpoint_description' => 'Dove vengono inviati gli eventi. Ogni richiesta è un POST JSON firmato.',
        'events' => 'Eventi',
        'events_description' => 'Scegli quali eventi riceve questo endpoint.',
        'headers' => 'Intestazioni personalizzate',
        'headers_description' => 'Inviate con ogni richiesta, ad esempio un token di autorizzazione richiesto dal ricevitore. Salvate cifrate.',
        'secret' => 'Segreto di firma',
        'secret_description' => 'Le richieste sono firmate con HMAC-SHA256 usando questo segreto. Ruotalo dal menu delle azioni.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Deve usare https e risolversi in un indirizzo pubblico.',
        'description' => 'Descrizione',
        'description_placeholder' => 'A cosa serve questo endpoint',
        'is_enabled' => 'Attivo',
        'all_events' => 'Tutti gli eventi',
        'all_events_help' => 'Ricevi ogni evento, compresi quelli aggiunti in seguito.',
        'selected_events' => 'Eventi selezionati',
        'selected_events_help' => 'Ricevi solo gli eventi che scegli.',
        'events' => 'Eventi',
        'events_count' => '{1} :count evento|[2,*] :count eventi',
        'headers' => 'Intestazioni',
        'header_name' => 'Nome',
        'header_value' => 'Valore',
        'add_header' => 'Aggiungi intestazione',
        'status' => 'Stato',
        'consecutive_failures' => 'Errori consecutivi',
        'last_delivery_at' => 'Ultimo invio',
        'created_at' => 'Creato',
        'never' => 'Mai',
    ],

    'endpoint_states' => [
        'enabled' => 'Attivo',
        'disabled' => 'Disattivato',
        'auto_disabled' => 'Disattivato per errori',
    ],

    'statuses' => [
        'pending' => 'In attesa',
        'succeeded' => 'Riuscito',
        'failed' => 'Fallito',
        'skipped' => 'Saltato',
    ],

    'model_events' => [
        'created' => ':model creato',
        'updated' => ':model aggiornato',
        'deleted' => ':model eliminato',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Copia ora il segreto di firma',
            'description' => 'Il segreto viene mostrato solo questa volta. Salvalo nel ricevitore per verificare le firme.',
        ],
        'copied' => 'Segreto copiato',
    ],

    'actions' => [
        'test' => [
            'label' => 'Invia evento di prova',
            'modal' => [
                'heading' => 'Invia un evento di prova',
                'description' => 'Un evento "ping" firmato viene inviato subito a :url e vedrai la sua risposta.',
                'submit' => 'Invia',
            ],
            'notification' => [
                'success' => 'L’endpoint ha risposto :status in :duration ms',
                'failed_status' => 'L’endpoint ha risposto :status in :duration ms',
                'failed' => 'Impossibile consegnare l’evento di prova',
            ],
        ],
        'rotate' => [
            'label' => 'Ruota segreto',
            'modal' => [
                'heading' => 'Ruota il segreto di firma',
                'description' => 'Viene generato un nuovo segreto, mostrato una sola volta. Aggiorna il ricevitore prima che il vecchio smetta di firmare.',
                'submit' => 'Ruota',
            ],
            'keep_previous' => 'Continua a firmare con il vecchio segreto per',
            'grace' => [
                'none' => 'Non conservarlo',
                'hours' => '{1} :count ora|[2,*] :count ore',
            ],
            'notification' => 'Nuovo segreto generato',
        ],
        'enable' => [
            'label' => 'Attiva',
            'notification' => 'Endpoint attivato',
        ],
        'disable' => [
            'label' => 'Disattiva',
            'modal' => [
                'heading' => 'Disattivare questo endpoint?',
                'description' => 'Non gli vengono inviati eventi finché non lo riattivi. I nuovi tentativi in coda vengono saltati.',
            ],
            'notification' => 'Endpoint disattivato',
        ],
        'replay' => [
            'label' => 'Reinvia',
            'bulk_label' => 'Reinvia selezionati',
            'modal' => [
                'heading' => 'Reinviare questa consegna?',
                'description' => 'L’evento viene inviato di nuovo con lo stesso id evento, così i ricevitori riconoscono i duplicati.',
            ],
            'notification' => 'Consegna in coda',
            'bulk_notification' => '{0} Nessuna consegna può essere reinviata|{1} :count consegna in coda|[2,*] :count consegne in coda',
        ],
    ],

    'deliveries' => [
        'title' => 'Consegne',
        'label' => 'consegna',
        'plural_label' => 'consegne',
        'test' => 'Prova',
        'replay' => 'Reinvio',
        'ping' => 'Ping (prova)',
        'request' => 'Richiesta',
        'response' => 'Risposta',
        'fields' => [
            'event' => 'Evento',
            'status' => 'Stato',
            'response_status' => 'Risposta',
            'attempt' => 'Tentativo',
            'duration' => 'Durata',
            'error' => 'Errore',
            'event_id' => 'Id evento',
            'created_at' => 'Data',
            'sent_at' => 'Inviata il',
            'next_retry_at' => 'Ritentata il',
            'is_test' => 'Eventi di prova',
            'headers' => 'Intestazioni',
            'body' => 'Corpo',
        ],
        'empty' => [
            'heading' => 'Ancora nessuna consegna',
            'description' => 'Le consegne compaiono qui quando viene inviato un evento. Invia un evento di prova per provare l’endpoint.',
        ],
    ],

    'empty' => [
        'heading' => 'Nessun endpoint webhook',
        'description' => 'Aggiungi un endpoint per inviare eventi a un’altra applicazione.',
    ],

    'url' => [
        'invalid' => 'Inserisci un URL valido.',
        'too_long' => 'L’URL non può superare i 2048 caratteri.',
        'https_required' => 'L’URL deve usare https.',
        'credentials' => 'L’URL non può contenere nome utente o password.',
        'blocked_host' => 'Questo host non può ricevere webhook.',
        'private_address' => 'L’URL si risolve in un indirizzo privato, di loopback o riservato.',
        'unresolvable' => 'Impossibile risolvere il nome host.',
    ],

    'headers' => [
        'invalid' => 'Le intestazioni non sono valide.',
        'too_many' => 'Aggiungi al massimo :max intestazioni.',
        'reserved' => 'L’intestazione ":name" non è valida o è riservata.',
        'invalid_value' => 'Il valore dell’intestazione ":name" non è valido.',
    ],

    'errors' => [
        'missing' => 'L’endpoint o l’evento non esiste più.',
        'endpoint_disabled' => 'L’endpoint è disattivato.',
        'request_failed' => 'La richiesta non è riuscita.',
        'redirect' => 'L’endpoint ha risposto con un reindirizzamento (:status); i reindirizzamenti non vengono seguiti.',
        'status' => 'L’endpoint ha risposto con lo stato :status.',
        'timeout' => 'Tempo scaduto per la richiesta.',
        'connection' => 'Impossibile connettersi all’endpoint.',
        'auto_disabled' => 'Disattivato automaticamente dopo :count consegne fallite di seguito.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Un endpoint webhook è stato disattivato',
            'body' => ':url è fallito :count volte di seguito e non riceve più eventi. Correggilo, poi riattivalo.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhook',
        'endpoint_disabled' => 'Endpoint webhook disattivato',
    ],
];
