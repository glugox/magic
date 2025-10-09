<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\Config\Builder\RelationBuilder;
use Illuminate\Support\Str;

class Filter
{
    public FilterType $type;

    /**
     * Constructor to initialize the Filter object.
     *
     * @param  FilterType|string  $type  The type of filter (can be a FilterType enum or string).
     * @param  string  $field  The field name associated with the filter.
     * @param  array{string, mixed}  $initialValues  Initial values for the filter.
     */
    public function __construct(
        FilterType|string $type,
        public string $field,
        public array $initialValues,
        public bool $dynamic,
        public ?string $label = null,
    ) {
        $this->type = $type instanceof FilterType ? $type : FilterType::from($type);
        $this->label = $label ?? Str::title(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Static factory method to create a Relation instance.
     */
    public static function make(RelationType $type, Entity $localEntity): RelationBuilder
    {
        return new RelationBuilder()
            ->type($type)
            ->localEntity($localEntity);
    }

    /**
     * Json representation of the relation.
     */
    public function toJson(): string
    {
        $data = [
            'type' => $this->type->value,
        ];
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return $json === false ? '{}' : $json;
    }

    /**
     * String representation of the relation.
     */
    public function toString(): string
    {
        /**
         * User → hasMany(projects) → Project [ FK: user_id, LK: id ]
         */
        return Str::of($this->toJson())->replace("\n", ' ')->replace('  ', ' ')->__toString();
    }
}
