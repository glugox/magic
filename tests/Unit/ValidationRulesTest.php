<?php

use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Validation\RuleSetHelper;
use Glugox\Magic\Enums\CrudActionType;

it('applies base ruleset for field type', function () {

    $field = Field::fromConfig([
        'name' => 'username',
        'type' => 'string',
        'nullable' => false,
        'sometimes' => false,
    ]);
    $rules = RuleSetHelper::rulesFor($field, CrudActionType::CREATE);
    $rulesArr = array_map(function ($rule) { return (string)$rule; }, $rules);

    expect($rulesArr)->toBeArray()
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

    $rules = RuleSetHelper::rulesFor($nullableField);
    $rulesArr = array_map(function ($rule) { return (string)$rule; }, $rules);

    expect($rulesArr)->not->toContain('required')
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

    $rules = RuleSetHelper::rulesFor($sometimeField);
    $rulesArr = array_map(function ($rule) { return (string)$rule; }, $rules);

    expect($rulesArr)->toContain('sometimes')
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

    $rules = RuleSetHelper::rulesFor($nullableButSometimeField);
    $rulesArr = array_map(function ($rule) { return (string)$rule; }, $rules);

    expect($rulesArr)->toContain('nullable')
        ->not->toContain('sometimes')
        ->not->toContain('required')
        ->toContain('string')
        ->toContain('email')
        ->toContain('max:255');
});
