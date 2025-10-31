<script setup lang="ts">
interface Props {
    message?: string | null;
    className?: string | string[] | Record<string, boolean>;
}

const props = defineProps<Props>();
</script>

<template>
    <p v-if="props.message" :class="['text-sm text-destructive', props.className]">
        {{ props.message }}
    </p>
</template>
