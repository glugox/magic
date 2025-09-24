<script setup lang="ts">
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import type {
    DbId,
    DialogInstance,
    DialogOptions,
    Entity,
    Relation,
} from '@/types/support';
import ResourceForm from '@/components/ResourceForm.vue';

const dialogs = ref<DialogInstance[]>([]);

// Open a new dialog
function openDialog(options: DialogOptions) {
    const id = 'dialog-' + Math.random().toString(36).substring(2, 15);
    dialogs.value.push({ ...options, id });
}

function closeDialog(id: string) {
    dialogs.value = dialogs.value.filter((d) => d.id !== id);
}

// Handle open-related inside a form field
function handleOpenRelated(relation: Relation) {
    console.log('DialogManager:: handleOpenRelated', relation);
    // TODO: you could even open a new dialog chain here
}

function handleFormEvent(
    d: DialogInstance,
    action: 'created' | 'updated' | 'deleted',
    payload: any,
) {
    // Call parent callback if provided
    d.onSuccess?.(payload, action);
    // Close dialog after success
    closeDialog(d.id);
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <div class="flex-1 overflow-y-auto pr-2">
        <template v-for="d in dialogs" :key="d.id">
            <Dialog :open="true" @update:open="() => closeDialog(d.id)">
                <DialogContent class="flex max-h-[90vh] flex-col">
                    <DialogHeader>
                        <DialogTitle
                        >{{ d.title ?? d.entity.singularName }}</DialogTitle
                        >
                    </DialogHeader>

                    <div class="flex-1 overflow-y-auto pr-2">
                        <ResourceForm
                            :entity="d.entity"
                            :item="d.item"
                            :parent-entity="d.parentEntity"
                            :parent-id="d.parentId"
                            :json-mode="true"
                            :dialog-mode="true"
                            @open-related="handleOpenRelated"
                            @created="
                                (record) =>
                                    handleFormEvent(d, 'created', record)
                            "
                            @updated="
                                (record) =>
                                    handleFormEvent(d, 'updated', record)
                            "
                            @deleted="(id) => handleFormEvent(d, 'deleted', id)"
                        >
                            <template
                                #default="{ submit, destroy, processing }"
                            >
                                <DialogFooter
                                    class="sticky bottom-0 mt-4 flex gap-4 border-t bg-background p-4"
                                >
                                    <button
                                        @click="submit"
                                        :disabled="processing"
                                        class="btn btn-primary"
                                    >
                                        {{ d.item?.id ? 'Update' : 'Create' }}
                                    </button>
                                    <button
                                        v-if="d.item?.id"
                                        @click="destroy"
                                        :disabled="processing"
                                        class="btn btn-destructive"
                                    >
                                        Delete
                                    </button>
                                </DialogFooter>
                            </template>
                        </ResourceForm>
                    </div>
                </DialogContent>
            </Dialog>
        </template>
    </div>
</template>
