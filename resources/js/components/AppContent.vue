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
    'flex-1',
    props.variant === 'sidebar' ? 'bg-muted/20' : '',
    props.class,
]);
</script>

<template>
    <main :class="classes">
        <slot />
    </main>
</template>
