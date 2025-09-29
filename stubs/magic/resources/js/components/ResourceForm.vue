<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FieldRenderer from '@/components/form/FieldRenderer.vue';
import {Relation, ResourceFormProps} from '@/types/support';
import { toast } from 'vue-sonner';
import { Button } from "@/components/ui/button";
import { useEntityContext } from "@/composables/useEntityContext";
import DebugBox from "@/components/debug/DebugBox.vue";
import { Toaster } from '@/components/ui/sonner';
import { useApi } from '@/composables/useApi';
import {ref} from "vue";

const props = defineProps<ResourceFormProps>();

const emit = defineEmits<{
    (e: 'openRelated', relation: Relation): void;
    (e: 'created', record: any): void;
    (e: 'updated', record: any): void;
    (e: 'deleted', id: number|string): void;
}>();

// Initial form data
const initialData: Record<string, any> = {};
props.entity.fields.forEach((f) => {
    initialData[f.name] = props.item?.[f.name] ?? f.default ?? '';
});
const form = useForm(initialData);
const { post, put } = useApi();
const globalErrors = ref<string[]>([]);

// Context: URLs, action type
const { crudActionType, destroyUrl, storeUrl, updateUrl } =
    useEntityContext(props.entity, props.parentEntity, props.parentId, props.item);

// If we are in dialog mode, we must be also in jsonMode
const jsonMode = props.jsonMode || props.dialogMode;

const onCreated = (record: unknown) => {
    globalErrors.value = []
    console.log("On created", record);
    form.defaults(initialData)
    form.reset()
    if (jsonMode) {
        emit('created', record);
    }
    toast.success(`${props.entity.singularName} created`);
}

const onUpdated = (record: unknown) => {
    console.log("On updated", record);
    globalErrors.value = []
    if (jsonMode) {
        emit('updated', record);
    }
    toast.success(`${props.entity.singularName} updated`);
}
// Submit form
const submit = async () => {

    console.log("Submit", form);
    const isJsonMode = jsonMode
    const headers = {
        'X-Inertia-Dialog': isJsonMode ? 'true' : '',
        'Accept': isJsonMode ? 'application/json' : 'text/html',
        'Content-Type': 'application/json'
    }

    if (!props.item?.id) {
        if (isJsonMode) {
            // Use Axios/fetch for modal
            try {
                const record = await post(storeUrl.value.url, form.data(), headers)
                console.log("result", record);
                onCreated(record)
            } catch (e) {
                console.error(e)
                // handle errors
            }
        } else {
            // Normal Inertia submit
            form.post(storeUrl.value, {
                headers,
                preserveScroll: true,
                onSuccess: (page) => onCreated(page.props?.record ?? form.data()),
                onError: (errors) => {
                    console.log("Errors", errors);
                    globalErrors.value = Object.values(errors).flat(); // flatten messages into array
                }
            })
        }
    } else {
        if (isJsonMode) {
            try {
                const record = await put(updateUrl.value.url, form.data(), headers)
                onUpdated(record)
            } catch (e) {
                console.error(e)
            }
        } else {
            // Normal Inertia submit
            form.put(updateUrl.value, {
                headers,
                preserveScroll: true,
                onSuccess: (page) => onUpdated(page.props?.record ?? form.data()),
                onError: (errors) => {
                    console.log("Errors", errors);
                    globalErrors.value = Object.values(errors).flat(); // flatten messages into array
                }
            })
        }
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
    <div v-if="globalErrors.length" class="py-2 px-3 mb-2 rounded-lg border border-red-300 text-red-300">
        <ul class="list-disc list-inside">
            <li v-for="(err, idx) in globalErrors" :key="idx">{{ err }}</li>
        </ul>
    </div>
    <DebugBox v-if="false" v-bind="props" />
    <form @submit.prevent="submit" class="space-y-6">
        <FieldRenderer
            v-for="field in entity.fields"
            :key="field.name"
            :field="field"
            :entity="entity"
            :error="form.errors[field.name]"
            v-model="form[field.name]"
            :crud-action-type="crudActionType"
            @update:model-value="val => form[field.name] = val"
            @open-related="handleOpenRelated"
        />

        <!-- Default inline buttons if no dialog footer slot is used -->
        <div v-if="!$slots.footer" class="flex gap-4">
            <Button type="submit" :disabled="form.processing" class="btn btn-primary">
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
