<?php


use Glugox\Magic\Actions\Build\GenerateControllersAction;
use Glugox\Magic\Support\BuildContext;

it('generates controller and routes for entities', function () {

    // Prepare
    $action = new GenerateControllersAction();
    $buildContext = getFixtureBuildContext();

    // Act
    $buildContext = $action($buildContext);

    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0)
        ->and($buildContext->getFilesGenerationUpdate()->created)->toHaveLength(12);

});
