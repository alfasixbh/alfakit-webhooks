<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'نقطة استقبال webhook',
        'plural_label' => 'نقاط استقبال webhook',
        'navigation_label' => 'Webhooks',
    ],

    'sections' => [
        'endpoint' => 'نقطة الاستقبال',
        'endpoint_description' => 'المكان الذي تُرسل إليه الأحداث. كل طلب هو POST بصيغة JSON وموقّع.',
        'events' => 'الأحداث',
        'events_description' => 'اختر الأحداث التي تستقبلها نقطة الاستقبال هذه.',
        'headers' => 'ترويسات مخصصة',
        'headers_description' => 'تُرسل مع كل طلب، مثل رمز تفويض يتوقعه المستقبِل. تُخزَّن مشفّرة.',
        'secret' => 'سر التوقيع',
        'secret_description' => 'تُوقَّع الطلبات بـ HMAC-SHA256 باستخدام هذا السر. يمكنك تدويره من قائمة الإجراءات.',
    ],

    'fields' => [
        'url' => 'الرابط',
        'url_help' => 'يجب أن يستخدم https وأن يشير إلى عنوان عام.',
        'description' => 'الوصف',
        'description_placeholder' => 'الغرض من نقطة الاستقبال هذه',
        'is_enabled' => 'مفعّلة',
        'all_events' => 'كل الأحداث',
        'all_events_help' => 'استقبال كل الأحداث، بما فيها التي تُضاف لاحقًا.',
        'selected_events' => 'أحداث محددة',
        'selected_events_help' => 'استقبال الأحداث التي تختارها فقط.',
        'events' => 'الأحداث',
        'events_count' => '{1} حدث واحد (:count)|[2,*] :count أحداث',
        'headers' => 'الترويسات',
        'header_name' => 'الاسم',
        'header_value' => 'القيمة',
        'add_header' => 'إضافة ترويسة',
        'status' => 'الحالة',
        'consecutive_failures' => 'إخفاقات متتالية',
        'last_delivery_at' => 'آخر تسليم',
        'created_at' => 'تاريخ الإنشاء',
        'never' => 'أبدًا',
    ],

    'endpoint_states' => [
        'enabled' => 'مفعّلة',
        'disabled' => 'معطّلة',
        'auto_disabled' => 'معطّلة بسبب الإخفاقات',
    ],

    'statuses' => [
        'pending' => 'قيد الانتظار',
        'succeeded' => 'ناجح',
        'failed' => 'فاشل',
        'skipped' => 'متجاوَز',
    ],

    'model_events' => [
        'created' => 'إنشاء :model',
        'updated' => 'تحديث :model',
        'deleted' => 'حذف :model',
    ],

    'secret' => [
        'reveal' => [
            'heading' => 'انسخ سر التوقيع الآن',
            'description' => 'هذه هي المرة الوحيدة التي يُعرض فيها السر. احفظه لدى المستقبِل للتحقق من التواقيع.',
        ],
        'copied' => 'تم نسخ السر',
    ],

    'actions' => [
        'test' => [
            'label' => 'إرسال حدث تجريبي',
            'modal' => [
                'heading' => 'إرسال حدث تجريبي',
                'description' => 'يُرسل حدث "ping" موقّع فورًا إلى :url وسترى الاستجابة.',
                'submit' => 'إرسال',
            ],
            'notification' => [
                'success' => 'استجابت نقطة الاستقبال بـ :status خلال :duration مللي ثانية',
                'failed_status' => 'استجابت نقطة الاستقبال بـ :status خلال :duration مللي ثانية',
                'failed' => 'تعذّر تسليم الحدث التجريبي',
            ],
        ],
        'rotate' => [
            'label' => 'تدوير السر',
            'modal' => [
                'heading' => 'تدوير سر التوقيع',
                'description' => 'يُنشأ سر جديد ويُعرض مرة واحدة. حدّث المستقبِل قبل أن يتوقف السر القديم عن التوقيع.',
                'submit' => 'تدوير',
            ],
            'keep_previous' => 'مواصلة التوقيع بالسر القديم لمدة',
            'grace' => [
                'none' => 'عدم الاحتفاظ به',
                'hours' => '{1} ساعة واحدة (:count)|[2,*] :count ساعات',
            ],
            'notification' => 'تم إنشاء سر جديد',
        ],
        'enable' => [
            'label' => 'تفعيل',
            'notification' => 'تم تفعيل نقطة الاستقبال',
        ],
        'disable' => [
            'label' => 'تعطيل',
            'modal' => [
                'heading' => 'تعطيل نقطة الاستقبال هذه؟',
                'description' => 'لن تُرسل إليها أي أحداث حتى تفعّلها مجددًا. تُتجاوز المحاولات المجدولة.',
            ],
            'notification' => 'تم تعطيل نقطة الاستقبال',
        ],
        'replay' => [
            'label' => 'إعادة الإرسال',
            'bulk_label' => 'إعادة إرسال المحدد',
            'modal' => [
                'heading' => 'إعادة إرسال هذا التسليم؟',
                'description' => 'يُرسل الحدث مجددًا بمعرّف الحدث نفسه، ليتعرّف المستقبِلون على التكرارات.',
            ],
            'notification' => 'تمت جدولة التسليم',
            'bulk_notification' => '{0} تعذّرت إعادة إرسال أي تسليم|[1,*] تمت جدولة :count من التسليمات',
        ],
    ],

    'deliveries' => [
        'title' => 'التسليمات',
        'label' => 'تسليم',
        'plural_label' => 'التسليمات',
        'test' => 'تجريبي',
        'replay' => 'إعادة إرسال',
        'ping' => 'Ping (تجريبي)',
        'request' => 'الطلب',
        'response' => 'الاستجابة',
        'fields' => [
            'event' => 'الحدث',
            'status' => 'الحالة',
            'response_status' => 'الاستجابة',
            'attempt' => 'المحاولة',
            'duration' => 'المدة',
            'error' => 'الخطأ',
            'event_id' => 'معرّف الحدث',
            'created_at' => 'التاريخ',
            'sent_at' => 'وقت الإرسال',
            'next_retry_at' => 'وقت إعادة المحاولة',
            'is_test' => 'الأحداث التجريبية',
            'headers' => 'الترويسات',
            'body' => 'المحتوى',
        ],
        'empty' => [
            'heading' => 'لا توجد تسليمات بعد',
            'description' => 'تظهر التسليمات هنا عند إرسال حدث. أرسل حدثًا تجريبيًا لتجربة نقطة الاستقبال.',
        ],
    ],

    'empty' => [
        'heading' => 'لا توجد نقاط استقبال webhook',
        'description' => 'أضف نقطة استقبال لإرسال الأحداث إلى تطبيق آخر.',
    ],

    'url' => [
        'invalid' => 'أدخل رابطًا صالحًا.',
        'too_long' => 'لا يجوز أن يتجاوز الرابط 2048 حرفًا.',
        'https_required' => 'يجب أن يستخدم الرابط https.',
        'credentials' => 'لا يجوز أن يحتوي الرابط على اسم مستخدم أو كلمة مرور.',
        'blocked_host' => 'لا يُسمح لهذا المضيف باستقبال webhooks.',
        'private_address' => 'يشير الرابط إلى عنوان خاص أو محلي أو محجوز.',
        'unresolvable' => 'تعذّر تحليل اسم المضيف.',
    ],

    'headers' => [
        'invalid' => 'الترويسات غير صالحة.',
        'too_many' => 'أضف :max ترويسة على الأكثر.',
        'reserved' => 'الترويسة ":name" غير صالحة أو محجوزة.',
        'invalid_value' => 'قيمة الترويسة ":name" غير صالحة.',
    ],

    'errors' => [
        'missing' => 'لم تعد نقطة الاستقبال أو الحدث موجودة.',
        'endpoint_disabled' => 'نقطة الاستقبال معطّلة.',
        'request_failed' => 'فشل الطلب.',
        'redirect' => 'استجابت نقطة الاستقبال بإعادة توجيه (:status)؛ لا تُتبَع عمليات إعادة التوجيه.',
        'status' => 'استجابت نقطة الاستقبال بالحالة :status.',
        'timeout' => 'انتهت مهلة الطلب.',
        'connection' => 'تعذّر الاتصال بنقطة الاستقبال.',
        'auto_disabled' => 'عُطّلت تلقائيًا بعد :count تسليمات فاشلة متتالية.',
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'تم تعطيل نقطة استقبال webhook',
            'body' => 'فشلت :url عدد :count مرات متتالية ولم تعد تستقبل الأحداث. أصلح المشكلة ثم فعّلها مجددًا.',
        ],
    ],

    'mail_templates' => [
        'group' => 'الويب هوك',
        'endpoint_disabled' => 'تم تعطيل نقطة الويب هوك',
    ],
];
