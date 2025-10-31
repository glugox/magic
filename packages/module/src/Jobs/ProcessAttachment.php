<?php

namespace Glugox\Module\Jobs;

use Glugox\Module\Models\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Attachment $attachment, protected string $tempFilePath)
    {
    }

    public function handle(): void
    {
        try {
            if (! file_exists($this->tempFilePath)) {
                Log::error("Temp file not found for attachment ID {$this->attachment->getKey()}");

                return;
            }

            $extension = pathinfo($this->tempFilePath, PATHINFO_EXTENSION);
            $filename = Str::uuid().'.'.$extension;

            $disk = config('attachments.attachments_disk', 'public');
            $directory = sprintf(
                'attachments/%s/%s/',
                $this->attachment->attachable_type,
                $this->attachment->attachable_id,
            );

            Storage::disk($disk)->putFileAs($directory, $this->tempFilePath, $filename);

            $storagePath = $directory.$filename;

            $this->attachment->update([
                'file_path' => $storagePath,
                'size' => filesize($this->tempFilePath),
            ]);

            @unlink($this->tempFilePath);

            Log::info("Processed attachment ID {$this->attachment->getKey()} stored at {$storagePath}");
        } catch (Throwable $e) {
            Log::error("Failed to process attachment ID {$this->attachment->getKey()}: {$e->getMessage()}");

            throw $e;
        }
    }
}
