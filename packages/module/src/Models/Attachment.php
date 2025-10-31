<?php

namespace Glugox\Module\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $file_path
 * @property string $file_type
 * @property int $attachable_id
 * @property string $attachable_type
 * @property array<string, mixed>|null $meta
 */
class Attachment extends Model
{
    protected $table = 'attachments';

    protected $fillable = [
        'file_path',
        'file_type',
        'attachable_id',
        'attachable_type',
        'meta',
        'order_index',
        'file_size',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected $appends = ['url', 'is_image'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function url(): Attribute
    {
        return Attribute::make(get: function () {
            $disk = Config::get('attachments.attachments_disk', 'public');

            if (Config::get('attachments.use_signed_urls', false)) {
                $expiration = now()->addMinutes(Config::get('attachments.signed_url_expiration_minutes', 60));

                return Storage::disk($disk)->temporaryUrl($this->file_path, $expiration);
            }

            return Storage::disk($disk)->url($this->file_path);
        });
    }

    protected function isImage(): Attribute
    {
        return Attribute::make(get: fn (): bool => str_starts_with((string) $this->file_type, 'image/'));
    }
}
