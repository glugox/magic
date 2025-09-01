<script setup lang="ts">
import { ref, computed} from 'vue';
import { Form, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Entity } from '@/types/support';
import FormField from '@/components/FormField.vue';

// Props
const { item, entityMeta, controller } = defineProps<{
    item?: Record<string, any>;
    entityMeta: Entity
    controller: any;
}>();

// Get Laravel errors from Inertia page props
const page = usePage();
const errors = page.props.errors as Record<string, string>;

// Build a reactive form object from entityMeta.fields
const form = ref<Record<string, any>>({});

// Initialize form with either existing item or defaults
entityMeta.fields.forEach((field: any) => {
    console.log('Initializing field:', field.name, 'with default:', field.default);
    form.value[field.name] = item ? item[field.name] : (field.default ?? '');
});

// Decide crudAction type
const crudActionType = computed(() => item ? 'update' : 'create');

// Decide CRUD action based on presence of item
const isEditMode = computed(() => crudActionType === 'update');

// Decide which action to call (create vs update)
const formAction = computed(() => {
    return item ? controller.update(item.id) : controller.store();
});
// Decide form method
const formMethod = computed(() => {
    return item ? 'put' : 'post';
});
</script>

<template>
    <Form :action="formAction" :method="formMethod" class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
        <FormField
            v-for="field in entityMeta.fields"
            :item="item"
            :error="errors[field.name]"
            :key="field.name"
            :field="field"
            :crud-action-type="crudActionType"
            v-model="form[field.name]"
        />

        <div class="flex items-center gap-4">
            <Button :disabled="processing">
                {{ item ? 'Update' : 'Create' }}
            </Button>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
            </Transition>
        </div>
    </Form>
</template>
