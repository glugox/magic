<?php

use Glugox\Magic\Actions\Build\GenerateVuePagesAction;

it('generates Vue pages for entities', function () {

    // Prepare
    $action = app(GenerateVuePagesAction::class);
    $buildContext = getFixtureBuildContext();

    // Act
    $buildContext = $action($buildContext);

    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0)
        ->and($buildContext->getFilesGenerationUpdate()->created)->toHaveLength(53);

});
