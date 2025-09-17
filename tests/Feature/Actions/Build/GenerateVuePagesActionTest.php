<?php

use Glugox\Magic\Actions\Build\GenerateVuePagesAction;

it('generates Vue pages for entities', function (): void {

    // Prepare
    $action = app(GenerateVuePagesAction::class);
    $buildContext = getFixtureBuildContext();

    // Act
    $buildContext = $action($buildContext);

    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0);

});
