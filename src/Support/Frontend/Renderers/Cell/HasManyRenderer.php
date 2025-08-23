<?php

namespace Glugox\Magic\Support\Frontend\Renderers\Cell;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Frontend\Renderers\RendererResult;

class HasManyRenderer extends Renderer
{

    /**
     * Render the cell value.
     */
    public function render( Field $field, Entity $entity): RendererResult
    {
        $relation = $entity->getRelationByName($field->name);

        // Lets load related items count
        // For example: roles relation on User entity
        //  return $item->roles()->count();

        // Create instance of entity model
        /*$entityClass = $entity->getFullyQualifiedModelClass();
        $item = new $entityClass();
        $item->exists = true; // Mark as existing to avoid issues
        $item->id = 1; // Dummy ID, adjust as needed
        $count = $item->{$relation->getRelationName()}()->count();
        $formattedStr = "return '{$count}';";*/

        $formattedStr = "return 'HasMany...'";

        return new RendererResult(
            content: $formattedStr,
            type: $this->getType()
        );
    }

    /**
     * Get the type of the renderer.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'hasMany';
    }
}
