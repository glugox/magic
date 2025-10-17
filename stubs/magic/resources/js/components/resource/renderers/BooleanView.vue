<script setup lang="ts">
import type {Entity, Field} from '@/types/support'

defineProps<{ field: Field; entity: Entity; value?: boolean | null }>()
</script>

<template>
  <span>
    {{ value ? 'Yes' : 'No' }}
  </span>
</template>
