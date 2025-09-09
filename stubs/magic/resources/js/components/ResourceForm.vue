<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Entity } from '@/types/support'
import FieldRenderer from '@/components/form/FieldRenderer.vue'
import {DbId} from "../types/support";

// Props
const { item, entity, controller, parentEntity, parentId } = defineProps<{
    item?: Record<string, any>
    entity: Entity
    controller: any,
    parentEntity?: Entity,
    parentId?: DbId,

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
    // item ? controller.update(item.id) : controller.store()
    var args = {}

    // If item has id, use it
    if (item && item.id) {
        args = { id: item.id }
    }

    // If parentId is provided, add it to args in a format suitable for the controller method
    // E.g., if parent entity is 'User', add { user: parentId }
    const parentEntitySingularLower = parentEntity?.singularNameLower
    if (parentId && parentEntitySingularLower) {
        args = { [parentEntitySingularLower]: parentId }
    }

    // Call the appropriate controller method from Wayfinder
    if (!item?.id) {
        return controller.store(args)
    } else {
        return controller.update(args)
    }
})

// Decide crud action type
const crudActionType = computed(() => (item ? 'update' : 'create'))

// Submit handler
function submit() {
    if (!item?.id) {
        form.post(formAction.value)
    } else {
        form.put(formAction.value)
    }
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
                {{ !item?.id ? 'Create' : 'Update'}}
            </Button>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                    Saved.
                </p>
            </Transition>
        </div>
    </form>
</template>
