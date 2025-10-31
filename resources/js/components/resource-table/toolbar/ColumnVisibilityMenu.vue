<script setup lang="ts">

import {ArrowDownWideNarrow} from "lucide-vue-next";

const props = defineProps<{
    columns?: Column[],
    visibleColumns?: string[]
}>()

const emit = defineEmits<{
    (e: "toggle-column", columnId: string): void
}>()

import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent, DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {Column} from "@glugox/module/types/support";

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline">
                Columns <ArrowDownWideNarrow class="ml-2 h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56">
            <DropdownMenuCheckboxItem
                :model-value="visibleColumns?.includes(col.name)"
                v-for="col in props.columns"
                :key="col.name"
                class="flex items-center gap-2"
                @click="emit('toggle-column', col.name)"
            >
                {{ col.label }}
            </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
