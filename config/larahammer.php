<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Output Target
    |--------------------------------------------------------------------------
    | Options: blade, filament, api, all
    */
    'default_target' => env('LARAHAMMER_TARGET', 'blade'),

    /*
    |--------------------------------------------------------------------------
    | Stub Path Override
    |--------------------------------------------------------------------------
    | Set a custom stubs directory. Leave null to use the package defaults.
    | Run `php artisan vendor:publish --tag=larahammer-stubs` to publish stubs.
    */
    'stubs_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Force Overwrite
    |--------------------------------------------------------------------------
    | If true, existing files will always be overwritten without --force flag.
    */
    'force' => false,

];
