<?php

use Illuminate\Support\Facades\Route;

Route::middleware(config('attachments.route_middleware', ['api']))
    ->prefix('attachments')
    ->group(function (): void {
        $controller = config('attachments.controller', 'App\\Http\\Controllers\\AttachmentController');

        Route::post('/', [$controller, 'store'])->name('attachments.store');
        Route::delete('/{attachment}', [$controller, 'destroy'])->name('attachments.destroy');
        Route::get('/{attachable_type}/{attachable_id}', [$controller, 'index'])
            ->name('attachments.index');
    });
