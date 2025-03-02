<?php

return [
    'access_key' => env('UNSPLASH_ACCESS_KEY'),
    'store_in_database' => env('UNSPLASH_STORE_IN_DATABASE', false),
    'disk' => env('UNSPLASH_STORAGE_DISK', 'local'),
    'app_name' => env('UNSPLASH_APP_NAME', 'your_app_name'),
];
