<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Enums\CrudActionType;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;

#[ActionDescription(
    name: 'generate_vue_pages',
    description: 'Generates Vue.js pages for all entities defined in the given Config.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateVuePagesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * Context with config
     */
    protected BuildContext $context;

    /**
     * Path to the Vue pages directory
     */
    protected string $pagesPath;

    /**
     * Constructor
     */
    public function __construct(
        protected TsHelper $tsHelper
    ) {
        $this->pagesPath = resource_path('js/pages');
    }

    public function __invoke(BuildContext $buildContext): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        $this->context = $buildContext;
        foreach ($this->context->getConfig()->entities as $entity) {
            // List resource pages , displaying items in table
            $this->generateIndexPage($entity);

            // Create form page for edit
            $this->generateEditFormPage($entity);

            // Create form page for create (can be same as edit)
            $this->generateCreateFormPage($entity);

            // Create child pages for relations if any
            foreach ($entity->getRelationsWithValidEntity() as $relation) {
                $this->generateRelationPages($entity, $relation);
            }
        }

        return $buildContext;
    }

    /**
     * Generate the Index.vue page.
     */
    protected function generateIndexPage(Entity $entity)
    {
        $folderName = $entity->getFolderName();
        $path = "{$this->pagesPath}/{$folderName}/Index.vue";

        // Build the template for the index page
        $template = $this->getIndexTemplate($entity);

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));
        // Action call -- Use the GenerateFileAction to create the file
        app(GenerateFileAction::class)($path, $template);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate the Create/Edit form page.
     */
    protected function generateEditFormPage(Entity $entity)
    {
        $folderName = $entity->getFolderName();
        $path = "{$this->pagesPath}/{$folderName}/Edit.vue";

        // Build the template for the form page
        $template = $this->getEditFormTemplate($entity);

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));
        app(GenerateFileAction::class)($path, $template);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate the Create form page.
     */
    protected function generateCreateFormPage(Entity $entity)
    {
        $folderName = $entity->getFolderName();
        $path = "{$this->pagesPath}/{$folderName}/Create.vue";
        // For now, use the same template as Edit
        $template = $this->getCreateFormTemplate($entity);
        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));
        app(GenerateFileAction::class)($path, $template);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Generate pages for entity relations.
     */
    protected function generateRelationPages(Entity $entity, Relation $relation)
    {

        $folderName = $entity->getFolderName();
        $path = "{$this->pagesPath}/{$folderName}/{$relation->getRelationName()}/Index.vue";

        // Build the template for the relation index page
        // This template will also be used for create/edit forms if needed. Controller will pass showEditForm flag
        // to indicate if the form should be shown above the table.
        $template = $this->getRelationIndexTemplate($entity, $relation); // You might want a different template for relations

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));
        app(GenerateFileAction::class)($path, $template);
        $this->context->registerGeneratedFile($path);
    }

    /**
     * Get the Index.vue template content.
     */
    protected function getIndexTemplate(Entity $entity): string
    {
        return StubHelper::loadStub('vue/index.stub', [
            'entityName' => $entity->getName(),
            'pluralName' => $entity->getPluralName(),
            'href' => $entity->getHref(),
            'entityImports' => $this->tsHelper->writeEntityImports($entity),
            'supportImports' => $this->tsHelper->writeIndexPageSupportImports($entity),
            'selectFields' => StubHelper::getSelectFieldsString($entity),
            'tableFields' => StubHelper::getTableFieldsString($entity),
            'searchableFields' => StubHelper::getSearchableFieldsString($entity),
            'relations' => StubHelper::getRelationNamesString($entity),
        ]);
    }

    /**
     * Get the Relation Index.vue template content.
     */
    protected function getRelationIndexTemplate(Entity $entity, Relation $relation): string
    {
        return StubHelper::loadStub('vue/relation/index.stub', [
            'mainEntityName' => $entity->getName(),
            'relatedEntityName' => $relation->getRelatedEntity()->getName(),
            'relatedEntityPluralName' => $relation->getRelatedEntity()->getPluralName(),
            'entitySingularName' => $entity->getSingularName(),
            'mainEntityNamePlural' => $entity->getPluralName(),
            'relationName' => $relation->getRelationName(),
            'href' => $relation->getRelatedEntity()->getHref(),
            'relationType' => $relation->getType()->value,
            'foreignKey' => $relation->getForeignKey(),
            'folderName' => $entity->getFolderName(),
            'controllerClass' => $entity->name.$relation->getRelatedEntity()->getName().'Controller',
            'mainEntityImports' => $this->tsHelper->writeEntityImports($entity, options: ['controller' => false]),
            'relatedEntityImports' => $this->tsHelper->writeEntityImports($relation->getRelatedEntity(), $entity),
            'supportImports' => $this->tsHelper->writeRelationIndexPageSupportImports($relation->getRelatedEntity(), $entity),
            'relationSidebarItems' => $this->tsHelper->writeRelationSidebarItems($entity, $this->context->getConfig()),
        ]);
    }

    /**
     * Get the Form.vue template content.
     */
    protected function getEditFormTemplate(Entity $entity): string
    {
        return StubHelper::loadStub('vue/edit.stub', [
            'entityName' => $entity->getName(),
            'title' => $entity->getPluralName(),
            'folderName' => $entity->getRouteName(),
            'entityImports' => $this->tsHelper->writeEntityImports($entity),
            'supportImports' => $this->tsHelper->writeFormPageSupportImports($entity),
            'relationSidebarItems' => $this->tsHelper->writeRelationSidebarItems($entity, $this->context->getConfig()),
        ]);
    }

    /**
     * Get the Create.vue template content.
     */
    protected function getCreateFormTemplate(Entity $entity): string
    {
        // Eg. User
        $entityName = $entity->getName();
        // Eg. Users
        $title = $entity->getPluralName();
        // Eg. users
        $folderName = $entity->getFolderName();
        $entityImports = $this->tsHelper->writeEntityImports($entity);
        $supportImports = $this->tsHelper->writeFormPageSupportImports($entity);
        $relationSidebarItems = $this->tsHelper->writeRelationSidebarItems($entity, $this->context->getConfig(), CrudActionType::CREATE);

        return <<<PHP
<script setup lang="ts">
import { create } from '@/routes/{$folderName}';
import { Head, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ResourceLayout from '@/layouts/resource/Layout.vue';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { SquareMinus, Link, CornerDownRight, FolderTree, GitCompareArrows } from 'lucide-vue-next';
import ResourceForm from '@/components/ResourceForm.vue';
$entityImports
$supportImports

interface Props {

}
defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: '{$title}',
        href: '#',
        icon: SquareMinus
    },
];

const sidebarNavItems: NavItem[] = [];
const page = usePage();
const entity = get{$entityName}EntityMeta();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="{$entityName}" />
        <ResourceLayout title="New {$entityName}" description="$entityName" :sidebar-nav-items="sidebarNavItems">
            <div class="flex flex-col space-y-6 max-w-2xl">
                <HeadingSmall title="{$entity->name} information" description="Fill {$entity->name} details" />
                <ResourceForm
                    :entity="entity"
                    :controller="{$entity->name}Controller"
                    />
            </div>
        </ResourceLayout>
    </AppLayout>
</template>
PHP;
    }
}
