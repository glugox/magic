<script setup lang="ts">
import type {Entity, Field} from '@glugox/module/types/support'

defineProps<{ field: Field; entity: Entity; value?: number | null }>()
</script>

<template>
  <span>
    {{ value }}
  </span>
</template>
