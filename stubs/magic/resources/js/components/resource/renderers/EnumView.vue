<script setup lang="ts">
import type {Entity, Field} from '@/types/support'

defineProps<{ field: Field; entity: Entity; value: string }>()
</script>

<template>
  <span>
    {{ value }}
  </span>
</template>
