<script setup lang="ts">
import {computed, ref} from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Entity } from '@/types/support'
import FieldRenderer from '@/components/form/FieldRenderer.vue'
import { DbId } from "../types/support"
import {Loader} from "lucide-vue-next";

// Props
const { item, entity, controller, parentEntity, parentId } = defineProps<{
    item?: Record<string, any>
    entity: Entity
    controller: any
    parentEntity?: Entity
    parentId?: DbId
}>()

// Build initial form data
const initialData: Record<string, any> = {}
entity.fields.forEach((field: any) => {
    initialData[field.name] = item ? item[field.name] : field.default ?? ''
})

// Inertia form
const form = useForm(initialData)

// Decide CRUD action URL
const formAction = computed(() => {
    let args = {}

    if (item?.id) {
        args = [item.id]
    }

    const parentEntitySingularLower = parentEntity?.singularNameLower
    if (parentId && parentEntitySingularLower) {
        args = { [parentEntitySingularLower]: parentId }
    }

    if (!item?.id) {
        return controller.store(args)
    } else {
        return controller.update(args)
    }
})

// Decide delete action URL
const deleteAction = computed(() => {
    if (!item?.id) return null
    return controller.destroy(item.id)
})

// Decide crud action type
const crudActionType = computed(() => (item ? 'update' : 'create'))

// Submit handler
async function submit() {
    if (!item?.id) {
        form.post(formAction.value)
    } else {
        form.put(formAction.value)
    }
}

// Delete handler
function destroy() {
    if (!deleteAction.value) return
    //if (confirm("Are you sure you want to delete this item?")) {
    form.delete(deleteAction.value)
    //}
}
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
            :item="item"
            :crud-action-type="crudActionType"
        />

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing">
                {{ !item?.id ? 'Create' : 'Update' }}
            </Button>

            <Button
                v-if="item?.id"
                type="button"
                variant="destructive"
                :disabled="form.processing"
                @click="destroy"
            >
                Delete
            </Button>

            <Loader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                    {{entity.singularName}} updated successfully.
                </p>
            </Transition>
        </div>
    </form>
</template>
