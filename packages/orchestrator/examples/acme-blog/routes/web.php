<?php

use Illuminate\Support\Facades\Route;

Route::get('/blog', function () {
    return view('acme-blog::posts.index', [
        'title' => __('acme-blog::messages.title'),
        'posts' => [
            ['title' => 'Hello from the Blog module', 'excerpt' => 'Rendered through the orchestrator.'],
        ],
    ]);
})->name('index');
