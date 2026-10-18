<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'endpoint de webhook',
        'plural_label' => 'endpoints de webhook',
        'navigation_label' => 'Webhooks',
    ],

    'sections' => [
        'endpoint' => 'Endpoint',
        'endpoint_description' => 'A dónde se envían los eventos. Cada solicitud es un POST JSON firmado.',
        'events' => 'Eventos',
        'events_description' => 'Elige qué eventos recibe este endpoint.',
        'headers' => 'Cabeceras personalizadas',
        'headers_description' => 'Se envían con cada solicitud, por ejemplo un token de autorización que espera tu receptor. Se guardan cifradas.',
        'secret' => 'Secreto de firma',
        'secret_description' => 'Las solicitudes se firman con HMAC-SHA256 usando este secreto. Rótalo desde el menú de acciones.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Debe usar https y resolver a una dirección pública.',
        'description' => 'Descripción',
        'description_placeholder' => 'Para qué sirve este endpoint',
        'is_enabled' => 'Activado',
        'all_events' => 'Todos los eventos',
        'all_events_help' => 'Recibir todos los eventos, incluidos los que se añadan después.',
        'selected_events' => 'Eventos seleccionados',
        'selected_events_help' => 'Recibir solo los eventos que elijas.',
        'events' => 'Eventos',
        'events_count' => '{1} :count evento|[2,*] :count eventos',
        'headers' => 'Cabeceras',
        'header_name' => 'Nombre',
        'header_value' => 'Valor',
        'add_header' => 'Añadir cabecera',
        'status' => 'Estado',
        'consecutive_failures' => 'Fallos seguidos',
        'last_delivery_at' => 'Última entrega',
        'created_at' => 'Creado',
        'never' => 'Nunca',
    ],

    'endpoint_states' => [
        'enabled' => 'Activado',
        'disabled' => 'Desactivado',
        'auto_disabled' => 'Desactivado por fallos',
    ],

    'statuses' => [
        'pending' => 'Pendiente',
        'succeeded' => 'Correcta',
        'failed' => 'Fallida',
        'skipped' => 'Omitida',
    ],

    'model_events' => [
        'created' => ':model creado',
        'updated' => ':model actualizado',
        'deleted' => ':model eliminado',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Copia ahora tu secreto de firma',
            'description' => 'Es la única vez que se muestra el secreto. Guárdalo en tu receptor para verificar las firmas.',
        ],
        'copied' => 'Secreto copiado',
    ],

    'actions' => [
        'test' => [
            'label' => 'Enviar evento de prueba',
            'modal' => [
                'heading' => 'Enviar un evento de prueba',
                'description' => 'Se envía ahora mismo un evento "ping" firmado a :url y verás su respuesta.',
                'submit' => 'Enviar',
            ],
            'notification' => [
                'success' => 'El endpoint respondió :status en :duration ms',
                'failed_status' => 'El endpoint respondió :status en :duration ms',
                'failed' => 'No se pudo entregar el evento de prueba',
            ],
        ],
        'rotate' => [
            'label' => 'Rotar secreto',
            'modal' => [
                'heading' => 'Rotar el secreto de firma',
                'description' => 'Se genera un secreto nuevo que se muestra una sola vez. Actualiza tu receptor antes de que el anterior deje de firmar.',
                'submit' => 'Rotar',
            ],
            'keep_previous' => 'Seguir firmando con el secreto anterior durante',
            'grace' => [
                'none' => 'No conservarlo',
                'hours' => '{1} :count hora|[2,*] :count horas',
            ],
            'notification' => 'Secreto nuevo generado',
        ],
        'enable' => [
            'label' => 'Activar',
            'notification' => 'Endpoint activado',
        ],
        'disable' => [
            'label' => 'Desactivar',
            'modal' => [
                'heading' => '¿Desactivar este endpoint?',
                'description' => 'No se le envían eventos hasta que lo vuelvas a activar. Los reintentos pendientes se omiten.',
            ],
            'notification' => 'Endpoint desactivado',
        ],
        'replay' => [
            'label' => 'Reenviar',
            'bulk_label' => 'Reenviar seleccionadas',
            'modal' => [
                'heading' => '¿Reenviar esta entrega?',
                'description' => 'El evento se envía de nuevo con el mismo id de evento, para que los receptores reconozcan los duplicados.',
            ],
            'notification' => 'Entrega en cola',
            'bulk_notification' => '{0} No se pudo reenviar ninguna entrega|{1} :count entrega en cola|[2,*] :count entregas en cola',
        ],
    ],

    'deliveries' => [
        'title' => 'Entregas',
        'label' => 'entrega',
        'plural_label' => 'entregas',
        'test' => 'Prueba',
        'replay' => 'Reenvío',
        'ping' => 'Ping (prueba)',
        'request' => 'Solicitud',
        'response' => 'Respuesta',
        'fields' => [
            'event' => 'Evento',
            'status' => 'Estado',
            'response_status' => 'Respuesta',
            'attempt' => 'Intento',
            'duration' => 'Duración',
            'error' => 'Error',
            'event_id' => 'Id del evento',
            'created_at' => 'Fecha',
            'sent_at' => 'Enviada el',
            'next_retry_at' => 'Reintentada el',
            'is_test' => 'Eventos de prueba',
            'headers' => 'Cabeceras',
            'body' => 'Cuerpo',
        ],
        'empty' => [
            'heading' => 'Aún no hay entregas',
            'description' => 'Las entregas aparecen aquí cuando se envía un evento. Envía un evento de prueba para probar el endpoint.',
        ],
    ],

    'empty' => [
        'heading' => 'No hay endpoints de webhook',
        'description' => 'Añade un endpoint para enviar eventos a otra aplicación.',
    ],

    'url' => [
        'invalid' => 'Introduce una URL válida.',
        'too_long' => 'La URL no puede tener más de 2048 caracteres.',
        'https_required' => 'La URL debe usar https.',
        'credentials' => 'La URL no puede contener un usuario ni una contraseña.',
        'blocked_host' => 'Este host no puede recibir webhooks.',
        'private_address' => 'La URL resuelve a una dirección privada, de loopback o reservada.',
        'unresolvable' => 'No se pudo resolver el nombre del host.',
    ],

    'headers' => [
        'invalid' => 'Las cabeceras no son válidas.',
        'too_many' => 'Añade como máximo :max cabeceras.',
        'reserved' => 'La cabecera ":name" no es válida o está reservada.',
        'invalid_value' => 'El valor de la cabecera ":name" no es válido.',
    ],

    'errors' => [
        'missing' => 'El endpoint o el evento ya no existe.',
        'endpoint_disabled' => 'El endpoint está desactivado.',
        'request_failed' => 'La solicitud falló.',
        'redirect' => 'El endpoint respondió con una redirección (:status); las redirecciones no se siguen.',
        'status' => 'El endpoint respondió con el estado :status.',
        'timeout' => 'Se agotó el tiempo de espera de la solicitud.',
        'connection' => 'No se pudo conectar con el endpoint.',
        'auto_disabled' => 'Desactivado automáticamente tras :count entregas fallidas seguidas.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Se desactivó un endpoint de webhook',
            'body' => ':url falló :count veces seguidas y ya no recibe eventos. Corrígelo y vuelve a activarlo.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhooks',
        'endpoint_disabled' => 'Endpoint de webhook desactivado',
    ],
];
