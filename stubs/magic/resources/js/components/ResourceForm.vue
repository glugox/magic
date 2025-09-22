<script setup lang="ts">
import { computed} from 'vue';
import { useForm } from '@inertiajs/vue3';
import FieldRenderer from '@/components/form/FieldRenderer.vue';
import {Entity, Relation, DbId, ResourceFormProps} from '@/types/support';
import { toast } from 'vue-sonner';
import {Button} from "@/components/ui/button";
import {useEntityContext} from "@/composables/useEntityContext";
import DebugBox from "@/components/debug/DebugBox.vue";

const props = defineProps<ResourceFormProps>();

const emit = defineEmits<{
    (e: 'openRelated', relation: Relation): void;
}>();

const initialData: Record<string, any> = {};
props.entity.fields.forEach((f) => {
    initialData[f.name] = props.item?.[f.name] ?? f.default ?? '';
});

const form = useForm(initialData);

// Entity context
const {relation, controller, crudActionType, formAction, destroyUrl, storeUrl, updateUrl} = useEntityContext(props.entity, props.parentEntity, props.parentId, props.item);

// Expose submit & destroy for dialog buttons
function submit() {
    if (!props.item?.id) {
        form.post(storeUrl.value, { onFinish: () => toast(`${props.entity.singularName} created`) });
    } else {
        form.put(updateUrl.value, { onFinish: () => toast(`${props.entity.singularName} updated`) });
    }
}
function destroy() {
    if (!destroyUrl.value) return;
    form.delete(destroyUrl.value);
}


function handleOpenRelated(relation: Relation) {
    console.log("ResourceForm:: Handle open related", relation);
    emit('openRelated', relation);
}

// For v-slot
defineExpose({ submit, destroy, processing: form.processing });
</script>

<template>
    <DebugBox v-bind="props" />
    <form @submit.prevent="submit" class="space-y-6">
        <FieldRenderer
            v-for="field in entity.fields"
            :key="field.name"
            :field="field"
            :entity="entity"
            :error="form.errors[field.name]"
            v-model="form[field.name]"
            :crud-action-type="crudActionType"
            @open-related="handleOpenRelated"
        />

        <!-- Default inline buttons if no dialog footer slot is used -->
        <div v-if="!$slots.footer" class="flex gap-4">
            <Button @click="submit" :disabled="form.processing" class="btn btn-primary">
                {{ crudActionType === 'update' ? 'Update' : 'Create' }}
            </Button>

            <Button variant="destructive" v-if="props.item?.id" @click="destroy" :disabled="form.processing" class="btn btn-destructive">Delete</Button>
        </div>

        <!-- Slot for dialog footer buttons -->
        <slot name="footer"></slot>
    </form>
</template>
