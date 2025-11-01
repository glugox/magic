<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('acme-analytics::dashboard.index', [
        'headline' => __('acme-analytics::messages.headline'),
        'metrics' => [
            ['label' => 'Active users', 'value' => 128],
            ['label' => 'Conversion rate', 'value' => '4.2%'],
        ],
    ]);
})->name('index');
