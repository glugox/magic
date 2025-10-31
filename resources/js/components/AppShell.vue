<script setup lang="ts">
import { computed } from 'vue';

type Variant = 'default' | 'sidebar';

interface Props {
    variant?: Variant;
    class?: string | Record<string, boolean> | string[];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default' as Variant,
    class: '',
});

const classes = computed(() => [
    'min-h-screen w-full bg-background text-foreground',
    props.variant === 'sidebar' ? 'flex' : 'flex flex-col',
    props.class,
]);
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
