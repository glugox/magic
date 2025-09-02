<script setup lang="ts">
import { CalendarDate, getLocalTimeZone, toCalendarDate } from "@internationalized/date"
import { DateFormatter } from "@internationalized/date"
import { CalendarIcon } from "lucide-vue-next"

import { ref, watch } from "vue"
import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import BaseField from "./BaseField.vue"
import { Field, CrudActionType } from "@/types/support"
import { cn } from "@/lib/utils"

interface Props {
    error?: string
    field: Field
    crudActionType: CrudActionType
    modelValue?: string | Date | null
}
const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

// Formatter for display
const df = new DateFormatter("en-US", { dateStyle: "long" })

// --- Convert string/Date to CalendarDate ---
function toCalendarDateSafe(input: string | Date | null): CalendarDate | undefined {
    if (!input) return undefined
    const d = input instanceof Date ? input : new Date(input.replace(/\.\d+Z$/, 'Z'))
    if (isNaN(d.getTime())) return undefined
    return new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

// --- Local CalendarDate reactive value ---
const value = ref<CalendarDate | undefined>(toCalendarDateSafe(props.modelValue))

// --- Emit ISO string whenever CalendarDate changes ---
watch(value, (val) => {
    if (!val) {
        emit('update:modelValue', null)
    } else {
        const jsDate = new Date(val.year, val.month - 1, val.day)
        const sqlDate = jsDate.toISOString().slice(0, 19).replace('T', ' ')
        emit('update:modelValue', sqlDate)
    }
})
</script>

<template>
    <BaseField :field="field" :error="error" :model-value="modelValue" :crud-action-type="crudActionType">
        <template #default>
            <Popover>
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
