<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('hello-world', static fn (): array => [
    'message' => 'Hello from the Hello World module!',
]);
