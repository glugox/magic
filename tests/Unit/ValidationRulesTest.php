<?php

use Glugox\Magic\Enums\CrudActionType;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Validation\RuleSetHelper;
use Glugox\Magic\Validation\ValidationRule;

it('applies base ruleset for field type', function (): void {

    $field = Field::fromConfig([
        'name' => 'username',
        'type' => 'string',
        'nullable' => false,
        'sometimes' => false,
    ]);
    $rules = RuleSetHelper::rulesFor($field, CrudActionType::CREATE);
    $rulesArr = array_map(fn (ValidationRule $rule): string => (string) $rule, $rules);

    expect($rulesArr)->toBeArray()
        ->toContain('required')
        ->toContain('string')
        ->toContain('max:255');
});

it('removes required and adds nullable if field is nullable', function (): void {

    $nullableField = Field::fromConfig([
        'name' => 'email',
        'type' => 'email',
        'nullable' => true,
        'sometimes' => false,
    ]);

    $rules = RuleSetHelper::rulesFor($nullableField);
    $rulesArr = array_map(fn (ValidationRule $rule): string => (string) $rule, $rules);

    expect($rulesArr)->not->toContain('required')
        ->toContain('nullable')
        ->toContain('string')
        ->toContain('max:255');
});

it('adds sometimes if field is marked sometimes', function (): void {
    $sometimeField = Field::fromConfig([
        'name' => 'password',
        'type' => 'password',
        'nullable' => false,
        'sometimes' => true,
    ]);

    $rules = RuleSetHelper::rulesFor($sometimeField);
    $rulesArr = array_map(fn (ValidationRule $rule): string => (string) $rule, $rules);

    expect($rulesArr)->toContain('sometimes')
        ->toContain('string')
        ->toContain('min:8')
        ->toContain('confirmed');
});

it('nullable and sometimes together', function (): void {
    $nullableButSometimeField = Field::fromConfig([
        'name' => 'email',
        'type' => 'email',
        'nullable' => true,
        'sometimes' => true,
    ]);

    $rules = RuleSetHelper::rulesFor($nullableButSometimeField);
    $rulesArr = array_map(fn (ValidationRule $rule): string => (string) $rule, $rules);

    expect($rulesArr)->toContain('nullable')
        ->not->toContain('sometimes')
        ->not->toContain('required')
        ->toContain('string')
        ->toContain('email')
        ->toContain('max:255');
});
