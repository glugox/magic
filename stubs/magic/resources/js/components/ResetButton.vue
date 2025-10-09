<template>
    <button
        type="button"
        class="absolute -top-1 right-0 text-muted-foreground hover:text-foreground"
        @click="$emit('click')"
    >
        ✕
    </button>
</template>

<script setup lang="ts">
defineEmits<{ (e: "click"): void }>()
</script>
