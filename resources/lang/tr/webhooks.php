<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'webhook uç noktası',
        'plural_label' => 'webhook uç noktaları',
        'navigation_label' => 'Webhook\'lar',
    ],

    'sections' => [
        'endpoint' => 'Uç nokta',
        'endpoint_description' => 'Olayların gönderildiği yer. Her istek imzalı bir JSON POST isteğidir.',
        'events' => 'Olaylar',
        'events_description' => 'Bu uç noktanın hangi olayları alacağını seçin.',
        'headers' => 'Özel başlıklar',
        'headers_description' => 'Her istekle gönderilir, örneğin alıcınızın beklediği bir yetkilendirme belirteci. Şifreli saklanır.',
        'secret' => 'İmza anahtarı',
        'secret_description' => 'İstekler bu anahtarla HMAC-SHA256 kullanılarak imzalanır. İşlemler menüsünden yenileyin.',
    ],

    'fields' => [
        'url' => 'URL',
        'url_help' => 'https kullanmalı ve herkese açık bir adrese çözümlenmelidir.',
        'description' => 'Açıklama',
        'description_placeholder' => 'Bu uç nokta ne için',
        'is_enabled' => 'Etkin',
        'all_events' => 'Tüm olaylar',
        'all_events_help' => 'Sonradan eklenenler dahil her olayı al.',
        'selected_events' => 'Seçilen olaylar',
        'selected_events_help' => 'Yalnızca seçtiğiniz olayları al.',
        'events' => 'Olaylar',
        'events_count' => ':count olay',
        'headers' => 'Başlıklar',
        'header_name' => 'Ad',
        'header_value' => 'Değer',
        'add_header' => 'Başlık ekle',
        'status' => 'Durum',
        'consecutive_failures' => 'Art arda hata',
        'last_delivery_at' => 'Son teslimat',
        'created_at' => 'Oluşturulma',
        'never' => 'Hiç',
    ],

    'endpoint_states' => [
        'enabled' => 'Etkin',
        'disabled' => 'Devre dışı',
        'auto_disabled' => 'Hatalar nedeniyle devre dışı',
    ],

    'statuses' => [
        'pending' => 'Bekliyor',
        'succeeded' => 'Başarılı',
        'failed' => 'Başarısız',
        'skipped' => 'Atlandı',
    ],

    'model_events' => [
        'created' => ':model oluşturuldu',
        'updated' => ':model güncellendi',
        'deleted' => ':model silindi',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'İmza anahtarınızı şimdi kopyalayın',
            'description' => 'Anahtar yalnızca bu kez gösterilir. İmzaları doğrulamak için alıcınızda saklayın.',
        ],
        'copied' => 'Anahtar kopyalandı',
    ],

    'actions' => [
        'test' => [
            'label' => 'Test olayı gönder',
            'modal' => [
                'heading' => 'Bir test olayı gönder',
                'description' => 'İmzalı bir "ping" olayı hemen :url adresine gönderilir ve yanıtını görürsünüz.',
                'submit' => 'Gönder',
            ],
            'notification' => [
                'success' => 'Uç nokta :duration ms içinde :status yanıtını verdi',
                'failed_status' => 'Uç nokta :duration ms içinde :status yanıtını verdi',
                'failed' => 'Test olayı teslim edilemedi',
            ],
        ],
        'rotate' => [
            'label' => 'Anahtarı yenile',
            'modal' => [
                'heading' => 'İmza anahtarını yenile',
                'description' => 'Yeni bir anahtar oluşturulur ve bir kez gösterilir. Eski anahtar imzalamayı bırakmadan önce alıcınızı güncelleyin.',
                'submit' => 'Yenile',
            ],
            'keep_previous' => 'Eski anahtarla imzalamaya devam etme süresi',
            'grace' => [
                'none' => 'Saklama',
                'hours' => ':count saat',
            ],
            'notification' => 'Yeni anahtar oluşturuldu',
        ],
        'enable' => [
            'label' => 'Etkinleştir',
            'notification' => 'Uç nokta etkinleştirildi',
        ],
        'disable' => [
            'label' => 'Devre dışı bırak',
            'modal' => [
                'heading' => 'Bu uç nokta devre dışı bırakılsın mı?',
                'description' => 'Yeniden etkinleştirene kadar olay gönderilmez. Sıradaki yeniden denemeler atlanır.',
            ],
            'notification' => 'Uç nokta devre dışı bırakıldı',
        ],
        'replay' => [
            'label' => 'Yeniden gönder',
            'bulk_label' => 'Seçilenleri yeniden gönder',
            'modal' => [
                'heading' => 'Bu teslimat yeniden gönderilsin mi?',
                'description' => 'Olay aynı olay kimliğiyle yeniden gönderilir; böylece alıcılar tekrarları tanıyabilir.',
            ],
            'notification' => 'Teslimat sıraya alındı',
            'bulk_notification' => '{0} Hiçbir teslimat yeniden gönderilemedi|[1,*] :count teslimat sıraya alındı',
        ],
    ],

    'deliveries' => [
        'title' => 'Teslimatlar',
        'label' => 'teslimat',
        'plural_label' => 'teslimatlar',
        'test' => 'Test',
        'replay' => 'Yeniden gönderim',
        'ping' => 'Ping (test)',
        'request' => 'İstek',
        'response' => 'Yanıt',
        'fields' => [
            'event' => 'Olay',
            'status' => 'Durum',
            'response_status' => 'Yanıt',
            'attempt' => 'Deneme',
            'duration' => 'Süre',
            'error' => 'Hata',
            'event_id' => 'Olay kimliği',
            'created_at' => 'Tarih',
            'sent_at' => 'Gönderilme',
            'next_retry_at' => 'Yeniden denenme',
            'is_test' => 'Test olayları',
            'headers' => 'Başlıklar',
            'body' => 'Gövde',
        ],
        'empty' => [
            'heading' => 'Henüz teslimat yok',
            'description' => 'Bir olay gönderildiğinde teslimatlar burada görünür. Uç noktayı denemek için bir test olayı gönderin.',
        ],
    ],

    'empty' => [
        'heading' => 'Webhook uç noktası yok',
        'description' => 'Olayları başka bir uygulamaya göndermek için bir uç nokta ekleyin.',
    ],

    'url' => [
        'invalid' => 'Geçerli bir URL girin.',
        'too_long' => 'URL 2048 karakterden uzun olamaz.',
        'https_required' => 'URL https kullanmalıdır.',
        'credentials' => 'URL kullanıcı adı veya parola içeremez.',
        'blocked_host' => 'Bu sunucu webhook alamaz.',
        'private_address' => 'URL özel, geri döngü veya ayrılmış bir adrese çözümleniyor.',
        'unresolvable' => 'Sunucu adı çözümlenemedi.',
    ],

    'headers' => [
        'invalid' => 'Başlıklar geçersiz.',
        'too_many' => 'En fazla :max başlık ekleyin.',
        'reserved' => '":name" başlığı geçersiz veya ayrılmış.',
        'invalid_value' => '":name" başlığının değeri geçersiz.',
    ],

    'errors' => [
        'missing' => 'Uç nokta veya olay artık yok.',
        'endpoint_disabled' => 'Uç nokta devre dışı.',
        'request_failed' => 'İstek başarısız oldu.',
        'redirect' => 'Uç nokta bir yönlendirmeyle yanıt verdi (:status); yönlendirmeler izlenmez.',
        'status' => 'Uç nokta :status durumuyla yanıt verdi.',
        'timeout' => 'İstek zaman aşımına uğradı.',
        'connection' => 'Uç noktaya bağlanılamadı.',
        'auto_disabled' => 'Art arda :count başarısız teslimattan sonra otomatik olarak devre dışı bırakıldı.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Bir webhook uç noktası devre dışı bırakıldı',
            'body' => ':url art arda :count kez başarısız oldu ve artık olay almıyor. Sorunu giderin, ardından yeniden etkinleştirin.',
        ],
    ],

    'mail_templates' => [
        'group' => 'Webhook’lar',
        'endpoint_disabled' => 'Webhook uç noktası devre dışı',
    ],
];
