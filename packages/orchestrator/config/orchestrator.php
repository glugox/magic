<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Path
    |--------------------------------------------------------------------------
    |
    | By default the orchestrator resolves all relative paths using the Laravel
    | application's base path. When the helper is not available (for example
    | when running inside tests without a full application container) we fall
    | back to the current working directory.
    */
    'base_path' => function_exists('base_path') ? base_path() : getcwd(),

    /*
    |--------------------------------------------------------------------------
    | Where we keep the json only configuration specifications for modules ready to be built
    |--------------------------------------------------------------------------
    |
    | You may specify a custom path where your module specifications are stored.
    | This is useful if you want to keep them outside of the default location.
    | The path should be relative to the base path of the application.
    */
    'module_specs_path' => 'specs/modules',

    /*
    |--------------------------------------------------------------------------
    | Manifest Path
    |--------------------------------------------------------------------------
    |
    | The manifest file caches the discovered modules and their state. It is
    | stored inside the bootstrap cache directory by default.
    */
    'manifest_path' => 'bootstrap/cache/modules.php',

    /*
    |--------------------------------------------------------------------------
    | Composer installed.json Path
    |--------------------------------------------------------------------------
    |
    | Modules are primarily discovered through Composer metadata. You may
    | customise the location of the installed.json file if your application
    | keeps it somewhere else.
    */
    'installed_path' => 'vendor/composer/installed.json',

    /*
    |--------------------------------------------------------------------------
    | Additional Module Discovery Paths
    |--------------------------------------------------------------------------
    |
    | Some packages may ship a standalone module.json file outside of Composer.
    | The orchestrator will scan the glob patterns listed here and merge any
    | discovered modules into the manifest.
    */
    'module_json_paths' => [
        'vendor/*/*/module.json',
        'modules/*/module.json',
        'modules/*/*/module.json',
        'packages/*/*/module.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules Default Vendor
    |--------------------------------------------------------------------------
    |
    | When a module is discovered without an explicit namespace, the orchestrator
    | will assign it this default namespace. You may customise it here.
     */
    'default_vendor' => 'Glugox',

    /*
    |--------------------------------------------------------------------------
    | Modules Default Path
    |--------------------------------------------------------------------------
    |
    | When a module is discovered without an explicit path, the orchestrator
    | will assign it this default path relative to the base path. You may
    | customise it here.
    */
    'modules_default_path' => 'modules',

    /*
    |--------------------------------------------------------------------------
    | Modules Default Version Constraint
    |--------------------------------------------------------------------------
    |
    | When installing new modules through the orchestrator we will require the
    | package using this version constraint unless an explicit version is
    | provided. This allows you to control the preferred stability for new
    | module dependencies.
    */
    'modules_default_version' => '^1.0',

    /*
    |--------------------------------------------------------------------------
    | Default State
    |--------------------------------------------------------------------------
    |
    | When a new module is discovered the orchestrator will mark it as installed
    | and enabled according to these toggles unless the manifest already
    | contains explicit state for the module.
    */
    'auto_install' => true,
    'auto_enable' => true,

    /*
    |--------------------------------------------------------------------------
    | Developer Tools
    |--------------------------------------------------------------------------
    |
    | The orchestrator can expose diagnostic routes that help during local
    | development. These routes are disabled by default and should never be
    | enabled in production. Toggle them on when you need to inspect module
    | state or to step through the module manager with a debugger.
    */
    'dev_tools' => [
        'enabled' => env('ORCHESTRATOR_DEV_ENABLED'),
        'prefix' => 'dev/orchestrator',
        'middleware' => ['web'],
        'domain' => null,
    ],
];
