<?php

// config file for the Magic package
return [

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

        // Match by exact type
        'type:string' => 'word()',
        'type:integer' => 'randomNumber()',
        'type:unsignedBigInteger' => 'randomNumber()',
        'type:bigInteger' => 'randomNumber()',
        'type:float' => 'randomFloat(2, 1, 100)',
        'type:double' => 'randomFloat(2, 1, 100)',
        'type:decimal' => 'randomFloat(2, 1, 100)',
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
];
