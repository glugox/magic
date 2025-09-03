<script setup lang="ts">
import { Check, ChevronsUpDown, PlusCircleIcon } from 'lucide-vue-next'
import { onMounted, ref, watch } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxSeparator,
    ComboboxTrigger
} from '@/components/ui/combobox'
import { CrudActionType, Entity, Field } from "@/types/support"
import BaseField from "@/components/form/BaseField.vue"
import { useApi } from "@/composables/useApi"

interface Props {
    error?: string
    field: Field
    entityMeta: Entity
    crudActionType: CrudActionType
    modelValue?: any
    item?: Record<string, any>
}

interface Option extends Record<string, any> {
    id: string
    name: string
    [key: string]: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])
const { get } = useApi()

const model = ref<string | null>(props.modelValue ? String(props.modelValue) : null)
const selectedOption = ref<Option | null>(null)
const searchQuery = ref('')
const isLoading = ref(false)
const options = ref<Option[]>([])
const lastQuery = ref<string>('')

// Get relation metadata
const relationMetadata = props.entityMeta.relations.find(r => r.foreignKey === props.field.name)
const modelNameSingular = relationMetadata?.entityName

const normalize = (d: any) => ({ ...d, id: String(d.id) })

onMounted(async () => {
    // Fetch initial record by ID
    if (model.value) {
        const res = await get(`/${relationMetadata?.relationName}/${model.value}`)
        const record = res?.data ?? res
        if (record) {
            const normalized = normalize(record)
            selectedOption.value = normalized
            // ensure it’s in options
            if (!options.value.find(o => o.id === normalized.id)) {
                options.value.unshift(normalized)
            }
        }
    }

    // Load default options
    await fetchOptions()
})

// Watch selectedOption to sync modelValue
watch(selectedOption, (val) => {
    if (val) {
        model.value = val.id
        emit('update:modelValue', val.id)
    } else {
        model.value = null
        emit('update:modelValue', null)
    }
})

// Watch options to re-sync selectedOption reference
watch(options, (list) => {
    if (selectedOption.value) {
        const match = list.find(o => o.id === selectedOption.value!.id)
        if (match) selectedOption.value = match
    }
})

// Debounced search
let searchTimeout: any = null
watch(searchQuery, (query) => {
    clearTimeout(searchTimeout)
    if (query && query.length > 1) {
        searchTimeout = setTimeout(() => fetchOptions(query), 300)
    } else if (!query) {
        fetchOptions()
    }
})

const fetchOptions = async (query: string = '') => {
    lastQuery.value = query
    isLoading.value = true
    try {
        const res = await get(`/${relationMetadata?.relationName}`, {
            search: query,
            limit: '5',
        })

        if (lastQuery.value !== query) return // ignore stale results

        const list = ((res?.data ?? res)?.data ?? res?.data ?? []).map(normalize)
        options.value = list

        // keep selected visible if not in list
        if (selectedOption.value && !list.find(o => o.id === selectedOption.value!.id)) {
            options.value.unshift(selectedOption.value!)
        }
    } catch (e) {
        console.error('Error fetching options:', e)
        options.value = []
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <BaseField v-bind="props" :error="error" v-model="model">
        <template #default>
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
                                Select {{ modelNameSingular }}...
                            </template>
                            <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
                        </Button>
                    </ComboboxTrigger>
                </ComboboxAnchor>

                <ComboboxList class="w-[300px]">
                    <ComboboxInput
                        :placeholder="'Select ' + modelNameSingular + '...'"
                        v-model="searchQuery"
                    />

                    <ComboboxEmpty>No {{ modelNameSingular }} found.</ComboboxEmpty>

                    <ComboboxGroup>
                        <ComboboxItem
                            v-for="item in options"
                            :key="item.id"
                            :value="item"
                        >
                            <Avatar class="size-5">
                                <AvatarImage :src="`https://github.com/${item.name}.png`" />
                                <AvatarFallback>{{ item.name[0] }}</AvatarFallback>
                            </Avatar>
                            {{ item.name }}
                            <ComboboxItemIndicator>
                                <Check />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>

                    <ComboboxSeparator />

                    <ComboboxGroup>
                        <ComboboxItem :value="null">
                            <PlusCircleIcon />
                            Create {{ modelNameSingular }}
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>
        </template>
    </BaseField>
</template>
