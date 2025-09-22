<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FieldRenderer from '@/components/form/FieldRenderer.vue';
import {Relation, ResourceFormProps} from '@/types/support';
import { toast } from 'vue-sonner';
import { Button } from "@/components/ui/button";
import { useEntityContext } from "@/composables/useEntityContext";
import DebugBox from "@/components/debug/DebugBox.vue";
import { Toaster } from '@/components/ui/sonner';
import ResourceDebugBox from "@/components/debug/ResourceDebugBox.vue";

const props = defineProps<ResourceFormProps>();

const emit = defineEmits<{
    (e: 'openRelated', relation: Relation): void;
    (e: 'created', record: any): void;
    (e: 'updated', record: any): void;
    (e: 'deleted', id: number|string): void;
}>();

// Initial form data
const initialData: Record<string, any> = {
    _parentComponent: props.parentInertiaPage ?? null,
};
props.entity.fields.forEach((f) => {
    initialData[f.name] = props.item?.[f.name] ?? f.default ?? '';
});
const form = useForm(initialData);

// Context: URLs, action type
const { crudActionType, destroyUrl, storeUrl, updateUrl } =
    useEntityContext(props.entity, props.parentEntity, props.parentId, props.item);

const onCreated = (record: unknown) => {
    if (props.jsonMode) {
        emit('created', record);
    }
    toast.success(`${props.entity.singularName} created`);
}

const onUpdated = (record: unknown) => {
    if (props.jsonMode) {
        emit('updated', record);
    }
    toast.success(`${props.entity.singularName} updated`);
}

// Helpers
function submit() {
    const headers = {
        Accept: props.jsonMode ? 'application/json' : 'text/html',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Inertia-Dialog': 'true', // 👈 custom marker
    };
    if (!props.item?.id) {
        form.post(storeUrl.value, {
            headers,
            preserveScroll: true,
            onSuccess: (page) => onCreated(page.props?.record ?? form.data())
        });
    } else {
        form.put(updateUrl.value, {
            headers,
            preserveScroll: true,
            onSuccess: (page) => onUpdated(page.props?.record ?? form.data()),
        });
    }
}

function destroy() {
    if (!destroyUrl.value) return;
    form.delete(destroyUrl.value, {
        onSuccess: () => {
            toast.success(`${props.entity.singularName} deleted`);
            emit('deleted', props.item?.id as any);
        },
    });
}

function handleOpenRelated(relation: Relation) {
    emit('openRelated', relation);
}

// For dialogs
defineExpose({ submit, destroy, processing: form.processing });
</script>

<template>
    <ResourceDebugBox v-bind="props" class="mb-4" />
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

            <Button
                v-if="props.item?.id"
                variant="destructive"
                @click="destroy"
                :disabled="form.processing"
                class="btn btn-destructive"
            >
                Delete
            </Button>
        </div>

        <!-- Slot for dialog footer -->
        <slot name="footer"></slot>
    </form>
    <Toaster position="top-right" />
</template>
