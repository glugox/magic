<?php

use Glugox\Magic\Actions\Build\GenerateEnumsAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->phpEnumsPath = app_path('Enums');
    $this->tsEnumsPath = resource_path('js/enums');

    // Clean directories before each test
    File::deleteDirectory($this->phpEnumsPath);
    File::deleteDirectory($this->tsEnumsPath);
});

it('generates PHP and TS enums for enum fields', function () {
    // Arrange: mock entity with enum field
    $buildContext = getFixtureBuildContext();

    // Act
    $action = app(GenerateEnumsAction::class);
    $action($buildContext);

    // Assert PHP enum
    $phpFile = $this->phpEnumsPath . '/ProductStatusEnum.php';
    expect(File::exists($phpFile))->toBeTrue();

    $phpContent = File::get($phpFile);
    expect($phpContent)
        ->toContain('enum ProductStatusEnum')
        ->toContain("case Active = 'active'")
        ->toContain("case Inactive = 'inactive'")
        ->toContain("case Discontinued = 'discontinued'");

    // Assert TS enum
    $tsFile = $this->tsEnumsPath . '/ProductStatusEnum.ts';
    expect(File::exists($tsFile))->toBeTrue();

    $tsContent = File::get($tsFile);
    expect($tsContent)
        ->toContain('export const ProductStatusEnum')
        ->toContain("Active: 'active'")
        ->toContain("Inactive: 'inactive'")
        ->toContain("Discontinued: 'discontinued'");
});
