<?php

namespace Modules\Crm\Meta\Models;

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\Filter;
use Glugox\ModelMeta\ModelMeta;
use Glugox\ModelMeta\Relation;

class CustomerMeta extends ModelMeta
{
    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * @return array<int, Relation>
     */
    public function relations(): array
    {
        return [];
    }

    /**
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [];
    }

    public function tableName(): string
    {
        return 'customers';
    }
}
