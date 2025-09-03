<template>
    <BaseField v-bind="props" :error="error" v-model="model">
        <template #default="{ validate }">
            <Popover v-model:open="open">
                <PopoverTrigger as-child>
                    <Button
                        ref="triggerElement"
                        variant="outline"
                        role="combobox"
                        :aria-label="`Select ${field.label}`"
                        :class="['w-full justify-between', !model ? 'text-muted-foreground' : '']"
                        :disabled="isLoading"
                    >
                        {{ displayValue || `Select ${field.label}...` }}
                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-full p-0" :style="`min-width: ${triggerWidth}px`">
                    <Command :filter-function="customFilter" @keydown.enter="handleEnter">
                        <CommandInput
                            :placeholder="`Search ${field.label}...`"
                            class="h-9"
                            v-model="searchQuery"
                        />
                        <CommandList>
                            <CommandEmpty>
                                {{ isLoading ? 'Loading...' : 'No results found.' }}
                            </CommandEmpty>
                            <CommandGroup v-if="!isLoading">
                                <CommandItem
                                    v-for="option in options"
                                    :key="option.value"
                                    :value="option.label"
                                    @select="() => handleSelect(option, validate)"
                                    :class="['cursor-pointer', model === option.value ? 'bg-accent' : '']"
                                >
                                    {{ option.label }}
                                    <CheckIcon
                                        v-if="model === option.value"
                                        class="ml-auto h-4 w-4 opacity-100"
                                        :class="[model === option.value ? 'opacity-100' : 'opacity-0']"
                                    />
                                </CommandItem>
                            </CommandGroup>
                            <CommandGroup v-else>
                                <CommandItem value="loading" disabled>
                                    <Loader2 class="h-4 w-4 animate-spin mr-2" />
                                    Loading...
                                </CommandItem>
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import BaseField from './BaseField.vue'
import { Button } from '@/components/ui/button'
import { CheckIcon, ChevronsUpDown, Loader2 } from 'lucide-vue-next'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Field, CrudActionType, Entity } from '@/types/support'
import { ref, watch, onMounted, computed, nextTick } from 'vue'
import {useApi} from "@/composables/useApi";

interface Props {
    error?: string
    field: Field
    entityMeta: Entity
    crudActionType: CrudActionType
    modelValue?: any
    item?: Record<string, any>
}

interface Option {
    value: string | number
    label: string
    [key: string]: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const model = ref(props.modelValue)
const open = ref(false)
const searchQuery = ref('')
const isLoading = ref(false)
const options = ref<Option[]>([])
const triggerWidth = ref(200)
const triggerElement = ref<HTMLElement | null>(null)

// Get relation metadata
const relationMetadata = props.entityMeta.relations.find(r => r.foreignKey === props.field.name)
const relationName = relationMetadata ? relationMetadata.relationName : ''

// Display value for the selected option
const displayValue = computed(() => {
    if (!model.value) return ''
    const selected = options.value.find(opt => opt.value === model.value)
    return selected ? selected.label : 'Loading...'
})

watch(model, (val) => {
    emit('update:modelValue', val)
})

watch([open, searchQuery], async ([isOpen, query]) => {
    if (isOpen && options.value.length === 0) {
        await fetchOptions()
    }
    if (isOpen && query) {
        // Simple debounce implementation
        clearTimeout((window as any).searchTimeout)
        ;(window as any).searchTimeout = setTimeout(async () => {
            await fetchOptions(query)
        }, 300)
    }
})

onMounted(async () => {
    await nextTick()
    if (triggerElement.value) {
        triggerWidth.value = triggerElement.value.offsetWidth
    }

    if (model.value && options.value.length === 0) {
        await fetchOptions()
    }
})

const customFilter = (value: string, search: string) => {
    return value.toLowerCase().includes(search.toLowerCase()) ? 1 : 0
}

const { get } = useApi()

const fetchOptions = async (query: string = '') => {
    isLoading.value = true
    try {
        const data = await get(`/${relationMetadata?.relationName}`, {
            search: query,
            limit: '50',
            fields: 'id,name'
        })

        options.value = data.map((item: any) => ({
            value: item.id,
            label: item.name || item.title || item.email || `Item ${item.id}`,
            ...item
        }))
    } catch (error) {
        console.error('Error fetching options:', error)
        options.value = []
    } finally {
        isLoading.value = false
    }
}

const handleSelect = (option: Option, validate: (value: any) => void) => {
    model.value = option.value
    validate(option.value)
    open.value = false
    searchQuery.value = ''
}

const handleEnter = () => {
    if (options.value.length > 0 && !isLoading.value) {
        handleSelect(options.value[0], () => {})
    }
}
</script>
