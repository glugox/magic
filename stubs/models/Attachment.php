<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * Class Attachment
 *
 * Represents a stored file (image, document, etc.) attached to any model
 * via a polymorphic relation (`attachable`).
 *
 * Usage example:
 *   $post->images()->create(['path' => $file->store('attachments')]);
 */

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
    /**
     * @use HasFactory<AttachmentFactory>
     */
    //use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attachments';

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'file_path',
        'file_type',
        'attachable_id',
        'attachable_type',
        'meta',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array', // allows storing JSON metadata (alt text, dimensions, etc.)
    ];

    /**
     * Appended accessors to include in model's array/JSON form.
     */
    protected $appends = ['url', 'is_image'];

    /**
     * Morph relation: the model this file is attached to.
     *
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accessor: full URL for the file.
     *
     * @return Attribute
     */
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

    /**
     * Accessor: check if this attachment is an image.
     */
    protected function isImage(): Attribute
    {
        return Attribute::make(get: fn (): bool => str_starts_with($this->file_type, 'image/'));
    }
}
