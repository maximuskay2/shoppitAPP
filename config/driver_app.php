<?php

return [
    'force_update' => env('DRIVER_APP_FORCE_UPDATE', false),
    'min_version' => env('DRIVER_APP_MIN_VERSION'),
    'latest_version' => env('DRIVER_APP_LATEST_VERSION'),
    'update_url' => env('DRIVER_APP_UPDATE_URL'),
    'message' => env('DRIVER_APP_UPDATE_MESSAGE'),
];
