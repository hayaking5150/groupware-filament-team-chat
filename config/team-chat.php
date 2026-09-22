<?php

use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All database tables created by this plugin will use this prefix
    | to avoid collisions with the host application's tables.
    |
    */
    'table_prefix' => 'tc_',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of the user model.
    |
    */
    'user_model' => User::class,

    /*
    |--------------------------------------------------------------------------
    | Polling Intervals (in seconds)
    |--------------------------------------------------------------------------
    */
    'polling' => [
        'messages' => 5,
        'sidebar' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    |
    | The disk must be private: every attachment is served through the
    | authorizing download route, never by its own storage URL.
    |
    */
    'uploads' => [
        'disk' => 'local',
        'directory' => 'team-chat-attachments',
        'max_size' => 10240, // KB
        'url_lifetime_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy to scope channels and conversations per team.
    | When enabled, set the tenant model and the method to resolve the
    | current tenant ID (e.g. from Filament's tenant or auth).
    |
    */
    'tenancy' => [
        'enabled' => false,
        'model' => null, // e.g. \App\Models\Team::class
        'resolver' => null, // callable or class that returns current tenant ID
    ],
];
