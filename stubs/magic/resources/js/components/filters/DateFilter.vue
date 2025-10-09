<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" />
        <Label>{{ label }}</Label>

        <div class="flex gap-2 mt-2">
            <!-- From Date -->
            <Popover v-model:open="openMin">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        :class="cn(
              'flex-1 justify-start text-left font-normal',
              !localValue.min && 'text-muted-foreground'
            )"
                    >
                        <CalendarIcon class="mr-2 h-4 w-4" />
                        {{ localValue.min ? df.format(toJsDate(localValue.min)) : "From" }}
                    </Button>
                </PopoverTrigger>

                <PopoverContent class="w-auto p-0">
                    <Calendar v-model="minDate" initial-focus />
                </PopoverContent>
            </Popover>

            <!-- To Date -->
            <Popover v-model:open="openMax">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        :class="cn(
              'flex-1 justify-start text-left font-normal',
              !localValue.max && 'text-muted-foreground'
            )"
                    >
                        <CalendarIcon class="mr-2 h-4 w-4" />
                        {{ localValue.max ? df.format(toJsDate(localValue.max)) : "To" }}
                    </Button>
                </PopoverTrigger>

                <PopoverContent class="w-auto p-0">
                    <Calendar v-model="maxDate" initial-focus />
                </PopoverContent>
            </Popover>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue"
import { CalendarDate, DateFormatter } from "@internationalized/date"
import { CalendarIcon } from "lucide-vue-next"

import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import { Calendar } from "@/components/ui/calendar"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui"
import ResetButton from "@/components/ResetButton.vue"
import { cn } from "@/lib/utils"
import { useFilter } from "@/composables/useFilter"
import type { FilterBaseProps, TableFilterEmits } from "@/types/support"
import { toRef } from "vue"

// props / emits
const props = defineProps<FilterBaseProps>()
const emit = defineEmits<TableFilterEmits>()

// filter logic
const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"),
    (val) => emit("change", val),
    {
        defaultValue: { min: null, max: null },
    }
)

const df = new DateFormatter("en-US", { dateStyle: "long" })

// open states
const openMin = ref(false)
const openMax = ref(false)

// helper to safely convert to CalendarDate
function toCalendarDateSafe(input: string | Date | null): CalendarDate | undefined {
    if (!input) return undefined
    const d = input instanceof Date ? input : new Date(input.replace(/\.\d+Z$/, "Z"))
    if (isNaN(d.getTime())) return undefined
    return new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

// helper for formatting
function toJsDate(input: string | Date) {
    return input instanceof Date ? input : new Date(input)
}

// local reactive CalendarDate refs
const minDate = ref<CalendarDate | undefined>(toCalendarDateSafe(localValue.value.min))
const maxDate = ref<CalendarDate | undefined>(toCalendarDateSafe(localValue.value.max))

// watchers
watch(minDate, (val) => {
    if (!val) localValue.value.min = null
    else {
        const jsDate = new Date(Date.UTC(val.year, val.month - 1, val.day))
        const sqlDate = jsDate.toISOString().slice(0, 19).replace("T", " ")
        localValue.value.min = sqlDate
        openMin.value = false
    }
})

watch(maxDate, (val) => {
    if (!val) localValue.value.max = null
    else {
        const jsDate = new Date(Date.UTC(val.year, val.month - 1, val.day))
        const sqlDate = jsDate.toISOString().slice(0, 19).replace("T", " ")
        localValue.value.max = sqlDate
        openMax.value = false
    }
})
</script>
