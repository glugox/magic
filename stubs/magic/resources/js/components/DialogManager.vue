<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import type { DbId, Entity, Relation } from '@/types/support';
import ResourceForm from '@/components/ResourceForm.vue';

interface DialogOptions {
    entity: Entity;
    item?: Record<string, any>;
    controller: any;
    parentEntity?: Entity;
    parentId?: DbId;
    title?: string;
}

interface DialogInstance extends DialogOptions {
    id: string;
}

const dialogs = ref<DialogInstance[]>([]);

// Open a new dialog
function openDialog(options: DialogOptions) {
    console.log("Op")
    const id = 'dialog-' + Math.random().toString(36).substring(2, 15);
    dialogs.value.push({ ...options, id });
}

function openDialogRelation(r: Relation) {
    if (dialogs.value.length === 0) return;
    const currentDialog = dialogs.value[dialogs.value.length - 1];
    openDialog({
        entity: currentDialog.entity,
        controller: currentDialog.controller, // You might want to adjust this based on your app logic
        parentEntity: currentDialog.entity,
        parentId: currentDialog.item?.id,
    });
}

// Close a dialog
function closeDialog(id: string) {
    dialogs.value = dialogs.value.filter(d => d.id !== id);
}

function handleOpenRelated(relation: Relation) {

    console.log("DialogManager:: Handle open related", relation);

    /*if (dialogs.value.length === 0) return;
    const currentDialog = dialogs.value[dialogs.value.length - 1];
    openDialog({
        entity: currentDialog.entity,
        controller: currentDialog.controller, // You might want to adjust this based on your app logic
        parentEntity: currentDialog.entity,
        parentId: currentDialog.item?.id,
    });*/
}

defineExpose({ openDialog, closeDialog, openDialogRelation });
</script>

<template>
    <div class="flex-1 overflow-y-auto pr-2">
        <template v-for="d in dialogs" :key="d.id">
            <Dialog :open="true" @update:open="() => closeDialog(d.id)">
                <DialogContent class="flex max-h-[90vh] flex-col">
                    <DialogHeader>
                        <DialogTitle>{{ d.title ?? d.entity.singularName }}</DialogTitle>
                    </DialogHeader>

                    <div class="flex-1 overflow-y-auto pr-2">
                        <ResourceForm
                            :entity="d.entity"
                            :item="d.item"
                            :controller="d.controller"
                            :parent-entity="d.parentEntity"
                            :parent-id="d.parentId"
                            @open-related="handleOpenRelated"
                        >
                            <template #default="{ submit, destroy, processing }">
                                <DialogFooter class="sticky bottom-0 mt-4 border-t bg-background p-4 flex gap-4">
                                    <button @click="submit" :disabled="processing" class="btn btn-primary">
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
