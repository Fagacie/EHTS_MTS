<?php

return [
    'brand_name' => 'Ecohydrotech Solution',
    'short_name' => 'ETMS',
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Kuala_Lumpur'),
    'currency' => env('PAYMENT_CURRENCY', env('APP_CURRENCY', 'NGN')),
    'support_email' => env('MAIL_FROM_ADDRESS', 'no-reply@ecohydrotech.com.my'),

    /*
    |--------------------------------------------------------------------------
    | Company Server Storage, Self-Hosted Video & Backup Settings (Stage 10)
    |--------------------------------------------------------------------------
    */
    'storage_root' => rtrim((string) env('ETMS_STORAGE_ROOT', storage_path('app/private/etms')), '/\\'),
    'max_upload_kb' => (int) env('ETMS_MAX_UPLOAD_KB', 2097152), // 2 GB

    'video' => [
        'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
        'hls_segment_seconds' => (int) env('ETMS_HLS_SEGMENT_SECONDS', 6),
        'signing_key' => env('ETMS_MEDIA_SIGNING_KEY') ?: env('APP_KEY', 'etms-local-media-signing-key'),
        'token_ttl_seconds' => (int) env('ETMS_VIDEO_TOKEN_TTL_SECONDS', 3600),
    ],

    'nginx' => [
        'x_accel_enabled' => filter_var(env('ETMS_X_ACCEL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'x_accel_internal_prefix' => rtrim((string) env('ETMS_X_ACCEL_PREFIX', '/_etms_internal_storage'), '/'),
    ],

    'backup' => [
        'root' => rtrim((string) env('ETMS_BACKUP_ROOT', storage_path('backups')), '/\\'),
        'retention_days' => (int) env('ETMS_BACKUP_RETENTION_DAYS', 14),
    ],
];
