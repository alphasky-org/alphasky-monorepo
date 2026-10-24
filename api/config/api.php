<?php

use Alphasky\ACL\Models\User;

return [
    // Existing clients remain compatible unless a site explicitly requires an audience.
    'account_type_required' => env('API_REQUIRE_ACCOUNT_TYPE', false),
    'provider' => [
        'model' => User::class,
        'guard' => 'web',
        'password_broker' => 'users',
        'verify_email' => false,
    ],
];
