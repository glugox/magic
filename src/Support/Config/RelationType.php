<?php

namespace Glugox\Magic\Support\Config;

enum RelationType: string
{
    // Standard relations

    /**
     * One-to-one relation.
     * https://laravel.com/docs/12.x/eloquent-relationships#one-to-one
     * Example:
     * class User extends Model {
     *     public function phone() {
     *         return $this->hasOne(Phone::class);
     *     }
     * }
     * class Phone extends Model {
     *     public function user() {
     *         return $this->belongsTo(User::class); // inverse
     *     }
     * }
     * Database table is 'phones' with columns:
     * - id
     * - number
     * - user_id (foreign key to users table)
     * - created_at
     * - updated_at
     * -- Note: The foreign key is on the related model's table (phones) --
     * -- The hasOne side (User) does not have the foreign key --
     */
    case HAS_ONE = 'hasOne';

    /**
     * Belongs to / One-to-many relation.
     * https://laravel.com/docs/12.x/eloquent-relationships#one-to-many
     * Example:
     * class Post extends Model {
     *     public function comments() {
     *         return $this->hasMany(Comment::class);
     *     }
     * }
     * class Comment extends Model {
     *     public function post() {
     *         return $this->belongsTo(Post::class); // inverse
     *     }
     * }
     *
     * Database table is 'comments' with columns:
     * - id
     * - content
     * - post_id (foreign key to posts table)
     * - created_at
     * - updated_at
     * -- Note: The foreign key is on the related model's table (comments) --
     * -- The hasMany side (Post) does not have the foreign key --
     */
    case BELONGS_TO = 'belongsTo';
    case HAS_MANY = 'hasMany';

    /**
     * Many-to-many relation.
     * https://laravel.com/docs/12.x/eloquent-relationships#many-to-many
     * Example:
     * class User extends Model {
     *     public function roles() {
     *         return $this->belongsToMany(Role::class);
     *     }
     * }
     * class Role extends Model {
     *     public function users() {
     *         return $this->belongsToMany(User::class); // inverse
     *     }
     * }
     *
     * Database tables:
     * - users (id, name, email, created_at, updated_at)
     * - roles (id, name, created_at, updated_at)
     * - role_user (user_id, role_id) // pivot table
     * -- Note: The pivot table (role_user) holds the foreign keys --
     * -- Neither side (User or Role) has the foreign key directly --
     */
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
     *
     * Database table is 'images' with columns:
     * - id
     * - url
     * - imageable_id (the ID of the related model, e.g., User ID)
     * - imageable_type (the class name of the related model, e.g., 'App\Models\User')
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
     *
     * Database table is 'comments' with columns:
     * - id
     * - content
     * - commentable_id (the ID of the related model, e.g., Post ID)
     * - commentable_type (the class name of the related model, e.g., 'App\Models\Post')
     * - created_at
     * - updated_at
     * -- Note: The foreign key and type are on the related model's table (comments) --
     * -- The morphMany side (Post) does not have the foreign key --
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
     *
     * Database table is 'comments' with columns:
     * - id
     * - content
     * - commentable_id (the ID of the related model, e.g., Post ID)
     * - commentable_type (the class name of the related model, e.g., 'App\Models\Post')
     * - created_at
     * - updated_at
     * -- Note: The foreign key and type are on this model's table (comments) --
     * -- This side (Comment) does not know the target model ahead of time --
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
     * Database tables:
     * - posts (id, title, content, created_at, updated_at)
     * - tags (id, name, created_at, updated_at)
     * - taggables (tag_id, taggable_id, taggable_type) // pivot table
     * -- Note: The pivot table (taggables) holds the foreign keys and type --
     * -- Neither side (Post or Tag) has the foreign key directly --
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
     *
     * Database tables:
     * - posts (id, title, content, created_at, updated_at)
     * - tags (id, name, created_at, updated_at)
     * - taggables (tag_id, taggable_id, taggable_type) // pivot table
     * -- Note: The pivot table (taggables) holds the foreign keys and type --
     * -- Neither side (Post or Tag) has the foreign key directly --
     */
    case MORPHED_BY_MANY = 'morphedByMany';

}
