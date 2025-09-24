<script setup lang="ts">
import { ref, watch } from "vue"
import { CalendarIcon, ClockIcon } from "lucide-vue-next"
import { Calendar } from "@/components/ui/calendar"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import BaseField from "./BaseField.vue"
import InputField from "./InputField.vue"
import type { FormFieldProps } from "@/types/support"

const props = defineProps<FormFieldProps>()
const emit = defineEmits<{ (e: "update:modelValue", value: string): void }>()

// Local reactive state
const dateValue = ref<Date | null>(props.modelValue ? new Date(props.modelValue) : null)
const popoverOpen = ref(false)

// Emit ISO string whenever date changes
watch(dateValue, (val) => {
    if (val) {
        emit("update:modelValue", val.toISOString())
        popoverOpen.value = false // close popover on selection
    } else {
        emit("update:modelValue", null)
    }
})

// Format display
function formatDisplay(date: Date | null) {
    if (!date) return "Pick date & time"
    return date.toLocaleString("en-US", { dateStyle: "short", timeStyle: "short" })
}
</script>

<template>
    <BaseField v-bind="props">
        <template #default>
            <Popover v-model:open="popoverOpen">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        :class="cn('w-[300px] justify-start text-left font-normal', !dateValue && 'text-muted-foreground')"
                    >
                        <CalendarIcon class="mr-2 h-4 w-4" />
                        <ClockIcon class="mr-2 h-4 w-4" />
                        {{ formatDisplay(dateValue) }}
                    </Button>
                </PopoverTrigger>

                <PopoverContent class="w-auto p-0">
                    <Calendar
                        v-model="dateValue"
                        showTime
                        initial-focus
                    />
                </PopoverContent>
            </Popover>
        </template>
    </BaseField>
</template>
