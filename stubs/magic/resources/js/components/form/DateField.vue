<script setup lang="ts">
import { ref, watch } from "vue"
import { CalendarDate, DateFormatter } from "@internationalized/date"
import { CalendarIcon } from "lucide-vue-next"

import BaseField from "./BaseField.vue"
import { Calendar } from "@/components/ui/calendar"
import { Button } from "@/components/ui/button"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import type { FormFieldProps } from "@/types/support"
import { cn } from "@/lib/utils"

const props = defineProps<FormFieldProps>()
const emit = defineEmits<{ (e: "update:modelValue", value: string | null): void }>()

const df = new DateFormatter("en-US", { dateStyle: "long" })

function toCalendarDateSafe(input: string | Date | null): CalendarDate | undefined {
    if (!input) return undefined
    const d = input instanceof Date ? input : new Date(input.replace(/\.\d+Z$/, "Z"))
    if (isNaN(d.getTime())) return undefined
    return new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

const value = ref<CalendarDate | undefined>(toCalendarDateSafe(props.modelValue))

watch(value, (val) => {
    if (!val) {
        emit("update:modelValue", null)
    } else {
        const jsDate = new Date(val.year, val.month - 1, val.day)
        const sqlDate = jsDate.toISOString().slice(0, 19).replace("T", " ")
        emit("update:modelValue", sqlDate)
        popoverOpen.value = false // auto-close popover when a date is selected
    }
})

// Control popover open state
const popoverOpen = ref(false)
</script>

<template>
    <BaseField v-bind="props">
        <template #default>
            <Popover v-model:open="popoverOpen">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        :class="cn(
              'w-[280px] justify-start text-left font-normal',
              !value && 'text-muted-foreground'
            )"
                    >
                        <CalendarIcon class="mr-2 h-4 w-4" />
                        {{ value ? df.format(new Date(value.year, value.month - 1, value.day)) : "Pick a date" }}
                    </Button>
                </PopoverTrigger>

                <PopoverContent class="w-auto p-0">
                    <Calendar v-model="value" initial-focus />
                </PopoverContent>
            </Popover>
        </template>
    </BaseField>
</template>
