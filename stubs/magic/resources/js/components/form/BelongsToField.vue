<script setup lang="ts">
import {ref, watch, onMounted, onUnmounted} from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxSeparator, ComboboxTrigger } from '@/components/ui/combobox';
import BaseField from './BaseField.vue';
import { useApi } from '@/composables/useApi';
import { FormFieldProps, Relation } from '@/types/support';
import { Link } from '@inertiajs/vue3';
import { useEntityEvents } from '@/composables/useEntityEvents';
import { Check, ChevronsUpDown, PlusCircleIcon } from 'lucide-vue-next';

interface Option { id: string; name: string; [key: string]: any }

const props = defineProps<FormFieldProps>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'openRelated', entry: Relation): void;
}>();

const { get } = useApi();
const { on, off } = useEntityEvents();

const model = ref<string | null>(props.modelValue ? String(props.modelValue) : null);
const selectedOption = ref<Option | null>(null);
const options = ref<Option[]>([]);
const searchQuery = ref('');
const isLoading = ref(false);

// Get relation metadata
const relationMetadata = props.entity.relations.find(r => r.foreignKey === props.field.name)!;
const modelNameSingular = relationMetadata.relatedEntityName;

// Normalize option
const normalize = (d: any) => ({ ...d, id: String(d.id) });

// Fetch options
const fetchOptions = async (query = '') => {
    isLoading.value = true;
    try {
        const res = await get(`/${relationMetadata.apiPath}`, { search: query, limit: '5' });
        const list = ((res?.data ?? res)?.data ?? res?.data ?? []).map(normalize);
        options.value = list;

        // Keep selected visible
        if (selectedOption.value && !list.find(o => o.id === selectedOption.value!.id)) {
            options.value.unshift(selectedOption.value!);
        }
    } catch (e) {
        console.error(e);
        options.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Load initial record
onMounted(async () => {
    if (model.value) {
        const res = await get(`/${relationMetadata.apiPath}/${model.value}`);
        const record = res?.data ?? res;
        if (record) {
            selectedOption.value = normalize(record);
            if(selectedOption.value) {
                options.value.unshift(selectedOption.value);
            }
        }
    }

    // Listen for created events to refresh options
    const busHandler = (payload: { entity: string; record: any }) => {
        if (payload.entity === relationMetadata.relatedEntityName) {
            fetchOptions().then(() => {
                selectedOption.value = normalize(payload.record.data);
                if (!options.value.find(o => o.id === selectedOption.value!.id)) {
                    options.value.unshift(selectedOption.value!);
                }
            });
        }
    };
    on('created', busHandler);
    fetchOptions();

    onUnmounted(() => off('created', busHandler));
});

// Sync model with external changes,
// also needed for reset from parent
watch(
    () => props.modelValue,
    (val) => {
        if (!val) {
            model.value = null;
            selectedOption.value = null;
        } else {
            model.value = String(val);
        }
    }
);
// Sync selectedOption with model
watch(selectedOption, val => {
    model.value = val?.id ?? null;
    emit('update:modelValue', model.value);
});

// Debounced search
let searchTimeout: any;
watch(searchQuery, query => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchOptions(query), 300);
});

// Emit open related
const emitOpenRelatedForm = () => emit('openRelated', relationMetadata);

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
