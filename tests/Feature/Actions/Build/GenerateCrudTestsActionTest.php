<?php

use Glugox\Magic\Actions\Build\GenerateCrudTestsAction;

it('generates crud test for all models', function (): void {

    // Prepare
    $action = app(GenerateCrudTestsAction::class);
    $buildContext = getFixtureBuildContext();

    // Act
    $buildContext = $action($buildContext);

    expect($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0)
        ->and($buildContext->getFilesGenerationUpdate()->created)->toBeGreaterThan(0);

});
