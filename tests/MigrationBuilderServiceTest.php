<?php

use Glugox\Magic\Services\MigrationBuilderService;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->configMock = $this->createMock(Config::class);
    $this->entityMock = $this->createMock(Entity::class);

    $this->configMock->entities = [$this->entityMock];

    $this->entityMock->method('getTableName')->willReturn('test_table');
    $this->entityMock->method('getFields')->willReturn([]);
    $this->entityMock->method('getRelations')->willReturn([]);
    $this->entityMock->method('hasTimestamps')->willReturn(true);
});

it('creates migration file when table does not exist and no migration exists', function () {
    Schema::shouldReceive('hasTable')->with('test_table')->andReturn(false);
    File::shouldReceive('glob')->andReturn([]);
    File::shouldReceive('put')->once();
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $service = new MigrationBuilderService($this->configMock);
    $service->build();

    expect(true)->toBeTrue();
});

it('skips migration creation if migration file already exists', function () {
    Schema::shouldReceive('hasTable')->with('test_table')->andReturn(false);
    File::shouldReceive('glob')->andReturn(['existing_migration.php']);
    File::shouldReceive('put')->never();
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $service = new MigrationBuilderService($this->configMock);
    $service->build();

    expect(true)->toBeTrue();
});

it('creates update migration if table exists', function () {
    Schema::shouldReceive('hasTable')->with('test_table')->andReturn(true);
    File::shouldReceive('glob')->andReturn([]);
    File::shouldReceive('put')->once();
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();
    Schema::shouldReceive('getColumnListing')->andReturn([]);

    $service = new MigrationBuilderService($this->configMock);
    $service->build();

    expect(true)->toBeTrue();
});

it('creates pivot migration for many-to-many relation', function () {
    $relationMock = Mockery::mock();
    $relationMock->shouldReceive('isManyToMany')->andReturn(true);
    $relationMock->shouldReceive('getPivotName')->andReturn('pivot_table');
    $relationMock->shouldReceive('getLocalKey')->andReturn('local_id');
    $relationMock->shouldReceive('getTableName')->andReturn('related_table');
    $this->entityMock->method('getRelations')->willReturn([$relationMock]);
    $this->entityMock->method('getForeignKey')->willReturn('entity_id');
    $this->entityMock->method('getTableName')->willReturn('test_table');

    File::shouldReceive('glob')->andReturn([]);
    File::shouldReceive('put')->once();
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $service = new MigrationBuilderService($this->configMock);
    $service->build();

    expect(true)->toBeTrue();
});

it('handles exception during migration file creation gracefully', function () {
    Schema::shouldReceive('hasTable')->with('test_table')->andReturn(false);
    File::shouldReceive('glob')->andReturn([]);
    File::shouldReceive('put')->andThrow(new Exception('File error'));
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->never();

    $service = new MigrationBuilderService($this->configMock);

    $exceptionThrown = false;
    try {
        $service->build();
    } catch (Exception $e) {
        $exceptionThrown = true;
    }

    expect($exceptionThrown)->toBeTrue();
});
