<?php

namespace App\Meta;
enum RelationType: string
{
    // Standard relations
    case HAS_ONE = 'hasOne';
    case BELONGS_TO = 'belongsTo';
    case HAS_MANY = 'hasMany';
    case BELONGS_TO_MANY = 'belongsToMany';

    // -- Polymorphic relations -- //

    /**
     * One-to-one polymorphic relation.
     * https://laravel.com/docs/12.x/eloquent-relationships#one-to-one-polymorphic-relations
     * Example:
     * class User extends Model {
     *     public function image() {
     *         return $this->morphOne(Image::class, 'imageable');
     *     }
     * }
     * class Image extends Model {
     *     public function imageable() {
     *         return $this->morphTo(); // inverse
     *     }
     * }
     */
    case MORPH_ONE = 'morphOne';

    /**
     * One-to-many polymorphic relation.
     * https://laravel.com/docs/12.x/eloquent-relationships#one-to-many-polymorphic-relations
     * Example:
     * class Post extends Model {
     *     public function comments() {
     *         return $this->morphMany(Comment::class, 'commentable');
     *     }
     * }
     * class Comment extends Model {
     *     public function commentable() {
     *         return $this->morphTo(); // inverse
     *     }
     * }
     */
    case MORPH_MANY = 'morphMany';

    /**
     * Inverse of a polymorphic relation (MORPH_ONE and MORPH_MANY, belongs to a polymorphic parent).
     * Example:
     * class Comment extends Model {
     *     public function commentable() {
     *         return $this->morphTo(); // can point to Post, Video, etc.
     *     }
     * }
     */
    case MORPH_TO = 'morphTo';

    /**
     * Many-to-many polymorphic relation.
     * https://laravel.com/docs/12.x/eloquent-relationships#many-to-many-polymorphic-relations
     * Example:
     * class Post extends Model {
     *     public function tags() {
     *         return $this->morphToMany(Tag::class, 'taggable');
     *     }
     * }
     * class Tag extends Model {
     *     public function posts() {
     *         return $this->morphedByMany(Post::class, 'taggable'); // inverse
     *     }
     * }
     */
    case MORPH_TO_MANY = 'morphToMany';

    /**
     * Inverse of a many-to-many polymorphic relation.
     * Example:
     * class Tag extends Model {
     *     public function posts() {
     *         return $this->morphedByMany(Post::class, 'taggable');
     *     }
     * }
     * class Post extends Model {
     *     public function tags() {
     *         return $this->morphToMany(Tag::class, 'taggable'); // inverse
     *     }
     * }
     */
    case MORPHED_BY_MANY = 'morphedByMany';

}
