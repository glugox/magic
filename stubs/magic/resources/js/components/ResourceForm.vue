<script setup lang="ts">
import {computed, ref, onMounted} from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Entity } from '@/types/support'
import FieldRenderer from '@/components/form/FieldRenderer.vue'
import { DbId } from "../types/support"
import {Loader} from "lucide-vue-next";
import { usePage } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner'
import { toast } from "vue-sonner"
import 'vue-sonner/style.css'

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
const inertiaPage = usePage()

onMounted(() => {
    console.log(inertiaPage.props.flash)
    if(inertiaPage.props.flash.success) {

    }
})

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
        form.post(formAction.value, {
            onFinish: () => {
                showToastMessage(entity.singularName + " created successfully");
            },
        })
    } else {
        form.put(formAction.value, {
            onFinish: () => {
                showToastMessage(entity.singularName + " updated successfully");
            },
        })
    }
}

// Delete handler
function destroy() {
    if (!deleteAction.value) return
    //if (confirm("Are you sure you want to delete this item?")) {
    form.delete(deleteAction.value)
    //}
}

function showToastMessage(message: string) {
    toast(message, {
        description: '',
        action: {
            label: 'Undo',
            onClick: () => console.log('Undo'),
        },
    })
}
</script>

<template>
    <Toaster /><Toaster />
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
        </div>
    </form>
</template>
