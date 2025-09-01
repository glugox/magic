<?php

use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Validation\RuleSet;
use Glugox\Magic\Enums\CrudActionType;

it('applies base ruleset for field type', function () {

    $field = Field::fromConfig([
        'name' => 'username',
        'type' => 'string',
        'nullable' => false,
        'sometimes' => false,
    ]);
    $rules = RuleSet::rulesFor($field, CrudActionType::CREATE);

    expect($rules)->toBeArray()
        ->toContain('required')
        ->toContain('string')
        ->toContain('max:255');
});

it('removes required and adds nullable if field is nullable', function () {

    $nullableField = Field::fromConfig([
        'name' => 'email',
        'type' => 'email',
        'nullable' => true,
        'sometimes' => false,
    ]);

    $rules = RuleSet::rulesFor($nullableField);

    expect($rules)->not->toContain('required')
        ->toContain('nullable')
        ->toContain('string')
        ->toContain('max:255');
});

it('adds sometimes if field is marked sometimes', function () {
    $sometimeField = Field::fromConfig([
        'name' => 'password',
        'type' => 'password',
        'nullable' => false,
        'sometimes' => true,
    ]);

    $rules = RuleSet::rulesFor($sometimeField);

    expect($rules)->toContain('sometimes')
        ->toContain('string')
        ->toContain('min:8')
        ->toContain('confirmed');
});

it('nullable and sometimes together', function () {
    $nullableButSometimeField = Field::fromConfig([
        'name' => 'email',
        'type' => 'email',
        'nullable' => true,
        'sometimes' => true,
    ]);

    $rules = RuleSet::rulesFor($nullableButSometimeField);

    expect($rules)->toContain('nullable')
        ->not->toContain('sometimes')
        ->not->toContain('required')
        ->toContain('string')
        ->toContain('email')
        ->toContain('max:255');
});
