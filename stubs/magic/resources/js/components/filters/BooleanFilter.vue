<template>
    <BaseFilter
        :label="label"
        :initialValues="{ active: false }"
    >
        <template #default="{ values }">
            <Switch v-model="values.active" class="mt-3 mx-auto" />
        </template>
    </BaseFilter>
</template>

<script setup lang="ts">
import { Switch } from "@/components/ui/switch"
import BaseFilter from "@/components/filters/BaseFilter.vue"
import type { FilterBaseProps } from "@/types/support"

const props = defineProps<FilterBaseProps>()
</script>
