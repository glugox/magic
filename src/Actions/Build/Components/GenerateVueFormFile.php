<?php

namespace Glugox\Magic\Actions\Build\Components;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\File\VueFile;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Traits\AsDescribableAction;

#[ActionDescription(
    name: 'generate_vue_form_file',
    description: 'Generates a VueFile PHP class that has string representation of full content for a vue component, from a FormSchema object',
    parameters: ['entity' => 'The entity configuration object (Entity instance) to generate the form for']
)]
class GenerateVueFormFile implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(Entity $entity): VueFile
    {
        $script = $this->generateScript($entity);
        $template = $this->generateTemplate($entity);

        return VueFile::fromArray([
            'fileName' => $entity->getName().'Form.vue',
            'directory' => 'components/forms',
            'script' => $script,
            'template' => $template,
            'style' => null,
        ]);
    }

    /**
     * Generates the script section of the Vue component.
     */
    protected function generateScript(Entity $entity): string
    {
        return <<<TS
import { ref } from "vue"

const props = defineProps<{
    item: Record<string, any>
}>()

const form = ref({
    {$this->buildDefaults($entity)}
})

function submit() {
  // TODO: implement form submit (Inertia.post/put)
}
TS;
    }

    /**
     * Generates the template section of the Vue component.
     */
    protected function generateTemplate(Entity $entity): string
    {
        $fields = array_map(
            fn (Field $field) => $this->renderField($field),
            $entity->getFormFields()
        );

        $fieldsMarkup = implode("\n", $fields);

        return <<<HTML
<form @submit.prevent="submit">
    {$fieldsMarkup}
    <button type="submit">Save</button>
</form>
HTML;
    }

    /**
     * Renders a single field based on its type.
     */
    protected function renderField(Field $field): string
    {
        return match ($field->type) {
            FieldType::STRING => "<input v-model=\"form.{$field->name}\" type=\"text\" placeholder=\"{$field->name}\" />",
            FieldType::INTEGER => "<input v-model=\"form.{$field->name}\" type=\"number\" placeholder=\"{$field->name}\" />",
            FieldType::BOOLEAN => "<input v-model=\"form.{$field->name}\" type=\"checkbox\" />",
            FieldType::EMAIL => "<input v-model=\"form.{$field->name}\" type=\"email\" placeholder=\"{$field->name}\" />",
            default => "<input v-model=\"form.{$field->name}\" />",
        };
    }

    /**
     * Builds default values for the form fields.
     */
    private function buildDefaults(Entity $entity): string
    {
        return collect($entity->getFormFields())
            ->map(fn (Field $f) => "{$f->name}: ".TsHelper::writeValue($f->default))
            ->join(",\n    ");
    }
}
