<?php

use Illuminate\Support\Facades\Route;

Route::get('/module-fixture', fn () => 'module-fixture-route')
    ->name('module.fixture.index');
