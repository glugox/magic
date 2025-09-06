<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attachments Disk
    |--------------------------------------------------------------------------
    |
    | The default filesystem disk to store attachments (images, files, etc.).
    | You can change this to 's3', 'public', or any disk defined in
    | config/filesystems.php.
    |
    */
    'attachments_disk' => env('ATTACHMENTS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size (MB)
    |--------------------------------------------------------------------------
    |
    | Maximum allowed file size for attachments. End users uploading files
    | larger than this will receive a validation error.
    |
    */
    'max_file_size' => 10, // MB

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | List of allowed MIME types for attachments.
    |
    */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],

    /*
    |--------------------------------------------------------------------------
    | Use Signed URLs
    |--------------------------------------------------------------------------
    |
    | If enabled, URLs returned for attachments will be signed and expire
    | after a specified time. Useful for private storage.
    |
    */
    'use_signed_urls' => true,

    'signed_url_expiration_minutes' => 60,

    /*
    |--------------------------------------------------------------------------
    | Default Image Placeholder
    |--------------------------------------------------------------------------
    |
    | If a model has no images, you can return this default image URL.
    |
    */
    'default_image_url' => '/images/placeholders/default.png',

    /*
    |--------------------------------------------------------------------------
    | Order Index
    |--------------------------------------------------------------------------
    |
    | Default order index for attachments. You can override this per model.
    |
    */
    'default_order_index' => 0,
];
