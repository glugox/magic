<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@glugox/module/types';

const props = withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <nav v-if="props.breadcrumbs.length" aria-label="Breadcrumb" class="mb-2">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            <li
                v-for="(breadcrumb, index) in props.breadcrumbs"
                :key="`${breadcrumb.title}-${index}`"
                class="flex items-center gap-2"
            >
                <component
                    :is="breadcrumb.href ? Link : 'span'"
                    :href="breadcrumb.href ?? undefined"
                    class="hover:text-foreground"
                >
                    {{ breadcrumb.title }}
                </component>
                <span v-if="index < props.breadcrumbs.length - 1" class="text-xs text-muted-foreground">/</span>
            </li>
        </ol>
    </nav>
</template>
