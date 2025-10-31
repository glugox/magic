<?php

return [
    'attachments_disk' => env('ATTACHMENTS_DISK', 'public'),

    'max_file_size' => env('ATTACHMENTS_MAX_FILE_SIZE', 10 * 1024),

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ],

    'use_signed_urls' => env('ATTACHMENTS_USE_SIGNED_URLS', false),

    'signed_url_expiration_minutes' => env('ATTACHMENTS_SIGNED_URL_EXPIRATION', 60),

    'default_image_url' => env('ATTACHMENTS_DEFAULT_IMAGE_URL', '/images/placeholders/default.png'),

    'default_order_index' => 0,

    'route_middleware' => ['api'],

    'controller' => 'App\\Http\\Controllers\\AttachmentController',
];
