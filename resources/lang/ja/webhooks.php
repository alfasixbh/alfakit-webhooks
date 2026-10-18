<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Webhook エンドポイント',
        'plural_label' => 'Webhook エンドポイント',
        'navigation_label' => 'Webhook',
    ],

    'sections' => [
        'endpoint' => 'エンドポイント',
        'endpoint_description' => 'イベントの送信先です。各リクエストは署名付きの JSON POST です。',
        'events' => 'イベント',
        'events_description' => 'このエンドポイントが受け取るイベントを選択します。',
        'headers' => 'カスタムヘッダー',
        'headers_description' => '受信側が必要とする認証トークンなど、すべてのリクエストに付けて送信します。暗号化して保存されます。',
        'secret' => '署名シークレット',
        'secret_description' => 'リクエストはこのシークレットを使って HMAC-SHA256 で署名されます。アクションメニューからローテーションできます。',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'https を使い、公開アドレスに解決される必要があります。',
        'description' => '説明',
        'description_placeholder' => 'このエンドポイントの用途',
        'is_enabled' => '有効',
        'all_events' => 'すべてのイベント',
        'all_events_help' => '後から追加されるものも含め、すべてのイベントを受け取ります。',
        'selected_events' => '選択したイベント',
        'selected_events_help' => '選択したイベントだけを受け取ります。',
        'events' => 'イベント',
        'events_count' => ':count 件のイベント',
        'headers' => 'ヘッダー',
        'header_name' => '名前',
        'header_value' => '値',
        'add_header' => 'ヘッダーを追加',
        'status' => 'ステータス',
        'consecutive_failures' => '連続失敗数',
        'last_delivery_at' => '最終配信',
        'created_at' => '作成日時',
        'never' => 'なし',
    ],

    'endpoint_states' => [
        'enabled' => '有効',
        'disabled' => '無効',
        'auto_disabled' => '失敗により無効',
    ],

    'statuses' => [
        'pending' => '保留中',
        'succeeded' => '成功',
        'failed' => '失敗',
        'skipped' => 'スキップ',
    ],

    'model_events' => [
        'created' => ':model の作成',
        'updated' => ':model の更新',
        'deleted' => ':model の削除',
    ],

    'secret' => [
        'reveal' => [
            'heading' => '署名シークレットを今すぐコピーしてください',
            'description' => 'シークレットが表示されるのはこの一度だけです。署名を検証するため受信側に保存してください。',
        ],
        'copied' => 'シークレットをコピーしました',
    ],

    'actions' => [
        'test' => [
            'label' => 'テストイベントを送信',
            'modal' => [
                'heading' => 'テストイベントを送信',
                'description' => '署名付きの「ping」イベントを :url にすぐ送信し、そのレスポンスを表示します。',
                'submit' => '送信',
            ],
            'notification' => [
                'success' => 'エンドポイントが :duration ms で :status を返しました',
                'failed_status' => 'エンドポイントが :duration ms で :status を返しました',
                'failed' => 'テストイベントを配信できませんでした',
            ],
        ],
        'rotate' => [
            'label' => 'シークレットをローテーション',
            'modal' => [
                'heading' => '署名シークレットのローテーション',
                'description' => '新しいシークレットを生成し、一度だけ表示します。古いシークレットでの署名が止まる前に受信側を更新してください。',
                'submit' => 'ローテーション',
            ],
            'keep_previous' => '古いシークレットでも署名を続ける期間',
            'grace' => [
                'none' => '保持しない',
                'hours' => ':count 時間',
            ],
            'notification' => '新しいシークレットを生成しました',
        ],
        'enable' => [
            'label' => '有効にする',
            'notification' => 'エンドポイントを有効にしました',
        ],
        'disable' => [
            'label' => '無効にする',
            'modal' => [
                'heading' => 'このエンドポイントを無効にしますか？',
                'description' => '再び有効にするまでイベントは送信されません。予定されている再試行はスキップされます。',
            ],
            'notification' => 'エンドポイントを無効にしました',
        ],
        'replay' => [
            'label' => '再送',
            'bulk_label' => '選択項目を再送',
            'modal' => [
                'heading' => 'この配信を再送しますか？',
                'description' => '受信側が重複を判別できるよう、同じイベント ID で再送します。',
            ],
            'notification' => '配信をキューに追加しました',
            'bulk_notification' => '{0} 再送できる配信はありませんでした|[1,*] :count 件の配信をキューに追加しました',
        ],
    ],

    'deliveries' => [
        'title' => '配信',
        'label' => '配信',
        'plural_label' => '配信',
        'test' => 'テスト',
        'replay' => '再送',
        'ping' => 'Ping（テスト）',
        'request' => 'リクエスト',
        'response' => 'レスポンス',
        'fields' => [
            'event' => 'イベント',
            'status' => 'ステータス',
            'response_status' => 'レスポンス',
            'attempt' => '試行',
            'duration' => '所要時間',
            'error' => 'エラー',
            'event_id' => 'イベント ID',
            'created_at' => '日時',
            'sent_at' => '送信日時',
            'next_retry_at' => '再試行日時',
            'is_test' => 'テストイベント',
            'headers' => 'ヘッダー',
            'body' => '本文',
        ],
        'empty' => [
            'heading' => 'まだ配信はありません',
            'description' => 'イベントが送信されるとここに配信が表示されます。テストイベントを送ってエンドポイントを試してください。',
        ],
    ],

    'empty' => [
        'heading' => 'Webhook エンドポイントはありません',
        'description' => 'エンドポイントを追加して、別のアプリケーションにイベントを送信しましょう。',
    ],

    'url' => [
        'invalid' => '有効な URL を入力してください。',
        'too_long' => 'URL は 2048 文字以内にしてください。',
        'https_required' => 'URL は https を使う必要があります。',
        'credentials' => 'URL にユーザー名やパスワードを含めることはできません。',
        'blocked_host' => 'このホストは Webhook を受け取れません。',
        'private_address' => 'URL がプライベート、ループバック、または予約済みのアドレスに解決されます。',
        'unresolvable' => 'ホスト名を解決できませんでした。',
    ],

    'headers' => [
        'invalid' => 'ヘッダーが正しくありません。',
        'too_many' => 'ヘッダーは最大 :max 個までです。',
        'reserved' => 'ヘッダー「:name」は無効か予約済みです。',
        'invalid_value' => 'ヘッダー「:name」の値が正しくありません。',
    ],

    'errors' => [
        'missing' => 'エンドポイントまたはイベントがもう存在しません。',
        'endpoint_disabled' => 'エンドポイントは無効です。',
        'request_failed' => 'リクエストに失敗しました。',
        'redirect' => 'エンドポイントがリダイレクト（:status）を返しました。リダイレクトには従いません。',
        'status' => 'エンドポイントがステータス :status を返しました。',
        'timeout' => 'リクエストがタイムアウトしました。',
        'connection' => 'エンドポイントに接続できませんでした。',
        'auto_disabled' => '配信に :count 回連続で失敗したため、自動的に無効になりました。',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Webhook エンドポイントが無効になりました',
            'body' => ':url は :count 回連続で失敗したため、イベントを受け取らなくなりました。問題を解決してから再び有効にしてください。',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhook',
        'endpoint_disabled' => 'Webhookエンドポイントが無効になりました',
    ],
];
