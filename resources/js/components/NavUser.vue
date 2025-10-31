<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

type AuthUser = {
    name?: string | null;
    email?: string | null;
    avatar?: string | null;
};

const page = usePage();

const user = computed<AuthUser>(() => {
    const authUser = (page.props as any)?.auth?.user as AuthUser | undefined;
    return authUser ?? { name: 'Magic User', email: null };
});

const initials = computed(() => {
    const name = user.value.name ?? 'MU';
    return name
        .split(' ')
        .filter(Boolean)
        .map(part => part[0]?.toUpperCase())
        .join('')
        .slice(0, 2) || 'MU';
});
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <SidebarMenuButton as-child>
                <Link href="/profile" class="flex w-full items-center gap-3 px-2 py-2">
                    <Avatar>
                        <AvatarImage :src="user.avatar ?? undefined" />
                        <AvatarFallback>{{ initials }}</AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 text-left leading-tight">
                        <span class="text-sm font-medium">{{ user.name ?? 'Magic User' }}</span>
                        <span v-if="user.email" class="text-xs text-muted-foreground">{{ user.email }}</span>
                    </div>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
