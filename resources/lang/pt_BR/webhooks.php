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
        'endpoint_description' => 'Para onde os eventos são enviados. Cada requisição é um POST JSON assinado.',
        'events' => 'Eventos',
        'events_description' => 'Escolha quais eventos este endpoint recebe.',
        'headers' => 'Cabeçalhos personalizados',
        'headers_description' => 'Enviados em toda requisição, por exemplo um token de autorização que seu receptor espera. Armazenados criptografados.',
        'secret' => 'Segredo de assinatura',
        'secret_description' => 'As requisições são assinadas com HMAC-SHA256 usando este segredo. Gire-o pelo menu de ações.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'Deve usar https e resolver para um endereço público.',
        'description' => 'Descrição',
        'description_placeholder' => 'Para que serve este endpoint',
        'is_enabled' => 'Ativado',
        'all_events' => 'Todos os eventos',
        'all_events_help' => 'Receber todos os eventos, inclusive os adicionados depois.',
        'selected_events' => 'Eventos selecionados',
        'selected_events_help' => 'Receber apenas os eventos escolhidos.',
        'events' => 'Eventos',
        'events_count' => '{1} :count evento|[2,*] :count eventos',
        'headers' => 'Cabeçalhos',
        'header_name' => 'Nome',
        'header_value' => 'Valor',
        'add_header' => 'Adicionar cabeçalho',
        'status' => 'Status',
        'consecutive_failures' => 'Falhas seguidas',
        'last_delivery_at' => 'Última entrega',
        'created_at' => 'Criado',
        'never' => 'Nunca',
    ],

    'endpoint_states' => [
        'enabled' => 'Ativado',
        'disabled' => 'Desativado',
        'auto_disabled' => 'Desativado após falhas',
    ],

    'statuses' => [
        'pending' => 'Pendente',
        'succeeded' => 'Sucesso',
        'failed' => 'Falhou',
        'skipped' => 'Ignorada',
    ],

    'model_events' => [
        'created' => ':model criado',
        'updated' => ':model atualizado',
        'deleted' => ':model excluído',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'Copie seu segredo de assinatura agora',
            'description' => 'Esta é a única vez que o segredo é exibido. Guarde-o no seu receptor para verificar as assinaturas.',
        ],
        'copied' => 'Segredo copiado',
    ],

    'actions' => [
        'test' => [
            'label' => 'Enviar evento de teste',
            'modal' => [
                'heading' => 'Enviar um evento de teste',
                'description' => 'Um evento "ping" assinado é enviado agora para :url e você verá a resposta.',
                'submit' => 'Enviar',
            ],
            'notification' => [
                'success' => 'O endpoint respondeu :status em :duration ms',
                'failed_status' => 'O endpoint respondeu :status em :duration ms',
                'failed' => 'Não foi possível entregar o evento de teste',
            ],
        ],
        'rotate' => [
            'label' => 'Girar segredo',
            'modal' => [
                'heading' => 'Girar o segredo de assinatura',
                'description' => 'Um novo segredo é gerado e exibido uma única vez. Atualize seu receptor antes que o antigo pare de assinar.',
                'submit' => 'Girar',
            ],
            'keep_previous' => 'Continuar assinando com o segredo antigo por',
            'grace' => [
                'none' => 'Não manter',
                'hours' => '{1} :count hora|[2,*] :count horas',
            ],
            'notification' => 'Novo segredo gerado',
        ],
        'enable' => [
            'label' => 'Ativar',
            'notification' => 'Endpoint ativado',
        ],
        'disable' => [
            'label' => 'Desativar',
            'modal' => [
                'heading' => 'Desativar este endpoint?',
                'description' => 'Nenhum evento é enviado até que você o ative novamente. As novas tentativas na fila são ignoradas.',
            ],
            'notification' => 'Endpoint desativado',
        ],
        'replay' => [
            'label' => 'Reenviar',
            'bulk_label' => 'Reenviar selecionadas',
            'modal' => [
                'heading' => 'Reenviar esta entrega?',
                'description' => 'O evento é enviado de novo com o mesmo id de evento, para que os receptores reconheçam duplicados.',
            ],
            'notification' => 'Entrega na fila',
            'bulk_notification' => '{0} Nenhuma entrega pôde ser reenviada|{1} :count entrega na fila|[2,*] :count entregas na fila',
        ],
    ],

    'deliveries' => [
        'title' => 'Entregas',
        'label' => 'entrega',
        'plural_label' => 'entregas',
        'test' => 'Teste',
        'replay' => 'Reenvio',
        'ping' => 'Ping (teste)',
        'request' => 'Requisição',
        'response' => 'Resposta',
        'fields' => [
            'event' => 'Evento',
            'status' => 'Status',
            'response_status' => 'Resposta',
            'attempt' => 'Tentativa',
            'duration' => 'Duração',
            'error' => 'Erro',
            'event_id' => 'Id do evento',
            'created_at' => 'Data',
            'sent_at' => 'Enviada em',
            'next_retry_at' => 'Nova tentativa em',
            'is_test' => 'Eventos de teste',
            'headers' => 'Cabeçalhos',
            'body' => 'Corpo',
        ],
        'empty' => [
            'heading' => 'Nenhuma entrega ainda',
            'description' => 'As entregas aparecem aqui quando um evento é enviado. Envie um evento de teste para experimentar o endpoint.',
        ],
    ],

    'empty' => [
        'heading' => 'Nenhum endpoint de webhook',
        'description' => 'Adicione um endpoint para enviar eventos a outra aplicação.',
    ],

    'url' => [
        'invalid' => 'Informe uma URL válida.',
        'too_long' => 'A URL não pode ter mais de 2048 caracteres.',
        'https_required' => 'A URL deve usar https.',
        'credentials' => 'A URL não pode conter usuário nem senha.',
        'blocked_host' => 'Este host não pode receber webhooks.',
        'private_address' => 'A URL resolve para um endereço privado, de loopback ou reservado.',
        'unresolvable' => 'Não foi possível resolver o nome do host.',
    ],

    'headers' => [
        'invalid' => 'Os cabeçalhos são inválidos.',
        'too_many' => 'Adicione no máximo :max cabeçalhos.',
        'reserved' => 'O cabeçalho ":name" é inválido ou reservado.',
        'invalid_value' => 'O valor do cabeçalho ":name" é inválido.',
    ],

    'errors' => [
        'missing' => 'O endpoint ou o evento não existe mais.',
        'endpoint_disabled' => 'O endpoint está desativado.',
        'request_failed' => 'A requisição falhou.',
        'redirect' => 'O endpoint respondeu com um redirecionamento (:status); redirecionamentos não são seguidos.',
        'status' => 'O endpoint respondeu com o status :status.',
        'timeout' => 'A requisição expirou.',
        'connection' => 'Não foi possível conectar ao endpoint.',
        'auto_disabled' => 'Desativado automaticamente após :count entregas com falha seguidas.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Um endpoint de webhook foi desativado',
            'body' => ':url falhou :count vezes seguidas e não recebe mais eventos. Corrija o problema e ative-o novamente.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhooks',
        'endpoint_disabled' => 'Endpoint de webhook desativado',
    ],
];
