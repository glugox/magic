<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Support\Facades\Config;

trait HasImages
{
    /**
     * MorphMany relation to attachments.
     */
    public function images()
    {
        return $this->morphMany(Attachment::class, 'imageable')->orderBy('order_index');
    }

    /**
     * Get the URL of the first image or a default placeholder if none exist.
     */
    public function firstImageUrl(): string
    {
        $image = $this->images()->first();
        if (! $image) {
            return Config::get('attachments.default_image_url', '/images/placeholders/default.png');
        }

        return $image->getUrl();
    }

    /**
     * Get the first image model.
     */
    public function firstImage()
    {
        return $this->images()->first();
    }
}
