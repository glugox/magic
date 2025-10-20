<script setup lang="ts">
import { computed } from "vue"
import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Ellipsis } from "lucide-vue-next"
import type { EntityAction } from "@/types/support"

const props = defineProps<{
    actions: EntityAction[]
    disabled?: boolean
    selectedCount?: number
}>()

const emit = defineEmits<{
    (e: "action", action: EntityAction): void
}>()

const visibleActions = computed(() => props.actions ?? [])
const hasActions = computed(() => visibleActions.value.length > 0)

function formatLabel(action: EntityAction): string {
    if (action.label) {
        return action.label
    }

    return action.name
        .replace(/[-_]+/g, " ")
        .replace(/(^|\s)\w/g, (match) => match.toUpperCase())
}

function onActionClick(action: EntityAction) {
    emit("action", action)
}
</script>

<template>
    <DropdownMenu v-if="hasActions">
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                size="icon"
                :disabled="disabled"
            >
                <Ellipsis class="h-4 w-4" />
                <span class="sr-only">Open actions</span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            class="w-60"
            align="end"
            side="bottom"
        >
            <DropdownMenuLabel class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide">
                <span>Actions</span>
                <span v-if="(selectedCount ?? 0) > 0" class="text-muted-foreground">
                    {{ selectedCount }} selected
                </span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                v-for="action in visibleActions"
                :key="action.name"
                class="flex flex-col items-start space-y-0.5"
                @click="() => onActionClick(action)"
            >
                <span class="font-medium">{{ formatLabel(action) }}</span>
                <span v-if="action.description" class="text-xs text-muted-foreground">
                    {{ action.description }}
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
