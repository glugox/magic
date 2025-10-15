<!-- components/view/ViewFieldRenderer.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import type {Entity, Field, ResourceData} from '@/types/support'

import StringView from '@/components/resource/renderers/StringView.vue'
import NumberView from '@/components/resource/renderers/NumberView.vue'
import BooleanView from '@/components/resource/renderers/BooleanView.vue'
import EnumView from '@/components/resource/renderers/EnumView.vue'
import DateView from '@/components/resource/renderers/DateView.vue'
import BelongsToView from '@/components/resource/renderers/BelongsToView.vue'
import IdView from "@/components/resource/renderers/IdView.vue";

interface Props {
    field: Field
    entity: Entity
    item: ResourceData
}

const props = defineProps<Props>()


const viewFieldComponents: Record<string, any> = {
    id: IdView,
    string: StringView,
    number: NumberView,
    decimal: NumberView,
    boolean: BooleanView,
    enum: EnumView,
    date: DateView,
    dateTime: DateView,
    belongsTo: BelongsToView,
    foreignId: BelongsToView,
}

const viewComponent = computed(() => {
    return props.field.component || viewFieldComponents[props.field.type] || 'span'
})

const value = computed(() => props.item[props.field.name])
</script>

<template>
    <component
        :is="viewComponent"
        :field="field"
        :entity="entity"
        :value="value"
    />
</template>
