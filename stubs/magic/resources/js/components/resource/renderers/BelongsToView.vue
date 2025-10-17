<script setup lang="ts">
import type {DbId, Entity, Field, ResourceData} from '@/types/support'
import {computed} from "vue";
import {useEntityLoader} from "@/composables/useEntityLoader";

const props = defineProps<{ field: Field; entity: Entity, value?: DbId | null }>()

const relationMetadata = props.entity.relations.find(r => r.foreignKey === props.field.name)!

const relatedEntity = computed(() => {
    const entityRef = relationMetadata.relatedEntity
    return typeof entityRef === "function" ? entityRef() : (entityRef as unknown as Entity)
})

const {record} = useEntityLoader({
    entity: relatedEntity.value,
    id: props.value
});

const nameValue = computed(() => {
    return relatedEntity.value.nameValueGetter && record.value?.data ? relatedEntity.value.nameValueGetter(record.value?.data as ResourceData) : ''
})


</script>

<template>
  <span>
    {{nameValue}}
  </span>
</template>
