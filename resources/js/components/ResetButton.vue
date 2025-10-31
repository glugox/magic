<template>
    <button
        type="button"
        class="text-muted-foreground hover:text-foreground"
        @click="$emit('click')"
        v-bind="$attrs"
    >
        ✕
    </button>
</template>

<script setup lang="ts">
defineEmits<{ (e: "click"): void }>()
</script>
