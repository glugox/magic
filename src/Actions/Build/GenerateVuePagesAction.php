<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
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
    public function __construct()
    {
        $this->pagesPath = resource_path('js/pages');
    }

    public function __invoke(BuildContext $buildContext): BuildContext
    {
        $this->context = $buildContext;
        foreach ($this->context->getConfig()->entities as $entity) {
            $this->generateIndexPage($entity);
            $this->generateFormPage($entity);
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

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        $template = $this->getIndexTemplate($entity);

        // Action call -- Use the GenerateFileAction to create the file
        app(GenerateFileAction::class)($path, $template);
    }

    /**
     * Generate the Create/Edit form page.
     */
    protected function generateFormPage(Entity $entity)
    {
        $entityName = $entity->getName();
        $folderName = $entity->getFolderName();

        $path = "{$this->pagesPath}/{$folderName}/Edit.vue";

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        $template = $this->getFormTemplate($entity);

        app(GenerateFileAction::class)($path, $template);
    }

    /**
     * Get the Index.vue template content.
     */
    protected function getIndexTemplate(Entity $entity): string
    {
        $entityName = $entity->getName();
        $title = $entity->getPluralName();
        $folderName = $entity->getFolderName();
        $href = $entity->getHref();
        $columnsJson = $entity->getFieldsJson();

        return <<<PHP
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type {$entityName} } from "@/types/app";
import { type PaginationObject, type TableFilters } from "@/types/magic";
import { Head } from '@inertiajs/vue3';
import { get{$entityName}Columns, get{$entityName}EntityMeta } from '@/helpers/{$folderName}_helper';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import ResourceTable from '@/components/ResourceTable.vue';
import {ColumnDef} from "@tanstack/vue-table";

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
                    />
            </div>
        </div>
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

        return <<<PHP
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type User } from "@/types/app";
import { type PaginationObject, type TableFilters } from "@/types/magic";
import { Head } from '@inertiajs/vue3';
import { get{$entityName}Columns, get{$entityName}EntityMeta } from '@/helpers/{$folderName}_helper';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import {ColumnDef} from "@tanstack/vue-table";
import ResourceForm from '@/components/ResourceForm.vue';

interface Props {
    item?: Record<string, any>
}

const { item }: Props = defineProps<Props>();

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
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div class="relative p-4 min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <ResourceForm
                    :entityMeta="entityMeta"
                    :item="item"
                    />
            </div>
        </div>
    </AppLayout>
</template>

PHP;

    }
}
