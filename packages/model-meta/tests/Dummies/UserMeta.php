<?php

namespace Dummies;

use Glugox\ModelMeta\Contracts\Filter;
use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\Filters\BooleanFilter;
use Glugox\ModelMeta\Filters\TextFilter;
use Glugox\ModelMeta\ModelMeta;
use Glugox\ModelMeta\Fields\{
    Id,
    Text,
    Email,
    Number,
    Slug,
    Image
};

class UserMeta extends ModelMeta
{
    /**
     * Define fields for the User resource.
     *
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('first_name')->max(50)->required(),
            Text::make('last_name')->max(50)->required(),
            Email::make('email')->required()->unique(),
            Number::make('age')->min(0)->max(120)->default(18),
            Slug::make('slug'),
            Image::make('avatar')->nullable(),
        ];
    }

    public function tableName(): string
    {
        return 'users';
    }

    public function relations(): array
    {
        return [];
    }

    /**
     * Define filters for the UserMeta resource.
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('email'),
            BooleanFilter::make('active'),
        ];
    }
}