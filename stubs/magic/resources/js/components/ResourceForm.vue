<script setup lang="ts">
import { computed} from 'vue';
import { useForm } from '@inertiajs/vue3';
import FieldRenderer from '@/components/form/FieldRenderer.vue';
import type { Entity, Relation, DbId } from '@/types/support';
import { toast } from 'vue-sonner';

const props = defineProps<{
    item?: Record<string, any>;
    entity: Entity;
    controller: any;
    parentEntity?: Entity;
    parentId?: DbId;
}>();

const emit = defineEmits<{
    (e: 'openRelated', relation: Relation): void;
}>();

const initialData: Record<string, any> = {};
props.entity.fields.forEach((f) => {
    initialData[f.name] = props.item?.[f.name] ?? f.default ?? '';
});

const form = useForm(initialData);

// Computed actions
const formAction = computed(() => {
    if (!props.item?.id) return props.controller.store({});
    return props.controller.update([props.item.id]);
});
const deleteAction = computed(() => (props.item?.id ? props.controller.destroy(props.item.id) : null));
const crudActionType = computed(() => (props.item ? 'update' : 'create'));

// Expose submit & destroy for dialog buttons
function submit() {
    if (!props.item?.id) {
        form.post(formAction.value, { onFinish: () => toast(`${props.entity.singularName} created`) });
    } else {
        form.put(formAction.value, { onFinish: () => toast(`${props.entity.singularName} updated`) });
    }
}
function destroy() {
    if (!deleteAction.value) return;
    form.delete(deleteAction.value);
}


function handleOpenRelated(relation: Relation) {
    console.log("ResourceForm:: Handle open related", relation);
    emit('openRelated', relation);
}

// For v-slot
defineExpose({ submit, destroy, processing: form.processing });
</script>

<template>
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
            <button @click="submit" :disabled="form.processing" class="btn btn-primary">
                {{ crudActionType === 'update' ? 'Update' : 'Create' }}
            </button>

            <button v-if="props.item?.id" @click="destroy" :disabled="form.processing" class="btn btn-destructive">Delete</button>
        </div>

        <!-- Slot for dialog footer buttons -->
        <slot name="footer"></slot>
    </form>
</template>
