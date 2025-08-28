<script setup lang="ts">
import { ref } from "vue"
import { router, usePage } from "@inertiajs/vue3"
import { Button } from "@/components/ui/button"
import { Entity } from "@/types/magic"

// Props
const { item, entityMeta } = defineProps<{
    item?: Record<string, any>
    entityMeta: Entity
}>()

// Get Laravel errors from Inertia page props
const page = usePage()
const errors = page.props.errors as Record<string, string>

// Build a reactive form object from entityMeta.fields
const form = ref<Record<string, any>>({})

// Initialize form with either existing item or defaults
entityMeta.fields.forEach((field: any) => {
    console.log("Initializing field:", field.name, "with default:", field.default)
    form.value[field.name] = item ? item[field.name] : field.default ?? ""
})

console.log("Form initialized with:", form.value)

// Submit handler
const submit = () => {
    const isEdit = !!item?.id
    const url = isEdit
        ? route(`${entityMeta.resourcePath}.update`, item.id)
        : route(`${entityMeta.resourcePath}.store`)

    const method = isEdit ? "put" : "post"

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            // maybe redirect or flash message
        }
    })
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4 p-4">
        <div
            v-for="field in entityMeta.fields"
            :key="field.name"
            class="flex flex-col"
        >
            <label :for="field.name" class="font-medium">
                {{ field.label ?? field.name }}
            </label>

            <!-- Dynamically render input types -->
            <input
                v-if="field.type === 'string'"
                v-model="form[field.name]"
                :id="field.name"
                type="text"
                class="border rounded px-2 py-1"
            />

            <input
                v-else-if="field.type === 'number'"
                v-model.number="form[field.name]"
                :id="field.name"
                type="number"
                class="border rounded px-2 py-1"
            />

            <input
                v-else-if="field.type === 'date'"
                v-model="form[field.name]"
                :id="field.name"
                type="date"
                class="border rounded px-2 py-1"
            />

            <select
                v-else-if="field.type === 'select'"
                v-model="form[field.name]"
                :id="field.name"
                class="border rounded px-2 py-1"
            >
                <option
                    v-for="opt in field.options ?? []"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </select>

            <!-- fallback -->
            <input
                v-else
                v-model="form[field.name]"
                :id="field.name"
                type="text"
                class="border rounded px-2 py-1"
            />

            <!-- Validation errors -->
            <p v-if="errors[field.name]" class="text-red-500 text-sm">
                {{ errors[field.name] }}
            </p>
        </div>

        <div class="flex gap-2">
            <Button type="submit">
                {{ item ? "Update" : "Create" }}
            </Button>
            <Button type="button" variant="secondary" @click="() => history.back()">
                Cancel
            </Button>
        </div>
    </form>
</template>
