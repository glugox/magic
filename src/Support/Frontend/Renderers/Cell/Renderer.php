<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;
use Illuminate\Support\Facades\Log;

class Renderer
{
    /*
     * Array to contain the cell renderers.
     */
    protected static array $cellRenderers = [

        // Date renderers
        FieldType::DATE->value => DateRenderer::class,
        FieldType::DATETIME->value => DateRenderer::class,
        FieldType::TIME->value => DateRenderer::class,
        FieldType::TIMESTAMP->value => DateRenderer::class,
        FieldType::YEAR->value => DateRenderer::class,

        // Text (long)
        FieldType::TEXT->value => TextRenderer::class,

        // Number types
        FieldType::INTEGER->value => NumberRenderer::class,
        FieldType::BIG_INTEGER->value => NumberRenderer::class,
        FieldType::BIG_INCREMENTS->value => NumberRenderer::class,
        FieldType::SMALL_INTEGER->value => NumberRenderer::class,
        FieldType::TINY_INTEGER->value => NumberRenderer::class,
        FieldType::UNSIGNED_INTEGER->value => NumberRenderer::class,
        FieldType::UNSIGNED_BIG_INTEGER->value => NumberRenderer::class,
        FieldType::UNSIGNED_SMALL_INTEGER->value => NumberRenderer::class,
        FieldType::UNSIGNED_TINY_INTEGER->value => NumberRenderer::class,
        FieldType::DECIMAL->value => NumberRenderer::class,
        FieldType::FLOAT->value => NumberRenderer::class,
        FieldType::DOUBLE->value => NumberRenderer::class,

        // Boolean types
        FieldType::BOOLEAN->value => BooleanRenderer::class,

        // Image types
        FieldType::IMAGE->value => ImageRenderer::class,

        // Password and sensitive types
        FieldType::PASSWORD->value => PasswordRenderer::class,
        FieldType::FILE->value => PasswordRenderer::class,
        FieldType::SECRET->value => PasswordRenderer::class,
        FieldType::TOKEN->value => PasswordRenderer::class,

        // Url types
        FieldType::URL->value => UrlRenderer::class,
    ];

    /**
     * Array to contain the cell renderers by field name. Example: Field name = 'title'
     */
    protected static array $cellRenderersByFieldName = [
        'title' => NameRenderer::class,
        'name' => NameRenderer::class,
    ];

    /*
     * Returns a cell renderer by field type.
     */
    public static function getRenderer(Field $field, ?Entity $entity = null): ?self
    {
        Log::channel('magic')->info('Getting renderer for field: '.$field->name.' of type: '.$field->type->value);

        // Check explicitly if the field is a name field
        if ($field->isMain()) {
            Log::channel('magic')->info('Field '.$field->name.' is identified as a name field. Using NameRenderer.');

            return new NameRenderer;
        }

        // Check if the field belongs to another entity
        $belongsTo = $field->belongsTo();
        // If the field belongs to another entity, we can use a specific renderer for that entity
        if ($belongsTo) {
            Log::channel('magic')->info('Field '.$field->name.' belongs to entity: '.$belongsTo->getRelatedEntityName().'. Using BelongsToRenderer.');

            return new BelongsToRenderer;
        }

        // Relation types
        if ($field->type === FieldType::HAS_MANY || $field->type === FieldType::BELONGS_TO_MANY) {
            Log::channel('magic')->info('Field '.$field->name.' is a relation of type: '.$field->type->value.'. Using HasManyRenderer.');

            return new HasManyRenderer;
        }

        // Check if the field name has a specific renderer
        if (isset(self::$cellRenderersByFieldName[$field->name])) {
            Log::channel('magic')->info('Found specific renderer for field name: '.$field->name.'. Using '.self::$cellRenderersByFieldName[$field->name].'.');

            return new self::$cellRenderersByFieldName[$field->name];
        }

        // Check if the field type has a specific renderer
        if (isset(self::$cellRenderers[$field->type->value])) {
            Log::channel('magic')->info('Found specific renderer for field type: '.$field->type->value.'. Using '.self::$cellRenderers[$field->type->value].'.');

            return new self::$cellRenderers[$field->type->value];
        }
        // If no specific renderer is found, return a default renderer
        Log::channel('magic')->info('Did not find a specific renderer for field type: '.$field->type->value.' or field name: '.$field->name.'. Using DefaultRenderer.');

        return new DefaultRenderer;
    }

    /**
     * Render the cell value.
     */
    public function render(Field $field, Entity $entity): RendererResult
    {
        return new RendererResult(
            content: (string) $field->name,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     */
    public function getType(): string
    {
        return 'simple';
    }
}
