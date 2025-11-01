<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Action Classes
    |--------------------------------------------------------------------------
    | If empty => allow any Action implementation. Otherwise, only classes listed
    | here are allowed to be executed via HTTP.
    */
    'allowed' => [
        // App\Actions\ExportUsers::class,
    ],

    /*
    | Broadcast channel name pattern.
    */
    'broadcast_channel' => 'private-action-run.{id}',

    /*
    | Route prefix and middleware group for the controller.
    */
    'route' => [
        'prefix' => 'api/actions',
        'middleware' => ['web', 'auth'],
    ],
];
