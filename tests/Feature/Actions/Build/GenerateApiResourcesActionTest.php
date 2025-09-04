<?php

use Glugox\Magic\Actions\Build\GenerateApiResourcesAction;

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up generated files before each test
    File::deleteDirectory(app_path('Http/Resources'));
    File::makeDirectory(app_path('Http/Resources'), 0755, true);
});

it('generates api resource and collection for entity', function () {

    $buildCotext = getFixtureBuildContext();

    $action = app(GenerateApiResourcesAction::class);
    $action($buildCotext);

    $resourcePath = app_path('Http/Resources/UserResource.php');
    $collectionPath = app_path('Http/Resources/UserCollection.php');

    expect(File::exists($resourcePath))->toBeTrue()
        ->and(File::exists($collectionPath))->toBeTrue();

    $resourceContent = File::get($resourcePath);
    $collectionContent = File::get($collectionPath);

    expect($resourceContent)->toContain('class UserResource')
        ->and($resourceContent)->toContain('extends JsonResource')
        ->and($resourceContent)->toContain("'id' => \$this->id")
        ->and($resourceContent)->toContain("'name' => \$this->name")
        ->and($collectionContent)->toContain('class UserCollection')
        ->and($collectionContent)->toContain('extends ResourceCollection')
        ->and($collectionContent)->toContain("'data' => UserResource::collection(\$this->collection)");
    // From HasName accessor

});

it('respects name accessor from HasName', function () {
    $buildCotext = getFixtureBuildContext();

    $action = app(GenerateApiResourcesAction::class);
    $action($buildCotext);

    $resourcePath = app_path('Http/Resources/UserResource.php');
    $resourceContent = File::get($resourcePath);

    expect($resourceContent)->toContain("'name' => \$this->name");
});
