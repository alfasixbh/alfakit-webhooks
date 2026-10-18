<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Webhook 端点',
        'plural_label' => 'Webhook 端点',
        'navigation_label' => 'Webhook',
    ],

    'sections' => [
        'endpoint' => '端点',
        'endpoint_description' => '事件的发送地址。每个请求都是一个签名的 JSON POST。',
        'events' => '事件',
        'events_description' => '选择此端点接收哪些事件。',
        'headers' => '自定义请求头',
        'headers_description' => '随每个请求发送，例如接收方需要的授权令牌。加密存储。',
        'secret' => '签名密钥',
        'secret_description' => '请求使用此密钥以 HMAC-SHA256 签名。可在操作菜单中轮换。',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => '必须使用 https，并解析到公网地址。',
        'description' => '描述',
        'description_placeholder' => '此端点的用途',
        'is_enabled' => '已启用',
        'all_events' => '所有事件',
        'all_events_help' => '接收所有事件，包括以后添加的事件。',
        'selected_events' => '选定的事件',
        'selected_events_help' => '只接收你选择的事件。',
        'events' => '事件',
        'events_count' => ':count 个事件',
        'headers' => '请求头',
        'header_name' => '名称',
        'header_value' => '值',
        'add_header' => '添加请求头',
        'status' => '状态',
        'consecutive_failures' => '连续失败次数',
        'last_delivery_at' => '最近投递',
        'created_at' => '创建时间',
        'never' => '从未',
    ],

    'endpoint_states' => [
        'enabled' => '已启用',
        'disabled' => '已停用',
        'auto_disabled' => '因失败而停用',
    ],

    'statuses' => [
        'pending' => '等待中',
        'succeeded' => '成功',
        'failed' => '失败',
        'skipped' => '已跳过',
    ],

    'model_events' => [
        'created' => ':model 已创建',
        'updated' => ':model 已更新',
        'deleted' => ':model 已删除',
    ],

    'secret' => [
        'reveal' => [
            'heading' => '请立即复制签名密钥',
            'description' => '密钥只显示这一次。请保存到接收方，用于验证签名。',
        ],
        'copied' => '密钥已复制',
    ],

    'actions' => [
        'test' => [
            'label' => '发送测试事件',
            'modal' => [
                'heading' => '发送测试事件',
                'description' => '立即向 :url 发送一个签名的 "ping" 事件，并显示其响应。',
                'submit' => '发送',
            ],
            'notification' => [
                'success' => '端点在 :duration 毫秒内返回 :status',
                'failed_status' => '端点在 :duration 毫秒内返回 :status',
                'failed' => '测试事件投递失败',
            ],
        ],
        'rotate' => [
            'label' => '轮换密钥',
            'modal' => [
                'heading' => '轮换签名密钥',
                'description' => '将生成一个新密钥并只显示一次。请在旧密钥停止签名前更新接收方。',
                'submit' => '轮换',
            ],
            'keep_previous' => '继续使用旧密钥签名的时长',
            'grace' => [
                'none' => '不保留',
                'hours' => ':count 小时',
            ],
            'notification' => '已生成新密钥',
        ],
        'enable' => [
            'label' => '启用',
            'notification' => '端点已启用',
        ],
        'disable' => [
            'label' => '停用',
            'modal' => [
                'heading' => '停用此端点？',
                'description' => '在重新启用前不会向其发送事件。排队中的重试将被跳过。',
            ],
            'notification' => '端点已停用',
        ],
        'replay' => [
            'label' => '重新发送',
            'bulk_label' => '重新发送所选',
            'modal' => [
                'heading' => '重新发送此投递？',
                'description' => '事件将以相同的事件 ID 再次发送，接收方可据此识别重复。',
            ],
            'notification' => '投递已加入队列',
            'bulk_notification' => '{0} 没有可重新发送的投递|[1,*] 已将 :count 个投递加入队列',
        ],
    ],

    'deliveries' => [
        'title' => '投递记录',
        'label' => '投递',
        'plural_label' => '投递',
        'test' => '测试',
        'replay' => '重新发送',
        'ping' => 'Ping（测试）',
        'request' => '请求',
        'response' => '响应',
        'fields' => [
            'event' => '事件',
            'status' => '状态',
            'response_status' => '响应',
            'attempt' => '尝试',
            'duration' => '耗时',
            'error' => '错误',
            'event_id' => '事件 ID',
            'created_at' => '日期',
            'sent_at' => '发送时间',
            'next_retry_at' => '重试时间',
            'is_test' => '测试事件',
            'headers' => '请求头',
            'body' => '正文',
        ],
        'empty' => [
            'heading' => '暂无投递',
            'description' => '发送事件后，投递记录会显示在这里。发送一个测试事件来试试端点。',
        ],
    ],

    'empty' => [
        'heading' => '没有 Webhook 端点',
        'description' => '添加端点，将事件发送到其他应用。',
    ],

    'url' => [
        'invalid' => '请输入有效的 URL。',
        'too_long' => 'URL 不能超过 2048 个字符。',
        'https_required' => 'URL 必须使用 https。',
        'credentials' => 'URL 不能包含用户名或密码。',
        'blocked_host' => '此主机不能接收 Webhook。',
        'private_address' => 'URL 解析到私有、回环或保留地址。',
        'unresolvable' => '无法解析主机名。',
    ],

    'headers' => [
        'invalid' => '请求头无效。',
        'too_many' => '最多添加 :max 个请求头。',
        'reserved' => '请求头 ":name" 无效或为保留名称。',
        'invalid_value' => '请求头 ":name" 的值无效。',
    ],

    'errors' => [
        'missing' => '端点或事件已不存在。',
        'endpoint_disabled' => '端点已停用。',
        'request_failed' => '请求失败。',
        'redirect' => '端点返回了重定向（:status）；不会跟随重定向。',
        'status' => '端点返回状态 :status。',
        'timeout' => '请求超时。',
        'connection' => '无法连接到端点。',
        'auto_disabled' => '连续 :count 次投递失败后已自动停用。',
    ],

    'notifications' => [
        'disabled' => [
            'title' => '一个 Webhook 端点已被停用',
            'body' => ':url 连续失败 :count 次，已不再接收事件。请修复问题后重新启用。',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhook',
        'endpoint_disabled' => 'Webhook 端点已停用',
    ],
];
