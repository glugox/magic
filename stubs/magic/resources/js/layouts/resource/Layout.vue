<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import 'vue-sonner/style.css'

interface Props {
    title: string;
    description?: string;
    sidebarNavItems: NavItem[];
}
const { sidebarNavItems }: Props = defineProps<Props>();

const currentPath = typeof window !== undefined ? window.location.pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading :title="title" :description="description" />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside v-if="sidebarNavItems?.length" class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="typeof item.href === 'string' ? item.href : item.href?.url"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': currentPath === (typeof item.href === 'string' ? item.href : item.href?.url) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 w-full">
                <section class="w-full space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
