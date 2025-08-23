<?php

namespace Tests\Services;

use Glugox\Magic\Services\ControllerBuilderService;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Glugox\Magic\Tests\TestCase;

class ControllerBuilderServiceTest extends TestCase
{
    protected $configMock;
    protected $entityMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configMock = $this->createMock(Config::class);
        $this->entityMock = $this->createMock(Entity::class);

        $this->configMock->entities = [$this->entityMock];

        $this->entityMock->method('getClassName')->willReturn('TestModel');
        $this->entityMock->method('getFullyQualifiedModelClass')->willReturn('App\\Models\\TestModel');
        $this->entityMock->method('getName')->willReturn('tests');
        $this->entityMock->method('getFolderName')->willReturn('Tests');
        $this->entityMock->method('getRelations')->willReturn([]);
        $this->entityMock->method('getFields')->willReturn([]);
        $this->entityMock->method('getRouteName')->willReturn('tests');
    }

    public function testBuildCreatesControllerAndRoutes()
    {
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('makeDirectory')->once();
        File::shouldReceive('put')->times(3);
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->times(3);
        File::shouldReceive('get')->andReturn("<?php\n");
        File::shouldReceive('append')->andReturnNull();
        File::shouldReceive('exists')->andReturn(true);

        $service = new ControllerBuilderService($this->configMock);
        $service->build();

        $this->assertTrue(true);
    }
}
