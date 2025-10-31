<?php

namespace Glugox\Module\Eloquent;

use Glugox\Module\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Config;

trait HasImages
{
    public function images(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'imageable')->orderBy('order_index');
    }

    public function firstImageUrl(): string
    {
        $image = $this->images()->first();
        if (! $image) {
            return Config::get('attachments.default_image_url', '/images/placeholders/default.png');
        }

        return $image->getUrl();
    }

    public function firstImage(): ?Attachment
    {
        return $this->images()->first();
    }
}
