<script setup lang="ts">
import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuShortcut,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

// icons
import { Pencil, Trash2, Copy, Archive } from "lucide-vue-next"

// Define action wit argument of the action type. Define 'action' event emitter
const emit = defineEmits<{
    (e: 'action', action: 'edit' | 'delete' | 'archive'): void
}>()

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline">
                ...
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            class="w-56"
            align="end"
            side="bottom"
        >
            <DropdownMenuGroup>
                <DropdownMenuItem @click="emit('action', 'edit')">
                    <Pencil class="mr-2 h-4 w-4" />
                    <span>Edit</span>
                    <DropdownMenuShortcut>⇧⌘P</DropdownMenuShortcut>
                </DropdownMenuItem>

                <DropdownMenuItem @click="emit('action', 'delete')">
                    <Trash2 class="mr-2 h-4 w-4 text-red-600" />
                    <span>Delete</span>
                    <DropdownMenuShortcut>⌘B</DropdownMenuShortcut>
                </DropdownMenuItem>

                <!-- Submenu -->
                <DropdownMenuSub>
                    <DropdownMenuSubTrigger>
                        More Actions
                    </DropdownMenuSubTrigger>
                    <DropdownMenuSubContent
                        align="end"
                        side="left"
                    >
                        <DropdownMenuItem>
                            <Copy class="mr-2 h-4 w-4" />
                            <span>Duplicate</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                            <Archive class="mr-2 h-4 w-4" />
                            <span>Archive</span>
                        </DropdownMenuItem>
                    </DropdownMenuSubContent>
                </DropdownMenuSub>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
