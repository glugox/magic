<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Entity } from '@/types/support'
import FieldRenderer from '@/components/form/FieldRenderer.vue'

// Props
const { item, entityMeta, controller } = defineProps<{
    item?: Record<string, any>
    entityMeta: Entity
    controller: any,

}>()

// Build initial form data
const initialData: Record<string, any> = {}
entityMeta.fields.forEach((field: any) => {
    initialData[field.name] = item ? item[field.name] : field.default ?? ''
})

// Inertia form
const form = useForm(initialData)

// Decide CRUD action URL
const formAction = computed(() =>
    item ? controller.update(item.id) : controller.store()
)

// Decide crud action type
const crudActionType = computed(() => (item ? 'update' : 'create'))

// Submit handler
function submit() {
    if (item) {
        form.put(formAction.value)
    } else {
        form.post(formAction.value)
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <FieldRenderer
            v-for="field in entityMeta.fields"
            :key="field.name"
            :field="field"
            :error="form.errors[field.name]"
            v-model="form[field.name]"
            :crud-action-type="crudActionType"
        />

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing">
                {{ item ? 'Update' : 'Create' }}
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
