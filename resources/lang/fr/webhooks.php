<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'point de terminaison webhook',
        'plural_label' => 'points de terminaison webhook',
        'navigation_label' => 'Webhooks',
    ],

    'sections' => [
        'endpoint' => 'Point de terminaison',
        'endpoint_description' => 'Où les événements sont envoyés. Chaque requête est un POST JSON signé.',
        'events' => 'Événements',
        'events_description' => 'Choisissez les événements que reçoit ce point de terminaison.',
        'headers' => 'En-têtes personnalisés',
        'headers_description' => 'Envoyés avec chaque requête, par exemple un jeton d’autorisation attendu par votre récepteur. Stockés chiffrés.',
        'secret' => 'Secret de signature',
        'secret_description' => 'Les requêtes sont signées en HMAC-SHA256 avec ce secret. Renouvelez-le depuis le menu des actions.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Doit utiliser https et pointer vers une adresse publique.',
        'description' => 'Description',
        'description_placeholder' => 'À quoi sert ce point de terminaison',
        'is_enabled' => 'Activé',
        'all_events' => 'Tous les événements',
        'all_events_help' => 'Recevoir tous les événements, y compris ceux ajoutés plus tard.',
        'selected_events' => 'Événements choisis',
        'selected_events_help' => 'Recevoir uniquement les événements choisis.',
        'events' => 'Événements',
        'events_count' => '{1} :count événement|[2,*] :count événements',
        'headers' => 'En-têtes',
        'header_name' => 'Nom',
        'header_value' => 'Valeur',
        'add_header' => 'Ajouter un en-tête',
        'status' => 'Statut',
        'consecutive_failures' => 'Échecs consécutifs',
        'last_delivery_at' => 'Dernier envoi',
        'created_at' => 'Créé le',
        'never' => 'Jamais',
    ],

    'endpoint_states' => [
        'enabled' => 'Activé',
        'disabled' => 'Désactivé',
        'auto_disabled' => 'Désactivé après des échecs',
    ],

    'statuses' => [
        'pending' => 'En attente',
        'succeeded' => 'Réussi',
        'failed' => 'Échoué',
        'skipped' => 'Ignoré',
    ],

    'model_events' => [
        'created' => ':model créé',
        'updated' => ':model mis à jour',
        'deleted' => ':model supprimé',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Copiez votre secret de signature maintenant',
            'description' => 'Le secret n’est affiché qu’une seule fois. Enregistrez-le dans votre récepteur pour vérifier les signatures.',
        ],
        'copied' => 'Secret copié',
    ],

    'actions' => [
        'test' => [
            'label' => 'Envoyer un événement de test',
            'modal' => [
                'heading' => 'Envoyer un événement de test',
                'description' => 'Un événement « ping » signé est envoyé immédiatement à :url, et vous verrez sa réponse.',
                'submit' => 'Envoyer',
            ],
            'notification' => [
                'success' => 'Le point de terminaison a répondu :status en :duration ms',
                'failed_status' => 'Le point de terminaison a répondu :status en :duration ms',
                'failed' => 'L’événement de test n’a pas pu être livré',
            ],
        ],
        'rotate' => [
            'label' => 'Renouveler le secret',
            'modal' => [
                'heading' => 'Renouveler le secret de signature',
                'description' => 'Un nouveau secret est généré et affiché une seule fois. Mettez votre récepteur à jour avant que l’ancien cesse de signer.',
                'submit' => 'Renouveler',
            ],
            'keep_previous' => 'Continuer à signer avec l’ancien secret pendant',
            'grace' => [
                'none' => 'Ne pas le garder',
                'hours' => '{1} :count heure|[2,*] :count heures',
            ],
            'notification' => 'Nouveau secret généré',
        ],
        'enable' => [
            'label' => 'Activer',
            'notification' => 'Point de terminaison activé',
        ],
        'disable' => [
            'label' => 'Désactiver',
            'modal' => [
                'heading' => 'Désactiver ce point de terminaison ?',
                'description' => 'Aucun événement ne lui est envoyé tant que vous ne le réactivez pas. Les nouvelles tentatives prévues sont ignorées.',
            ],
            'notification' => 'Point de terminaison désactivé',
        ],
        'replay' => [
            'label' => 'Renvoyer',
            'bulk_label' => 'Renvoyer la sélection',
            'modal' => [
                'heading' => 'Renvoyer cet envoi ?',
                'description' => 'L’événement est renvoyé avec le même identifiant, pour que les récepteurs reconnaissent les doublons.',
            ],
            'notification' => 'Envoi mis en file d’attente',
            'bulk_notification' => '{0} Aucun envoi n’a pu être renvoyé|{1} :count envoi mis en file d’attente|[2,*] :count envois mis en file d’attente',
        ],
    ],

    'deliveries' => [
        'title' => 'Envois',
        'label' => 'envoi',
        'plural_label' => 'envois',
        'test' => 'Test',
        'replay' => 'Renvoi',
        'ping' => 'Ping (test)',
        'request' => 'Requête',
        'response' => 'Réponse',
        'fields' => [
            'event' => 'Événement',
            'status' => 'Statut',
            'response_status' => 'Réponse',
            'attempt' => 'Tentative',
            'duration' => 'Durée',
            'error' => 'Erreur',
            'event_id' => 'Identifiant de l’événement',
            'created_at' => 'Date',
            'sent_at' => 'Envoyé le',
            'next_retry_at' => 'Nouvelle tentative le',
            'is_test' => 'Événements de test',
            'headers' => 'En-têtes',
            'body' => 'Corps',
        ],
        'empty' => [
            'heading' => 'Aucun envoi pour l’instant',
            'description' => 'Les envois apparaissent ici quand un événement est envoyé. Envoyez un événement de test pour essayer le point de terminaison.',
        ],
    ],

    'empty' => [
        'heading' => 'Aucun point de terminaison webhook',
        'description' => 'Ajoutez un point de terminaison pour envoyer des événements à une autre application.',
    ],

    'url' => [
        'invalid' => 'Saisissez une URL valide.',
        'too_long' => 'L’URL ne peut pas dépasser 2048 caractères.',
        'https_required' => 'L’URL doit utiliser https.',
        'credentials' => 'L’URL ne peut pas contenir de nom d’utilisateur ni de mot de passe.',
        'blocked_host' => 'Cet hôte ne peut pas recevoir de webhooks.',
        'private_address' => 'L’URL pointe vers une adresse privée, de bouclage ou réservée.',
        'unresolvable' => 'Le nom d’hôte n’a pas pu être résolu.',
    ],

    'headers' => [
        'invalid' => 'Les en-têtes ne sont pas valides.',
        'too_many' => 'Ajoutez au plus :max en-têtes.',
        'reserved' => 'L’en-tête « :name » n’est pas valide ou est réservé.',
        'invalid_value' => 'La valeur de l’en-tête « :name » n’est pas valide.',
    ],

    'errors' => [
        'missing' => 'Le point de terminaison ou l’événement n’existe plus.',
        'endpoint_disabled' => 'Le point de terminaison est désactivé.',
        'request_failed' => 'La requête a échoué.',
        'redirect' => 'Le point de terminaison a répondu par une redirection (:status) ; les redirections ne sont pas suivies.',
        'status' => 'Le point de terminaison a répondu avec le statut :status.',
        'timeout' => 'La requête a expiré.',
        'connection' => 'Impossible de se connecter au point de terminaison.',
        'auto_disabled' => 'Désactivé automatiquement après :count envois échoués consécutifs.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Un point de terminaison webhook a été désactivé',
            'body' => ':url a échoué :count fois de suite et ne reçoit plus d’événements. Corrigez le problème puis réactivez-le.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhooks',
        'endpoint_disabled' => 'Point de terminaison webhook désactivé',
    ],
];
