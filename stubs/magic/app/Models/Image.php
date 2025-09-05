<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ['path', 'alt_text', 'order_index'];

    public function imageable()
    {
        return $this->morphTo();
    }
}
