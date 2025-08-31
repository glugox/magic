<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Frontend\TsHelper;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;

#[ActionDescription(
    name: 'generate_vue_pages',
    description: 'Generates Vue.js pages for all entities defined in the given Config.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateVuePagesAction implements DescribableAction
{
    use AsDescribableAction;

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
    ){
        $this->pagesPath = resource_path('js/pages');
    }

    public function __invoke(BuildContext $buildContext): BuildContext
    {
        $this->context = $buildContext;
        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateIndexPage($entity);
            $this->generateFormPage($entity);

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
    protected function generateFormPage(Entity $entity)
    {
        $folderName = $entity->getFolderName();
        $path = "{$this->pagesPath}/{$folderName}/Edit.vue";

        // Build the template for the form page
        $template = $this->getFormTemplate($entity);

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
        $entityName = $entity->getName();
        $title = $entity->getSingularName();
        $folderName = $entity->getFolderName();
        $href = $entity->getHref();
        $columnsJson = $entity->getFieldsJson();
        $entityImports = $this->tsHelper->writeEntityImports($entity);
        $supportImports = $this->tsHelper->writeIndexPageSupportImports($entity);

        return <<<PHP
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import ResourceTable from '@/components/ResourceTable.vue';
import {ColumnDef} from "@tanstack/vue-table";
$entityImports
$supportImports

interface Props {
    data: PaginationObject;
    filters?: TableFilters;
}

const { data }: Props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '{$title}',
        href: '{$href}',
    },
];

const columns: ColumnDef<{$entityName}>[] = get{$entityName}Columns();
const entityMeta = get{$entityName}EntityMeta();

</script>

<template>
    <Head title="{$title}" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!--<div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>-->
            <div class="relative p-4 min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <ResourceTable
                    :data="data"
                    :columns="columns"
                    :entity-meta="entityMeta"
                    :filters="filters"
                    :controller="{$entity->name}Controller"
                    />
            </div>
        </div>
    </AppLayout>
</template>

PHP;
    }

    /**
     * Get the Relation Index.vue template content.
     */
    protected function getRelationIndexTemplate(Entity $entity, Relation $relation): string
    {
        // Eg. User projects relation, where User is the main entity
        $mainEntityName = $entity->getName();
        // eg. Project
        $relatedEntity = $relation->getRelatedEntity();
        // Eg. Project
        $relatedEntityName = $relatedEntity->getName();
        // Eg. Projects
        $title = $relatedEntity->getSingularName();
        // Eg. projects
        $relationName = $relation->getRelationName();
        // Eg. /projects
        $href = $relatedEntity->getHref();
        $columnsJson = $relatedEntity->getFieldsJson();
        $mainEntityImports = $this->tsHelper->writeEntityImports($entity);
        $relatedEntityImports = $this->tsHelper->writeEntityImports($relatedEntity);
        $supportImports = $this->tsHelper->writeIndexPageSupportImports($relatedEntity);
        $relationSidebarItems = $this->tsHelper->writeRelationSidebarItems($relatedEntity, $this->context->getConfig());
        $folderName = $relatedEntity->getFolderName();

        return <<<PHP
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ResourceLayout from '@/layouts/resource/Layout.vue';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { edit, show } from '@/routes/{$folderName}';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import ResourceTable from '@/components/ResourceTable.vue';
import {ColumnDef} from "@tanstack/vue-table";
import HeadingSmall from '@/components/HeadingSmall.vue';
$mainEntityImports
$relatedEntityImports
$supportImports

interface Props {
    item: {$mainEntityName};
    $relationName: PaginationObject;
    filters?: TableFilters;
}

const { item, $relationName }: Props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '{$title}',
        href: '{$href}',
    },
];

const columns: ColumnDef<{$relatedEntityName}>[] = get{$relatedEntityName}Columns();
const entityMeta = get{$relatedEntityName}EntityMeta();
const sidebarNavItems: NavItem[] = [
    {
        title: 'General Information',
        href: edit(item.id),
    },
    $relationSidebarItems
];

</script>

<template>
    <Head title="{$title}" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="User" />

        <ResourceLayout :sidebar-nav-items="sidebarNavItems">
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="User information" description="Update User details" />

                <ResourceTable
                    :data="$relationName"
                    :columns="columns"
                    :entity-meta="entityMeta"
                    :filters="filters"
                    :controller="{$relatedEntity->name}Controller"
                    />
            </div>
        </ResourceLayout>
    </AppLayout>
</template>

PHP;
    }

    /**
     * Get the Form.vue template content.
     */
    protected function getFormTemplate(Entity $entity): string
    {
        $entityName = $entity->getName();
        $title = $entity->getPluralName();
        $folderName = $entity->getFolderName();
        $href = $entity->getHref();
        $columnsJson = $entity->getFieldsJson();
        $entityImports = $this->tsHelper->writeEntityImports($entity);
        $supportImports = $this->tsHelper->writeFormPageSupportImports($entity);
        $relationSidebarItems = $this->tsHelper->writeRelationSidebarItems($entity, $this->context->getConfig());


        return <<<PHP
<script setup lang="ts">
import { edit, show } from '@/routes/{$folderName}';
import { Head, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ResourceLayout from '@/layouts/resource/Layout.vue';
import { type BreadcrumbItem, type NavItem } from '@/types';

import ResourceForm from '@/components/ResourceForm.vue';
$entityImports
$supportImports

interface Props {
    item: {$entityName};
}
const { item }: Props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: '{$title}',
        href: edit(item.id).url,
    },
];

const entityBaseRoute = edit(item.id);
const sidebarNavItems: NavItem[] = [
    {
        title: 'General Information',
        href: edit(item.id),
    },
    $relationSidebarItems
];

const page = usePage();
const entityMeta = get{$entityName}EntityMeta();

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="{$entityName}" />

        <ResourceLayout :sidebar-nav-items="sidebarNavItems">
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="{$entity->name} information" description="Update {$entity->name} details" />

                <ResourceForm
                    :entityMeta="entityMeta"
                    :item="item"
                    :controller="{$entity->name}Controller"
                    />
            </div>
        </ResourceLayout>
    </AppLayout>
</template>

PHP;

    }
}
