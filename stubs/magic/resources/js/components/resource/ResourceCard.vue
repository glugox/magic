<script setup lang="ts">
import {DbId, Entity, ResourceData} from "@/types/support";
import {useEntityLoader} from "@/composables/useEntityLoader";
import {computed} from "vue";
import ViewFieldRenderer from "@/components/resource/ViewFieldRenderer.vue";

const props = defineProps<{
    entity: Entity,
    id?: DbId | null
}>()

const { record } = useEntityLoader({
    entity: props.entity,
    id: props.id
});

const recordData = computed(() => record.value?.data as ResourceData || {})

const exists = computed(() => !!record.value && Object.keys(recordData.value).length > 0)

</script>

<template>
    <div v-if="exists" class="p-4 border rounded-lg shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <template v-for="field in entity.fields" :key="field.name">
                <div v-if="field.contexts?.card" class="space-y-1">
                    <div class="text-sm text-muted-foreground font-medium">{{ field.label || field.name }}</div>
                    <div class="text-sm font-bold">
                        <ViewFieldRenderer :field="field" :entity="entity" :item="recordData" />
                    </div>
                </div>
            </template>
        </div>
    </div>
    <div v-else>
        <!-- No relation established yet -->
        <p class="text-xs text-muted-foreground">
            No {{ entity.singularName.toLowerCase() }} associated yet.
        </p>
    </div>
</template>
