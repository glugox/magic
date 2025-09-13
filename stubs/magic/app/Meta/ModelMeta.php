<?php

namespace App\Meta;

interface ModelMeta
{
    /**
     * Return all fields (must be implemented in concrete subclass)
     *
     * @return Field[]
     */
    public static function getFields(): array;

    /**
     * Return model relations (must be implemented)
     *
     * @return Relation[]
     */
    public static function getRelations(): array;
}
