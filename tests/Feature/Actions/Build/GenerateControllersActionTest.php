<?php

use Glugox\Magic\Actions\Build\GenerateControllersAction;

it('generates controller and routes for entities', function () {

    // Prepare
    $action = app(GenerateControllersAction::class);
    $buildContext = getFixtureBuildContext();

    // Act
    $buildContext = $action($buildContext);

    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0)
        ->and($buildContext->getFilesGenerationUpdate()->created)->toHaveLength(44);

});
