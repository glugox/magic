<?php

// config file for the Magic package
return [

    'paths' => [
        // Support types file
        // In which file we should keep global type like Entity, Field, etc.
        'support_types_file' => env('MAGIC_SUPPORT_TYPES_FILE', resource_path('js/types/support.ts')),

        // Entity specific types file
        // In which file we should keep entity specific types.
        'entity_types_file' => env('MAGIC_ENTITY_TYPES_FILE', resource_path('js/types/entities.ts')),
    ],

    // Prism default model
    'ollama_model' => env('MAGIC_OLLAMA_MODEL', 'MFDoom/deepseek-r1-tool-calling:latest'),

    'logging' => [
        'enabled' => true,
        'level' => 'debug', // Can be 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    /**
     * How to dynamically generate fields for models?
     * This is a mapping of some most common field names and types
     * to Faker methods.
     */
    'faker_mappings' => [
        // Match by field name (partial match)
        'email' => 'unique()->safeEmail()',
        'name' => 'name()',
        'password' => 'password()',
        'date' => 'date()',
        'phone' => 'phoneNumber()',
        'street' => 'streetAddress()',
        'city' => 'city()',
        'country' => 'country()',
        'postal_code' => 'postcode()',
        'title' => 'sentence(3)',
        'description' => 'paragraph()',
        'content' => 'text()',
        'first_name' => 'firstName()',
        'last_name' => 'lastName()',
        'full_name' => 'name()',
        'address' => 'address()',
        'comment' => 'sentence()',
        'body' => 'paragraph()',
        'username' => 'userName()',
        'url' => 'url()',
        'price' => 'randomFloat()',
        'total' => 'randomFloat()',
        'amount' => 'randomFloat()',
        'count' => 'randomNumber()',
        'quantity' => 'randomNumber()',
        'expires_at' => 'dateTimeBetween("now", "+1 year")',
        'available_from' => 'dateTimeBetween("now", "+1 year")',
        'placed_at' => 'dateTimeBetween("now", "+1 year")',

        // Match by exact type
        'type:string' => 'word()',
        'type:integer' => 'randomNumber()',
        'type:unsignedBigInteger' => 'randomNumber()',
        'type:bigInteger' => 'randomNumber()',
        'type:float' => 'randomFloat(2, 1, 100)',
        'type:double' => 'randomFloat(2, 1, 100)',
        'type:decimal' => 'randomFloat(2, 1, 100)',
        'type:boolean' => 'boolean()',
        'type:text' => 'text()',
        'type:date' => 'date()',
        'type:datetime' => 'dateTime()',
    ],
    /**
     * Some models may require specific traits or base classes
     * and other fields to be defined.
     */
    'model_presets' => [
        'User' => [
            'extends' => '\Illuminate\Foundation\Auth\User',
            'traits' => [
                '\Illuminate\Notifications\Notifiable',
            ],
            'default_fields' => [
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'email', 'type' => 'string', 'nullable' => false, 'unique' => true],
                ['name' => 'email_verified_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'password', 'type' => 'string', 'nullable' => false],
                ['name' => 'remember_token', 'type' => 'string', 'nullable' => true],
            ],
            'fillable' => ['name', 'email', 'password'],
            'hidden' => ['password', 'remember_token'],
            'casts' => [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
            ],
        ],
    ],
    'dev' => [
        'default_password_hash' => '$2y$12$00A.1FrCk3FctOEVIHlkLu5qYNfFdBGJUCyzdMaGcvC9CPTgPoIgK',
    ],
];
