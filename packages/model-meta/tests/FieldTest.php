<?php

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\Fields\Number;
use Glugox\ModelMeta\Fields\Text;
use Glugox\ModelMeta\FieldType;
use Glugox\ModelMeta\Fields\Email;
use Glugox\ModelMeta\Fields\Slug;

it('can create a generic field', function () {
    $field = Text::make('username');

    expect($field)->toBeInstanceOf(Field::class)
        ->and($field->type)->toBe(FieldType::TEXT)
        ->and($field->name)->toBe('username')
        ->and($field->label)->toBeNull()
        ->and($field->nullable)->toBeFalse()
        ->and($field->default)->toBeNull();
});

it('can set rules, nullable, default, maxLength', function () {
    $field = Text::make('username')
        ->rules(['required', 'min:3'])
        ->default('guest')
        ->max(50);

    expect($field->rules)->toEqual(['required', 'min:3', 'max:50'])
        ->and($field->nullable)->toBeFalse()
        ->and($field->default)->toBe('guest')
        ->and($field->max)->toBe(50);
});

it('can set rules, removes required when set to nullable', function () {
    $field = Text::make('username')
        ->rules(['required', 'min:3'])
        ->nullable()
        ->default('guest')
        ->max(50);

    expect($field->rules)->toEqual(['min:3', 'nullable', 'max:50'])
        ->and($field->nullable)->toBeTrue()
        ->and($field->default)->toBe('guest')
        ->and($field->max)->toBe(50);
});

it('can create an Email field', function () {
    $email = Email::make('customer_email');

    expect($email)->toBeInstanceOf(Email::class)
        ->and($email->type)->toBe(FieldType::EMAIL)
        ->and($email->name)->toBe('customer_email')
        ->and($email->rules)->toContain('email');
});

it('can create a Slug field with sourceField', function () {
    $slug = Slug::make('post_slug', 'title');

    expect($slug)->toBeInstanceOf(Slug::class)
        ->and($slug->type)->toBe(FieldType::SLUG)
        ->and($slug->name)->toBe('post_slug');
});

it('defaults to non-nullable unless set', function () {
    $field = Text::make('nickname');
    expect($field->nullable)->toBeFalse();
});

it('can mark field as sortable and searchable', function () {
    $field = Text::make('name');
    $field->sortable = true;
    $field->searchable = true;

    expect($field->sortable)->toBeTrue()
        ->and($field->searchable)->toBeTrue();
});

it('can define a set of specific fields with required properties', function () {
    $fields = [
        Text::make('first_name')->rules(['required'])->max(50),
        Text::make('last_name')->nullable(),
        Email::make('email'),
        Number::make('age')->default(18)->min(0)->max(120),
    ];

    expect($fields)->toHaveCount(4)
        ->and($fields[0]->name)->toBe('first_name')
        ->and($fields[0]->type->value)->toBe('text')
        ->and($fields[0]->rules)->toContain('required')
        ->and($fields[0]->max)->toBe(50)
        ->and($fields[0]->nullable)->toBe(false)
        ->and($fields[1]->nullable)->toBe(true)
        ->and($fields[2]->type->value)->toBe('email')
        ->and($fields[3]->type->value)->toBe('number')
        ->and($fields[3]->default)->toBe(18)
        ->and($fields[3]->min)->toBe(0)
        ->and($fields[3]->max)->toBe(120);
});

it('can chain fluent methods on specific fields', function () {
    $field = Text::make('title')
        ->nullable()
        ->rules(['required'])
        ->max(255)
        ->default('Untitled');

    expect($field->name)->toBe('title')
        ->and($field->nullable)->toBeTrue()
        ->and($field->rules)->toContain('required')
        ->and($field->max)->toBe(255)
        ->and($field->default)->toBe('Untitled');
});
