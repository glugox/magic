<template>
    <div ref="stackContainer" class="gap-3 relative w-[400px] o">
        <div class="relative flex w-full">
            <transition-group name="slide-x" tag="div">
                <div
                    v-for="(form, i) in forms"
                    :key="form.id"
                    :class="['transition-all duration-300', i === 0 ? 'relative' : 'absolute top-0 left-0 w-full']"
                    :style="{
            zIndex: i + 1,
            transform: `translateX(${(i - (forms.length - 1)) * 100}%)`
          }"
                >
                    <ResourceForm
                        :entity="form.entity"
                        :item="form.item"
                        :controller="form.controller"
                        @open-related="openForm"
                        @close="() => closeForm(form.id)"
                    />
                </div>
            </transition-group>
        </div>
    </div>
</template>



<script setup lang="ts">
import { ref } from 'vue';
import ResourceForm from '@/components/ResourceForm.vue';
import { FormEntry, Relation } from '@/types/support';

const props = defineProps<{ initialForms: FormEntry[] }>();
const forms = ref<FormEntry[]>(props.initialForms);
const stackContainer = ref<HTMLElement | null>(null);

function openForm(relation: Relation) {
    const formEntry: FormEntry = {
        id: 'res-form-' + Math.random().toString(36).substring(2, 15),
        entity: props.initialForms[0].entity, // adjust based on relation if needed
        item: {},
        controller: props.initialForms[0].controller,
    };
    forms.value.push(formEntry);
}

function closeForm(id: string) {
    const index = forms.value.findIndex((f) => f.id === id);
    if (index !== -1) {
        forms.value.splice(index, 1);
    }
}
</script>

<style>
.slide-x-enter-from,
.slide-x-leave-to {
    opacity: 0.5;
    transform: translateX(50%);
}
.slide-x-enter-to,
.slide-x-leave-from {
    opacity: 1;
    transform: translateX(0%);
}
</style>
