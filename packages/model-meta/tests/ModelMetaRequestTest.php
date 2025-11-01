<?php

use Dummies\Product;
use Dummies\ProductMeta;
use Glugox\ModelMeta\ModelMetaResolver;
use Glugox\ModelMeta\Requests\MetaRequest;
use Illuminate\Routing\Route;


beforeEach(function () {

    ModelMetaResolver::setDefaultNamespace('Dummies');
    MetaRequest::setDefaultModelNamespace('Dummies');

    $this->makeRequestWithRoute = function (string $routeName): MetaRequest {
        $request = MetaRequest::create('/dummy-url', 'GET');

        $route = new Route(['GET'], '/dummy-url', []);
        $route->name($routeName);

        // Bind the route to the request
        $request->setRouteResolver(fn() => $route);

        return $request;
    };
});

it('resolves model and meta from plural route name', function () {
    $request = ($this->makeRequestWithRoute)('products.index');

    expect($request->resourceName())->toBe('products');

    $modelClass = $request->modelClass();
    $metaClass  = $request->metaClass();

    expect($modelClass)->toBe(Product::class);
    expect($metaClass)->toBe(ProductMeta::class);

    $meta = $request->meta();
    expect($meta)->toBeInstanceOf($metaClass);
    expect($meta->fields())->toBeArray();
});

it('resolves correctly with uppercase plural route', function () {
    $request = ($this->makeRequestWithRoute)('Products.index');

    expect(strtolower($request->resourceName()))->toBe('products');

    $modelClass = $request->modelClass();
    $metaClass  = $request->metaClass();

    expect($modelClass)->toBe(Product::class);
    expect($metaClass)->toBe(ProductMeta::class);
});

it('resolves from singular route name', function () {
    $request = ($this->makeRequestWithRoute)('product.show');

    expect($request->resourceName())->toBe('product');

    $modelClass = $request->modelClass();
    $metaClass  = $request->metaClass();

    expect($modelClass)->toBe(Product::class);
    expect($metaClass)->toBe(ProductMeta::class);
});
