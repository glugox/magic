<?php

// config for Glugox/Ai
return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Driver
    |--------------------------------------------------------------------------
    */
    'default_driver' => env('AI_DEFAULT_PROVIDER', 'ollama'),

    'drivers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
        ],
        'ollama' => [
            'url' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'gpt-oss:20b'),
            'class' => \Glugox\Ai\Drivers\PrismDriver::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enabled Tools
    |--------------------------------------------------------------------------
    | List the tools you want to register for the agent.
    | You can disable any by removing or commenting them out.
    */
    'tools' => [
        'calculator' => true,
        'web_search' => true,
        'memory' => true,
        'file_io' => true,
        'api_caller' => true,
        'ollama' => true,
        'database' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool-specific settings
    |--------------------------------------------------------------------------
    */
    'settings' => [

        'web_search' => [
            'provider' => 'serper', // serper, bing, brave...
            'api_key' => env('SERPER_API_KEY'),
        ],

        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
        ],

        'file_io' => [
            'disk' => env('AI_FILE_DISK', 'local'),
        ],
    ],

];
