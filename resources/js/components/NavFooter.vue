<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { NavItem } from '@glugox/module/types';

const props = withDefaults(defineProps<{ items?: NavItem[] }>(), {
    items: () => [],
});
</script>

<template>
    <SidebarMenu v-if="props.items.length">
        <SidebarMenuItem v-for="item in props.items" :key="item.title">
            <SidebarMenuButton as-child>
                <Link :href="item.href" class="flex w-full items-center gap-2">
                    <component v-if="item.icon" :is="item.icon" class="size-4" />
                    <span class="text-sm font-medium">{{ item.title }}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
