<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FieldRenderer from '@/components/form/FieldRenderer.vue';
import {DbId, Relation, ResourceData, ResourceFormEmits, ResourceFormProps} from '@/types/support';
import { toast } from 'vue-sonner';
import { Button } from "@/components/ui/button";
import { useEntityContext } from "@/composables/useEntityContext";
import DebugBox from "@/components/debug/DebugBox.vue";
import { Toaster } from '@/components/ui/sonner';
import { useApi } from '@/composables/useApi';
import {onMounted, ref, watch} from "vue";
import RelationRenderer from "@/components/form/RelationRenderer.vue";
import {useEntityLoader} from "@/composables/useEntityLoader";


const props = defineProps<ResourceFormProps>();
const emit = defineEmits<ResourceFormEmits>();

// Initial form data
const initialData: ResourceData = {} as ResourceData;
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


// 🧩 Load item automatically if not fully passed in
const { record, loading, error, load } = useEntityLoader({
    entity: props.entity,
    id: props.id,
    item: props.item,
}, form);


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
                const newRecord = await post(storeUrl.value.url, form.data(), headers)
                console.log("result", newRecord);
                onCreated(newRecord)
            } catch (e) {
                console.error(e)
                // handle errors
            }
        } else {
            // Normal Inertia submit
            form.post(storeUrl.value, {
                headers,
                preserveScroll: true,
                onSuccess: (page) => onCreated(form.data()),
                onError: (errors) => {
                    console.log("Errors", errors);
                    globalErrors.value = Object.values(errors).flat(); // flatten messages into array
                }
            })
        }
    } else {
        if (isJsonMode) {
            try {
                const newRecord = await put(updateUrl.value.url, form.data(), headers)
                onUpdated(newRecord)
            } catch (e) {
                console.error(e)
            }
        } else {
            // Normal Inertia submit
            form.put(updateUrl.value, {
                headers,
                preserveScroll: true,
                onSuccess: (page) => onUpdated(form.data()),
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
        <!-- Global errors -->
        <div v-if="globalErrors.length" class="py-2 px-3 mb-2 rounded-lg border border-red-300 text-red-300">
            <ul class="list-disc list-inside">
                <li v-for="(err, idx) in globalErrors" :key="idx">{{ err }}</li>
            </ul>
        </div>

        <div class="max-w-2xl">
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
                    :item="item?.[relation.relationName]"
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
                    v-if="props.item?.id"
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
