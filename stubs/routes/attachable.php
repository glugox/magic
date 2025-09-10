<?php

use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix('attachments')
    ->group(function (): void {
        // Upload a new attachment (image/file)
        Route::post('/', [AttachmentController::class, 'store'])->name('attachments.store');

        // Delete an attachment
        Route::delete('/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

        // Optional: list attachments for a model
        Route::get('/{attachable_type}/{attachable_id}', [AttachmentController::class, 'index'])
            ->name('attachments.index');
    });
