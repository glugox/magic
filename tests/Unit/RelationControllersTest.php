<?php

use Glugox\Magic\Actions\Build\GenerateControllersAction;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;

it('generates a relation controller template when related entity exists', function () {
    // Fake config entities
    $userEntity = new Entity('User');
    $roleEntity = new Entity('Role');

    // Add relation User -> Role (hasMany, belongsToMany, etc.)
    $relation = new Relation(
        type: RelationType::BELONGS_TO_MANY,
        localEntity: $userEntity,
        entityName: 'Role',
        relatedEntity: $roleEntity,
    );
    $userEntity->addRelation($relation);

    $buildContext = getFixtureBuildContext();

    $action = app(GenerateControllersAction::class);

    // Inject context into action
    $reflection = new ReflectionClass($action);
    $property = $reflection->getProperty('context');
    $property->setAccessible(true);
    $property->setValue($action, $buildContext);

    $result = $action->buildRelationControllers($userEntity, $relation);

    expect($result)
        ->toBeString()
        ->and($result)->toContain('class UserRoleController')
        ->and($result)->toContain('public function index');
    // ->and($result)->toContain('public function update');
});
