<script setup lang="ts">
import AppContent from '@glugox/module/components/AppContent.vue';
import AppShell from '@glugox/module/components/AppShell.vue';
import AppSidebar from '@glugox/module/components/AppSidebar.vue';
import AppSidebarHeader from '@glugox/module/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@glugox/module/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="px-5 pb-5">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
