<script setup lang="ts">
import { ref } from 'vue';
import { Form, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Entity } from '@/types/support';
import FormField from '@/components/FormField.vue';

// Props
const { item, entityMeta, controller } = defineProps<{
    item?: Record<string, any>;
    entityMeta: Entity;
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
</script>

<template>
    <Form :action="controller.update(item?.id)" method="post" class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
        <FormField
            v-for="field in entityMeta.fields"
            :item="item"
            :error="errors[field.name]"
            :key="field.name" :field="field" v-model="form[field.name]"
        />

        <div class="flex items-center gap-4">
            <Button :disabled="processing">Save</Button>

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
