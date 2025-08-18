<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Illuminate\Filesystem\Filesystem;

class VuePageBuilderService
{
    protected string $pagesPath;

    public function __construct(
        protected Filesystem $files,
        protected Config $config
    ) {
        $this->pagesPath = resource_path('js/pages');
    }

    /**
     * Build Vue pages for each entity in the config.
     */
    public function build()
    {
        foreach ($this->config->entities as $entity) {
            $this->generateIndexPage($entity);
            $this->generateFormPage($entity);
        }
    }

    /**
     * Generate the Index.vue page.
     */
    protected function generateIndexPage(Entity $entity)
    {

        $folderName = $entity->getFolderName();
        $path = "{$this->pagesPath}/{$folderName}/Index.vue";
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $template = $this->getIndexTemplate($entity);

        $this->files->put($path, $template);
    }

    /**
     * Generate the Create/Edit form page.
     */
    protected function generateFormPage(Entity $entity)
    {
        $entityName = $entity->getName();
        $folderName = $entity->getFolderName();
        $className = $entityName;

        $path = "{$this->pagesPath}/{$folderName}/Edit.vue";

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $columns = $entity->getFieldsJson();

        $template = $this->getFormTemplate($className, $columns);

        $this->files->put($path, $template);
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
import { type PaginationObject } from "@/types/magic";
import { Head } from '@inertiajs/vue3';
import { get{$entityName}Columns, get{$entityName}EntityMeta } from '@/helpers/{$folderName}_helper';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import ResourceTable from '@/components/ResourceTable.vue';
import {ColumnDef} from "@tanstack/vue-table";

interface Props {
    data: PaginationObject;
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
    protected function getFormTemplate(string $className, string $columns): string
    {
        return 'TODO';
    }
}
