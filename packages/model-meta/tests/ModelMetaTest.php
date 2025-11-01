<?php

use Dummies\UserMeta;
use Dummies\ProductMeta;
use Glugox\ModelMeta\Fields\Enum;
use Glugox\ModelMeta\FieldType;

it('defines UserMeta fields Nova-style', function () {
    $meta = new UserMeta();
    $fields = $meta->fields();

    expect($fields)->toHaveCount(7)
        ->and($fields[0]->type)->toBe(FieldType::ID)
        ->and($fields[0]->sortable)->toBeTrue()
        ->and($fields[1]->type)->toBe(FieldType::TEXT)
        ->and($fields[1]->required)->toBeTrue();

});

it('defines ProductMeta fields Nova-style', function () {
    $meta = new ProductMeta();
    $fields = $meta->fields();

    // Cast to Enum and test values
    /** @var Enum $enumField */
    $enumField = $fields[3];
    expect($fields)->toHaveCount(6)
        ->and($fields[2]->type)->toBe(FieldType::NUMBER)
        ->and($fields[2]->step)->toBe(0.01)
        ->and($enumField->type)->toBe(FieldType::ENUM)
        ->and($enumField->values)->toBe(['draft', 'published']);

});