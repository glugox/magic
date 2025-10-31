<script setup lang="ts">
import { Button } from "@/components/ui/button"

const {createUrl, label} = defineProps<{
    createUrl?: string
    label?: string
}>()
</script>

<template>
    <Button
        v-if="createUrl"
        variant="default"
        size="sm"
        :href="createUrl"
        as="a"
        class="ml-auto"
    >
        {{label}}
    </Button>
</template>
