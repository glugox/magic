<script setup lang="ts">
import type {Entity, Field} from '@/types/support'
import {formatDate} from "@/lib/app";

defineProps<{ field: Field; entity: Entity; value?: string | null }>()
</script>

<template>
  <span>
    {{ formatDate(value) }}
  </span>
</template>
