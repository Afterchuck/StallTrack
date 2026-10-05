<?php

return [
    'registration_enabled' => env('REGISTRATION_ENABLED', env('APP_ENV', 'production') !== 'production'),
    'demo_admin_password' => env('DEMO_ADMIN_PASSWORD'),
];
