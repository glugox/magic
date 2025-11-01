<?php

namespace Glugox\ModelMeta\Builder;

use Glugox\ModelMeta\Fields\Boolean;
use Glugox\ModelMeta\Fields\Date;
use Glugox\ModelMeta\Fields\DateTime;
use Glugox\ModelMeta\Fields\Email;
use Glugox\ModelMeta\Fields\Enum;
use Glugox\ModelMeta\Fields\File;
use Glugox\ModelMeta\Fields\Id;
use Glugox\ModelMeta\Fields\Image;
use Glugox\ModelMeta\Fields\Number;
use Glugox\ModelMeta\Fields\Password;
use Glugox\ModelMeta\Fields\Slug;
use Glugox\ModelMeta\Fields\Text;
use Glugox\ModelMeta\FieldType;
use Illuminate\Support\Str;

class ModelMetaBuilder
{
    protected array $config;

    // Store uses
    protected array $uses = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Generate PHP code for the resource meta class.
     */
    public function generate(): string
    {
        $className = $this->config['name'] . 'Meta';
        $namespace = "Dummies";

        $this->uses = [];
        $fieldsCode = $this->generateFieldsCode($this->config['fields'] ?? []);

        $uses = implode(",\n    ", array_keys($this->uses));

        return <<<PHP
<?php

namespace $namespace;

use Glugox\ModelMeta\Field;
use Glugox\ModelMeta\ModelMeta;
use Glugox\ModelMeta\Fields\\{
    $uses
};

class $className extends ModelMeta
{
    /**
     * Define fields for the {$this->config['name']} resource.
     *
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
$fieldsCode
        ];
    }
}
PHP;
    }

    /**
     * Generate code for fields.
     */
    protected function generateFieldsCode(array $fields): string
    {
        $lines = [];

        /**
         * Map FieldType enum values to actual Field class names
         * Add other Field classes as needed.
         */
        $typeClassMap = [
            FieldType::ID->value => Id::class,
            FieldType::STRING->value => Text::class,
            FieldType::EMAIL->value => Email::class,
            FieldType::PASSWORD->value => Password::class,
            FieldType::ENUM->value => Enum::class,
            FieldType::DECIMAL->value => Number::class,
            FieldType::FLOAT->value => Number::class,
            FieldType::NUMBER->value => Number::class,
            FieldType::FILE->value => File::class,
            FieldType::IMAGE->value => Image::class,
            FieldType::SLUG->value => Slug::class,
            FieldType::BOOLEAN->value => Boolean::class,
            FieldType::DATETIME->value => DateTime::class,
            FieldType::DATE->value => Date::class,
            FieldType::SLUG->value => Slug::class,

        ];

        $setterMap = [
            'required' => 'required',
            'nullable' => 'nullable',
            'unique' => 'unique',
            'sortable' => 'sortable',
            'searchable' => 'searchable',
            'hidden' => 'hidden',
            'default' => 'default',
            'maxLength' => 'maxLength',
            'min' => 'min',
            'max' => 'max',
            'step' => 'step',
        ];

        foreach ($this->config['fields'] as $field) {
            $type = $field['type'];
            $class = $typeClassMap[$type] ?? Text::class;

            // Base field creation
            $code = $this->buildFieldCode($field, class_basename($class));
            $this->uses[$class] = 1;

            // Apply fluent setters dynamically
            // E.g., ->required()->sortable()->default('value')
            foreach ($setterMap as $jsonKey => $setterMethod) {
                if (!empty($field[$jsonKey])) {
                    if (in_array($setterMethod, ['default', 'maxLength', 'min', 'max', 'step'])) {
                        $value = is_array($field[$jsonKey]) ? var_export($field[$jsonKey], true) : $field[$jsonKey];
                        $code .= "->{$setterMethod}({$value})";
                    } else {
                        $code .= "->{$setterMethod}()";
                    }
                }
            }

            $lines[] = $code;
        }

        return "            " . implode("\n            ", $lines);
    }

    /**
     * Build the initial field creation code based on type.
     * E.g., Id::make(), Text::make('name'), Enum::make('status', ['active', 'inactive'])
     */
    protected function buildFieldCode(array $field, string $class): string
    {
        $name = $field['name'];

        switch ($field['type']) {
            case FieldType::ENUM->value:
                $values = !empty($field['values']) ? var_export($field['values'], true) : '[]';
                return "{$class}::make('{$name}', {$values})";
            case FieldType::ID->value:
                return "{$class}::make()";
        }

        return "{$class}::make('{$name}')";
    }
}
