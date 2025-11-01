<?php

use Glugox\ModelMeta\Builder\ModelMetaBuilder;

it('generates UserMeta class from JSON config', function () {
    $config = [
        'name' => 'User',
        'icon' => 'Users',
        'fields' => [
            ['name' => 'id', 'type' => 'id', 'nullable' => false],
            ['name' => 'name', 'type' => 'string', 'sortable' => true, 'searchable' => true],
            ['name' => 'email', 'type' => 'email', 'unique' => true, 'sortable' => true, 'searchable' => true],
            ['name' => 'password', 'type' => 'password', 'nullable' => false, 'hidden' => true],
            ['name' => 'started_at', 'type' => 'date', 'nullable' => true],
        ],
    ];

    $builder = new ModelMetaBuilder($config);
    $code = $builder->generate();

    // Assert that the class name is correct
    expect($code)->toContain('class UserMeta extends ModelMeta')
        ->and($code)->toContain("Id::make(") // can have "id" or not, falls back to "id"
        ->and($code)->toContain("Text::make('name')->sortable()->searchable()")
        ->and($code)->toContain("Email::make('email')->unique()->sortable()->searchable()")
        ->and($code)->toContain("Password::make('password')->hidden()")
        ->and($code)->toContain("Date::make('started_at')->nullable()");

    // Assert that the fields are defined correctly
});
