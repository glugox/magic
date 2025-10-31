<script setup lang="ts">
import AppSidebarLayout from './app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@glugox/module/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>
