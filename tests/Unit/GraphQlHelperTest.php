<?php

use Glugox\Magic\Helpers\GraphQlHelper;
use Glugox\Magic\Support\Config\App;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Normalizer\GraphQlTypeNormalizer;
use Glugox\Magic\Support\Config\RelationType;

beforeEach(function () {
    $this->helper = new GraphQlHelper(new GraphQlTypeNormalizer());
});

it('can populate App from SDL', function () {
    $sdl = <<<SDL
type App @config {
    name: String @default("MyApp")
}
SDL;

    $app = new App('');
    $this->helper->populateApp($app, $sdl);

    expect($app->name)->toBe('MyApp')   ;
});

it('can extract entity with scalar fields', function () {
    $match = [
        1 => 'type',
        2 => 'User',
        3 => null,
        4 => <<<SDL
id: ID!
name: String
age: Int @default(18) @min(0)
SDL
    ];

    $entity = $this->helper->extractEntity($match);

    expect($entity)->toBeInstanceOf(Entity::class)
        ->and($entity->getName())->toBe('User');

    $fields = $entity->getFields();
    expect($fields)->toHaveCount(3);

    $idField = $fields[0];
    expect($idField->name)->toBe('id')
        ->and($idField->required)->toBeTrue()
        ->and($idField->nullable)->toBeFalse();

    $ageField = $fields[2];
    expect($ageField->default)->toBe(18)
        ->and($ageField->min)->toBe(0);
});

it('can extract enum values', function () {
    $directives = ['@values(RED,GREEN,BLUE)'];
    $values = $this->helper->extractEnumValues($directives);

    expect($values)->toEqual(['RED', 'GREEN', 'BLUE']);
});

it('can detect relation type', function () {
    expect($this->helper->detectRelationType(['@hasMany']))->toBe(RelationType::HAS_MANY);
    expect($this->helper->detectRelationType(['@belongsTo']))->toBe(RelationType::BELONGS_TO);
});

it('can parse default, min and max from directives', function () {
    $directives = ['@default(10)', '@min(5)', '@max(100)'];

    $default = $this->helper->extractDefault(FieldType::INTEGER, $directives);
    [$min, $max] = $this->helper->extractMinMax($directives);

    expect($default)->toBe(10)
        ->and($min)->toBe(5)
        ->and($max)->toBe(100);
});

it('can parse relation name and keys', function () {
    $directives = ['@name("myRelation")', '@foreignKey("fk_id")', '@localKey("id")'];

    expect($this->helper->parseRelationName($directives, 'defaultName'))->toBe('myRelation');

    [$fk, $lk, $rfk] = $this->helper->parseKeys($directives);
    expect($fk)->toBe('fk_id')
        ->and($lk)->toBe('id');
});
