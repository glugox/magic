<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Resource representation of an Attachment model.
 *
 * @property int $id
 * @property string $mime_type
 * @property int $size
 * @property string|null $path
 * @property string $attachable_type
 * @property int $attachable_id
 * @property int $order_index
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_type' => $this->mime_type,
            'size' => $this->size,
            'url' => $this->url(),
            'thumbnail_url' => $this->thumbnailUrl(),
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'order_index' => $this->order_index,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Full public URL for the file.
     */
    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    /**
     * Full public URL for the thumbnail, if exists.
     */
    public function thumbnailUrl(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $thumbnailPath = preg_replace('/(\.\w+)$/', '_thumb$1', $this->path);

        return Storage::disk('public')->exists($thumbnailPath)
            ? Storage::disk('public')->url($thumbnailPath)
            : $this->url();
    }
}
