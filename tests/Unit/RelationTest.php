<?php

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;

beforeEach(function () {

    $fieldName = Field::fromConfig([
        'name' => 'name',
        'type' => 'string',
        'main' => true,
    ]);

    $this->imageEntity = Entity::fromConfig([
        'name' => 'Image',
        'fields' => [
            [
                'name' => 'url',
                'type' => 'string',
            ],
            [
                'name' => 'imageable_id',
                'type' => 'string',
            ],
            [
                'name' => 'imageable_type',
                'type' => 'string',
            ]
        ],
    ]);

    $this->userEntity = new Entity('User');
    $this->roleEntity = new Entity('Role', fields: [
        $fieldName,
    ]);
    $this->postEntity = new Entity('Post');
    $this->commentEntity = new Entity('Comment');
});

it('infers relationName and keys for hasOne', function () {
    $relation = new Relation(
        RelationType::HAS_ONE,
        $this->userEntity,
        relatedEntityName: 'Profile'
    );

    expect($relation->getRelationName())->toBe('profile')
        ->and($relation->getForeignKey())->toBe('user_id')
        ->and($relation->getLocalKey())->toBe('id')
        ->and($relation->isHasOne())->toBeTrue();
});

it('infers relationName and keys for hasMany', function () {
    $relation = new Relation(
        RelationType::HAS_MANY,
        $this->userEntity,
        relatedEntityName: 'Post'
    );

    expect($relation->getRelationName())->toBe('posts')
        ->and($relation->getForeignKey())->toBe('user_id')
        ->and($relation->getLocalKey())->toBe('id')
        ->and($relation->isHasMany())->toBeTrue();
});

it('infers relationName and keys for belongsTo', function () {
    $relation = new Relation(
        RelationType::BELONGS_TO,
        $this->postEntity,
        relatedEntityName: 'User'
    );

    expect($relation->getRelationName())->toBe('user')
        ->and($relation->getForeignKey())->toBe('user_id')
        ->and($relation->getLocalKey())->toBe('id')
        ->and($relation->isBelongsTo())->toBeTrue();
});

it('infers relatedKey for belongsToMany', function () {
    $relation = new Relation(
        RelationType::BELONGS_TO_MANY,
        $this->userEntity,
        relatedEntityName: 'Role'
    );

    expect($relation->getRelationName())->toBe('roles')
        ->and($relation->getForeignKey())->toBe('user_id')
        ->and($relation->getRelatedKey())->toBe('role_id')
        ->and($relation->getPivotName())->toBe('role_user')
        ->and($relation->isBelongsToMany())->toBeTrue();
});

it('throws exception if belongsToMany has no related entity', function () {
    new Relation(
        RelationType::BELONGS_TO_MANY,
        $this->userEntity
    );
})->throws(RuntimeException::class, 'Entity name is not set for relation of type belongsToMany in entity User');

it('does not exception when morphTo has no relationName', function () {
    new Relation(
        RelationType::MORPH_TO,
        $this->imageEntity,
        relatedEntityName: null
    );
})->throwsNoExceptions();

it('infers morphOne relation', function () {
    $relation = new Relation(
        RelationType::MORPH_ONE,
        $this->userEntity,
        relatedEntityName: 'Image',
        relationName: 'image'
    );

    expect($relation->getRelationName())->toBe('image')
        ->and($relation->isMorphOne())->toBeTrue();
});

it('infers morphMany relation', function () {
    $relation = new Relation(
        RelationType::MORPH_MANY,
        $this->postEntity,
        relatedEntityName: 'Comment',
        relationName: 'comments'
    );

    expect($relation->getRelationName())->toBe('comments')
        ->and($relation->isMorphMany())->toBeTrue();
});

it('infers morphedByMany relation', function () {
    $relation = new Relation(
        RelationType::MORPHED_BY_MANY,
        $this->roleEntity,
        relatedEntityName: 'User',
        relationName: 'users'
    );

    expect($relation->getRelationName())->toBe('users')
        ->and($relation->isBelongsToMany())->toBeFalse() // ensure it's distinct
        ->and($relation->getType())->toBe(RelationType::MORPHED_BY_MANY);
});

it('infers morphTo foreign keys correctly', function () {
    $relation = new Relation(
        RelationType::MORPH_TO,
        $this->imageEntity,
        relatedEntityName: 'image'
    );

    expect($relation->getRelationName())->toBe('image')
        ->and($relation->getForeignKey())->toBe('imageable_id')
        ->and($relation->isMorphTo())->toBeTrue();
});

it('serializes to json correctly', function () {
    $relation = new Relation(
        RelationType::HAS_MANY,
        $this->userEntity,
        relatedEntityName: 'Post'
    );

    $json = json_decode($relation->toJson(), true);

    expect($json)->toMatchArray([
        'type' => 'hasMany',
        'relatedEntityName' => 'Post',
        'relationName' => 'posts',
        'foreignKey' => 'user_id',
        'localKey' => 'id',
        'localEntityName' => 'User',
    ]);
});

/**
 * --- Route & controller helpers ---
 */
it('generates API path', function () {
    $relation = new Relation(RelationType::HAS_MANY, $this->userEntity, relatedEntityName: 'Role');

    expect($relation->getApiPath())->toBe('roles');
});

it('generates Web path (same as API path)', function () {
    $relation = new Relation(RelationType::HAS_MANY, $this->userEntity, relatedEntityName: 'Post');

    expect($relation->getWebPath())->toBe('posts');
});

it('generates controller class FQN', function () {
    $relation = new Relation(RelationType::HAS_MANY, $this->userEntity, relatedEntityName: 'Role');

    expect($relation->getControllerFullQualifiedName())
        ->toBe('\\App\\Http\\Controllers\\User\\UserRoleController');
});

it('generates route definition path', function () {
    $relation = new Relation(RelationType::HAS_MANY, $this->userEntity, relatedEntityName: 'Role');

    expect($relation->getRouteDefinitionPath())
        ->toBe('users/{user}/roles');
});

it('returns eager fields string', function () {
    $relation = new Relation(RelationType::HAS_MANY, $this->userEntity, relatedEntityName: 'Role');
    $relation->setRelatedEntity($this->roleEntity);

    expect($relation->getEagerFieldsStr())->toBe('id,name');
});

it('creates morphOne relation', function () {
    $rel = new Relation(
        RelationType::MORPH_ONE,
        $this->imageEntity,
        'image',
        null
    );

    expect($rel->isPolymorphic())->toBeTrue();
    expect($rel->getForeignKey())->toBe('imageable_id');
});

it('creates morphMany relation', function () {
    $rel = new Relation(
        RelationType::MORPH_MANY,
        $this->userEntity,
        'Image',
        null,
        morphName: 'imageable'
    );

    expect($rel->isPolymorphic())->toBeTrue();
    expect($rel->getRelationName())->toBe('images');
    expect($rel->getForeignKey())->toBe('imageable_id');
    expect($rel->getType())->toBe(RelationType::MORPH_MANY);
    expect($rel->getLocalKey())->toBe('id');
});

it('creates morphTo relation', function () {
    $rel = new Relation(
        type: RelationType::MORPH_TO,
        localEntity: $this->imageEntity,
        // morphTo doesn’t know target ahead of time
    );

    expect($rel->isPolymorphic())->toBeTrue();
    expect($rel->getForeignKey())->toBe('imageable_id');
    expect($rel->getMorphTypeKey())->toBe('imageable_type');
    expect($rel->getRelationName())->toBe('image');
});

it('throws exception when creating MORPH_TO_MANY relation without related entity name', function () {
    $rel = new Relation(
        RelationType::MORPH_TO_MANY,
        $this->userEntity
    );
    // Related entity name is not set for relation of type morphToMany in entity User
})->throws(RuntimeException::class, 'Related entity name is not set for relation of type morphToMany in entity User');

it('creates MORPH_TO_MANY relation', function () {
    $rel = new Relation(
        type: RelationType::MORPH_TO_MANY,
        localEntity: $this->userEntity,
        relatedEntityName: 'Image'
    );

    expect($rel->isPolymorphic())->toBeTrue();
    expect($rel->getMorphName())->toBe('imageable');
    expect($rel->getPivotName())->toBe('imageables');

    // Related entity name is not set for relation of type morphToMany in entity User
});

it('creates morphedByMany relation', function () {
    $rel = new Relation(
        RelationType::MORPHED_BY_MANY,
        $this->imageEntity,
        'Post'
    );

    expect($rel->isPolymorphic())->toBeTrue();
    expect($rel->getMorphName())->toBe('postable');
    expect($rel->getPivotName())->toBe('postables');
});
