<template>
    <BaseFilter
        :label="label"
        :initialValues="null"
        v-slot="{ values }"
    >
        <Switch v-model="values.active" class="mt-3 mx-auto" />
    </BaseFilter>
</template>

<script setup lang="ts">
import { Switch } from "@/components/ui/switch"
import BaseFilter from "@/components/filters/BaseFilter.vue"
import type { FilterBaseProps } from "@/types/support"

defineProps<FilterBaseProps>()
</script>
