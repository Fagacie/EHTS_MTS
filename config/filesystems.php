<?php

$etmsStorageRoot = rtrim((string) env('ETMS_STORAGE_ROOT', storage_path('app/private/etms')), '/\\');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks (Company Server Private Storage — Stage 10)
    |--------------------------------------------------------------------------
    |
    | All protected ETMS assets (module learning materials, module videos,
    | session recordings, and generated certificates) live in dedicated private
    | directories outside public web root (`public/`). `serve => false` blocks
    | direct unauthenticated URL serving; files are only delivered after
    | authenticated Laravel Policy / Gate checks and valid signed routes.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => public_path('storage'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'materials' => [
            'driver' => 'local',
            'root' => $etmsStorageRoot.'/materials',
            'visibility' => 'private',
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'session-materials' => [
            'driver' => 'local',
            'root' => $etmsStorageRoot.'/session-materials',
            'visibility' => 'private',
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'videos' => [
            'driver' => 'local',
            'root' => $etmsStorageRoot.'/videos',
            'visibility' => 'private',
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'recordings' => [
            'driver' => 'local',
            'root' => $etmsStorageRoot.'/recordings',
            'visibility' => 'private',
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'certificates' => [
            'driver' => 'local',
            'root' => $etmsStorageRoot.'/certificates',
            'visibility' => 'private',
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
