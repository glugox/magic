<script setup lang="ts">
import {router, useForm, usePage} from '@inertiajs/vue3';
import FieldRenderer from '@glugox/module/components/form/FieldRenderer.vue';
import {ApiResponse, DbId, Relation, ResourceData, ResourceFormEmits, ResourceFormProps} from '@glugox/module/types/support';
import { toast } from 'vue-sonner';
import { Button } from "@/components/ui/button";
import { useEntityContext } from "@glugox/module/composables/useEntityContext";
import DebugBox from "@glugox/module/components/debug/DebugBox.vue";
import { Toaster } from '@/components/ui/sonner';
import { useApi } from '@glugox/module/composables/useApi';
import {ref, unref} from "vue";
import RelationRenderer from "@glugox/module/components/form/RelationRenderer.vue";
import {useEntityLoader} from "@glugox/module/composables/useEntityLoader";
import {createLogger} from "@glugox/module/lib/logger";


const props = withDefaults(defineProps<ResourceFormProps>(), {
    closeOnSubmit: true, // default value
})
const emit = defineEmits<ResourceFormEmits>();

// Initialize logger
const logger = createLogger('ResourceForm', [props.entity.singularName, unref(props.id)]);
logger.log("init")


// Initial form data
const initialData: ResourceData = {} as ResourceData;
props.entity.fields.forEach((f) => {
    initialData[f.name] = props.item?.[f.name] ?? f.default ?? '';
});
// relations
props.entity.relations.forEach((r) => {
    initialData[r.relationName] = props.item?.[r.relationName] ?? null;
});
const form = useForm(initialData);

console.log("Form initialized", props);

const page = usePage()
const { post, put } = useApi();
const globalErrors = ref<string[]>([]);

// Load item automatically if not fully passed in
const { record, loading, error, load, localId } = useEntityLoader({
    entity: props.entity,
    id: props.id,
    item: props.item,
    forceLoad: props.forceLoad,
}, form);

// Context: URLs, action type
const { crudActionType, destroyUrl, storeUrl, updateUrl } =
    useEntityContext(props.entity, props.parentEntity, props.parentId, props.item, localId.value);

// If we are in dialog mode, we must be also in jsonMode
const jsonMode = props.jsonMode || props.dialogMode;


const onCreated = (record: unknown) => {
    globalErrors.value = []
    console.log("On created", record);
    if (jsonMode) {
        emit('created', record);
    }
    // In normal mode, redirect to index after creation
    if(props.closeOnSubmit && !jsonMode) {
        router.visit(props.entity.controller.index());
    }

    // form.defaults(initialData)
    // form.reset()

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

// --- Unified API error handler ---
const handleApiResponse = <T>(res: ApiResponse<T>, action: 'create' | 'update') => {
    form.clearErrors();
    globalErrors.value = [];

    if (!res.success) {
        if (res.errors) {
            // join individual field key errors into string if it is an array
            const normErrors: Record<string, string> = {};
            for (const key in res.errors) {
                normErrors[key] = Array.isArray(res.errors[key]) ? res.errors[key].join(' ') : res.errors[key];
            }

            form.setError(normErrors);
            globalErrors.value = Object.values(res.errors).flat();
        } else if (res.message) {
            globalErrors.value = [res.message];
            toast.error(res.message);
        }
        return false;
    }

    if (action === 'create') onCreated(res.content);
    else onUpdated(res.content);
    return true;
};

// Submit form
const submit = async () => {
    globalErrors.value = [];
    const headers = {
        'X-Inertia-Dialog': jsonMode ? 'true' : '',
        'Accept': jsonMode ? 'application/json' : 'text/html',
        'Content-Type': 'application/json',
    };

    // JSON mode (modal or API mode)
    if (jsonMode) {
        const endpoint = localId.value ? updateUrl.value.url : storeUrl.value.url;
        const method = localId.value ? put : post;
        console.log("Sending ( JSON mode ) to", endpoint, form.data());
        const res = await method(endpoint, form.data(), headers);
        handleApiResponse(res, localId.value ? 'update' : 'create');
        return;
    }

    //

    // Normal Inertia form mode
    if (!localId.value) {
        form.post(storeUrl.value, {
            headers,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const order = (page.props.flash as Record<string, any>)?.item
                onCreated(order)
            },
            onError: (errors) => (globalErrors.value = Object.values(errors).flat()),
        });
    } else {
        form.put(updateUrl.value, {
            headers,
            preserveScroll: true,
            onSuccess: () => onUpdated(form.data()),
            onError: (errors) => (globalErrors.value = Object.values(errors).flat()),
        });
    }
};

// Delete item
function destroy() {
    if (!destroyUrl.value) return;
    form.delete(destroyUrl.value, {
        onSuccess: () => {
            toast.success(`${props.entity.singularName} deleted`);
            emit('deleted', localId.value as any);
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


    <form @submit.prevent="submit" class="space-y-6">
        <div class="max-w-2xl">

            <DebugBox v-if="false" v-bind="props" />

            <!-- Global errors -->
            <div v-if="globalErrors.length" class="py-2 px-3 mb-2 rounded-lg border border-red-300 text-red-300">
                <ul class="list-disc list-inside">
                    <li v-for="(err, idx) in globalErrors" :key="idx">{{ err }}</li>
                </ul>
            </div>

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
            <!-- Right: HasOne relations -->
            <div class="space-y-4 mt-12">
                <RelationRenderer
                    v-for="relation in entity.relations.filter(r => r.type === 'hasOne')"
                    :key="relation.relationName"
                    :relation="relation"
                    :entity="entity"
                    :item="form[relation.relationName]"
                    :parent-id="(form.id || null) as DbId"
                    :crud-action-type="crudActionType"
                />
            </div>
            <!-- Action buttons -->

            <div v-if="!$slots.footer" class="flex justify-end gap-4 mt-8">
                <Button size="sm" type="submit" :disabled="form.processing" class="btn btn-outline-info">
                    {{ crudActionType === 'update' ? 'Update' : 'Create' }} {{ entity.singularName }}
                </Button>
                <Button
                    v-if="localId"
                    variant="destructive"
                    @click="destroy"
                    :disabled="form.processing"
                    class="btn btn-destructive"
                >
                    Delete
                </Button>
            </div>
        </div>
        <!-- Footer slot -->
        <slot name="footer"></slot>
    </form>
    <Toaster position="top-right" />
</template>
