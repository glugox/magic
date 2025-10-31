<script setup lang="ts">
import type {Entity, Field} from '@glugox/module/types/support'
import {formatDate} from "@glugox/module/lib/app";

defineProps<{ field: Field; entity: Entity; value?: string | null }>()
</script>

<template>
  <span>
    {{ formatDate(value) }}
  </span>
</template>
