<script setup lang="ts">
import type {DbId, Entity, Field} from '@glugox/module/types/support'

defineProps<{ field: Field; entity: Entity; value?: DbId | null }>()
</script>

<template>
  <span>
    {{ value }}
  </span>
</template>
