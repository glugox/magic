<script setup lang="ts">
import { Check, ChevronDownIcon, ChevronsUpDown, PlusCircleIcon } from 'lucide-vue-next'
import {computed, onMounted, ref, watch} from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxSeparator, ComboboxTrigger, ComboboxViewport } from '@/components/ui/combobox'
import {CrudActionType, Entity, Field} from "@/types/support";
import BaseField from "@/components/form/BaseField.vue";
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
const { get } = useApi()
const model = ref(props.modelValue)
const selectedOption = ref<Option | null>(null)
const searchQuery = ref('')
const isLoading = ref(false)
const options = ref<Option[]>([])
// Get relation metadata
const relationMetadata = props.entityMeta.relations.find(r => r.foreignKey === props.field.name)

onMounted(async () => {
    // Fetch initial options
    await fetchOptions()

    // Set initial selection if modelValue exists
    if (model.value && options.value.length > 0) {
        selectedOption.value = options.value.find(opt => opt.value === model.value) || null
    }
})

// Debounced search
let searchTimeout: any = null
watch(searchQuery, async (query) => {
    clearTimeout(searchTimeout)

    console.log("Search query:", query)

    if (query && query.length > 1) {
        searchTimeout = setTimeout(async () => {
            await fetchOptions(query)
        }, 300)
    } else if (!query) {
        // Reset to initial options when search is cleared
        await fetchOptions()
    }
})

// Filter options based on search query
const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return options.value
    }

    const query = searchQuery.value.toLowerCase()
    return options.value.filter(option =>
        option.label.toLowerCase().includes(query) ||
        (option.value && option.value.toString().toLowerCase().includes(query))
    )
})

const fetchOptions = async (query: string = '') => {
    isLoading.value = true
    try {
        const response = await get(`/${relationMetadata?.relationName}`, {
            search: query,
            limit: '5'
        })

        const data = response.data || []
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

</script>

<template>
    <BaseField v-bind="props" :error="error" v-model="model">
        <template #default="{ validate }">
            <Combobox  v-model="selectedOption" by="name">
                <ComboboxAnchor class="w-[300px]" as-child>
                    <ComboboxTrigger  as-child>
                        <Button variant="outline" class="justify-between">
                            <template v-if="selectedOption">
                                <div class="flex items-center gap-2">
                                    <Avatar class="size-5">
                                        <AvatarImage
                                            :src="`https://github.com/${selectedOption.name}.png`"
                                        />
                                        <AvatarFallback>{{ selectedOption.name[0] }}</AvatarFallback>
                                    </Avatar>
                                    {{ selectedOption.name }}
                                </div>
                            </template>
                            <template v-else>
                                Select user...
                            </template>

                            <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
                        </Button>
                    </ComboboxTrigger>
                </ComboboxAnchor>

                <ComboboxList class="w-[300px]">
                    <ComboboxInput placeholder="Select user..." v-model="searchQuery" />

                    <ComboboxEmpty>
                        No user found.
                    </ComboboxEmpty>

                    <ComboboxGroup>
                        <ComboboxItem
                            v-for="user in filteredOptions"
                            :key="user.id"
                            :value="user"
                        >
                            <Avatar class="size-5">
                                <AvatarImage
                                    :src="`https://github.com/${user.name}.png`"
                                />
                                <AvatarFallback>{{ user.name[0] }}</AvatarFallback>
                            </Avatar>
                            {{ user.name }}

                            <ComboboxItemIndicator>
                                <Check />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                    <ComboboxSeparator />
                    <ComboboxGroup>
                        <ComboboxItem :value="null">
                            <PlusCircleIcon />
                            Create user
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>
        </template>
    </BaseField>
</template>
