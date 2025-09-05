<?php

namespace DummyNamespace\Models\Concerns;

use App\Models\Image;

trait HasImages
{
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function firstImage()
    {
        return $this->images()->orderBy('order_index')->first();
    }
}
