<script setup lang="ts">
import { ref, watch } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxSeparator, ComboboxTrigger } from '@/components/ui/combobox'
import BaseField from './BaseField.vue'
import { FormFieldEmits, FormFieldProps } from '@/types/support'
import { Link } from '@inertiajs/vue3'
import { Check, ChevronsUpDown, PlusCircleIcon } from 'lucide-vue-next'
import { useBelongsToOptions } from '@/composables/useBelongsToOptions'

const props = defineProps<FormFieldProps>()
const emit = defineEmits<FormFieldEmits>()

// Get relation metadata
const relationMetadata = props.entity.relations.find(r => r.foreignKey === props.field.name)!
const modelNameSingular = relationMetadata.relatedEntityName
const relationApiPath = relationMetadata.apiPath
const relationEntityName = relationMetadata.relatedEntityName

if(!relationApiPath) {
    throw new Error(`Relation metadata apiPath not found for field ${props.field.name}`)
}
if(!relationEntityName) {
    throw new Error(`Relation metadata relatedEntityName not found for field ${props.field.name}`)
}

// Use composable for options and selected
const { options, selectedOption, searchQuery, isLoading } = useBelongsToOptions({
    relationMetadata: {
        apiPath: relationApiPath,
        relatedEntityName: relationEntityName,
        foreignKey: relationApiPath
    },
    initialId: props.modelValue ? String(props.modelValue) : null,
    autoRefreshOnCreate: true,
})

// Local model for v-model binding
const model = ref<string | null>(props.modelValue ? String(props.modelValue) : null)

// Sync modelValue prop with local model
watch(
    () => props.modelValue,
    val => {
        model.value = val ? String(val) : null
    }
)

// Sync selectedOption with model and emit changes
watch(selectedOption, val => {
    model.value = val?.id ?? null
    emit('update:modelValue', model.value)
})

// Emit open related form event
const emitOpenRelatedForm = () => emit('openRelated', relationMetadata)
</script>

<template>
    <BaseField v-bind="props">
        <template #default>
            <select class="sr-only" :name="props.field.name" v-model="model" data-test="select-{{props.field.name}}">
                <option v-for="item in options" :key="item.id" :value="item.id">{{ item.name }}</option>
            </select>

            <Combobox v-model="selectedOption" by="id">
                <ComboboxAnchor class="w-[300px]" as-child>
                    <ComboboxTrigger as-child>
                        <Button variant="outline" class="justify-between">
                            <template v-if="selectedOption">
                                <div class="flex items-center gap-2">
                                    <Avatar class="size-5">
                                        <AvatarImage :src="`https://github.com/${selectedOption.name}.png`" />
                                        <AvatarFallback>{{ selectedOption.name[0] }}</AvatarFallback>
                                    </Avatar>
                                    {{ selectedOption.name }}
                                </div>
                            </template>
                            <template v-else>
                                <span class="text-muted-foreground">Select {{ modelNameSingular }}...</span>
                            </template>
                            <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
                        </Button>
                    </ComboboxTrigger>
                </ComboboxAnchor>

                <ComboboxList class="w-[300px]">
                    <ComboboxInput :placeholder="isLoading ? 'Loading...' : 'Select ' + modelNameSingular + '...'" v-model="searchQuery" />
                    <ComboboxEmpty>No {{ modelNameSingular }} found.</ComboboxEmpty>

                    <ComboboxGroup>
                        <!-- Null option -->
                        <ComboboxItem :value="null">
                            <em class="text-muted-foreground">Please select...</em>
                        </ComboboxItem>
                        <ComboboxItem v-for="item in options" :key="item.id" :value="item">
                            <Avatar class="size-5">
                                <AvatarImage :src="`https://github.com/${item.name}.png`" />
                                <AvatarFallback>{{ item.name[0] }}</AvatarFallback>
                            </Avatar>
                            {{ item.name }}
                            <ComboboxItemIndicator><Check /></ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>

                    <ComboboxSeparator />

                    <ComboboxGroup>
                        <ComboboxItem :value="null" @select="emitOpenRelatedForm">
                            <PlusCircleIcon />
                            Create {{ modelNameSingular }}
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>

            <div v-if="selectedOption">
                <Link :href="`/${relationMetadata.apiPath}/${selectedOption.id}/edit`" class="text-xs text-muted-foreground underline">
                    Edit this {{ modelNameSingular }}
                </Link>
            </div>
        </template>
    </BaseField>
</template>
