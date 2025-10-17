<template>

    <div class="flex flex-col gap-3 border rounded-xl p-4 bg-muted/30">

        <!-- Relation picker (BelongsTo field) -->
        <div class="flex flex-col items-center gap-3">

            <div class="flex items-center justify-end w-full">
                <Button
                    v-if="!expanded"
                    :disabled="!allowExpand"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="flex shrink-0 text-xs h-8 px-2 justify-self-end"
                    @click="expanded = true"
                >
                    Manage {{ entity?.singularName }} details
                </Button>
            </div>
            <div class="flex-1 min-w-0 w-full">
                <slot name="field" />
            </div>

        </div>

        <!-- Expandable nested form -->
        <Transition name="fade-expand">
            <div v-if="expanded" class="mt-3 border-t pt-3">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-xs font-semibold text-muted-foreground">
                        {{title}}
                    </h4>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-xs text-muted-foreground"
                        @click="expanded = false"
                    >
                        ✕ Cancel
                    </Button>
                </div>

                <ResourceForm
                    :entity="entity"
                    :id="id"
                    :item="item"
                    :json-mode="true"
                    :close-on-submit="false"
                />
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {ExpandableFormProps, ResourceFormEmits, ResourceFormProps} from '@/types/support'
import { Button } from '@/components/ui/button'
import ResourceForm from '@/components/ResourceForm.vue'

const props = defineProps<ExpandableFormProps>()
const emit = defineEmits<ResourceFormEmits>()

const title = props.id ? `Edit ${props.entity.singularName}` : `Create ${props.entity.singularName}`

const expanded = ref(false)
</script>

<style scoped>
.fade-expand-enter-active,
.fade-expand-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}
.fade-expand-enter-from,
.fade-expand-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-4px);
}
.fade-expand-enter-to,
.fade-expand-leave-from {
    opacity: 1;
    max-height: 1000px;
    transform: translateY(0);
}
</style>
