<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),
    'clientId' => env('REDDIT_CLIENT_ID'),
    'clientSecret' => env('REDDIT_CLIENT_SECRET'),
    'redirectUri' => env('REDDIT_REDIRECT_URI'),
    'userAgent' => env('REDDIT_USER_AGENT'),
    'scopes' => env('REDDIT_SCOPES', [
        'read',
        'account'
    ])

];
