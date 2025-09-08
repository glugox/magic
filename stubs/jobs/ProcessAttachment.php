<?php

namespace App\Jobs;

use App\Models\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Throwable;

class ProcessAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Attachment $attachment;

    protected string $tempFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct(Attachment $attachment, string $tempFilePath)
    {
        $this->attachment = $attachment;
        $this->tempFilePath = $tempFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (! file_exists($this->tempFilePath)) {
                Log::error("Temp file not found for attachment ID {$this->attachment->id}");

                return;
            }

            $extension = pathinfo($this->tempFilePath, PATHINFO_EXTENSION);
            $filename = Str::uuid()->toString().'.'.$extension;

            // Permanent storage path
            $storagePath = "attachments/{$this->attachment->attachable_type}/{$this->attachment->attachable_id}/{$filename}";

            // Move file to public storage
            Storage::disk('public')->putFileAs(
                "attachments/{$this->attachment->attachable_type}/{$this->attachment->attachable_id}/",
                $this->tempFilePath,
                $filename
            );

            // Optional: generate thumbnail for images
            /*if (str_starts_with(mime_content_type($this->tempFilePath), 'image/')) {
                $this->generateThumbnail($storagePath);
            }*/

            // Update attachment record
            $this->attachment->update([
                'file_path' => $storagePath,
                'size' => filesize($this->tempFilePath),
            ]);

            // Clean up temp file
            @unlink($this->tempFilePath);

            Log::info("Processed attachment ID {$this->attachment->id} stored at {$storagePath}");
        } catch (Throwable $e) {
            Log::error("Failed to process attachment ID {$this->attachment->id}: {$e->getMessage()}");
            throw $e; // Optionally retry the job
        }
    }

    /*protected function generateThumbnail(string $storagePath): void
    {
        $thumbnailPath = str_replace('.', '_thumb.', $storagePath);

        $image = Image::make(Storage::disk('public')->path($storagePath))
            ->resize(300, 300, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

        $image->save(Storage::disk('public')->path($thumbnailPath));
    }*/
}
