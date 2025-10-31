<script setup lang="ts">
import { computed, ref, watch } from "vue"
import { CalendarIcon, ClockIcon } from "lucide-vue-next"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Button } from "@/components/ui/button"
import BaseField from "./BaseField.vue"
import { cn } from "@glugox/module/lib/utils"
import { Calendar } from "@/components/ui/calendar"
import InputField from "./InputField.vue"
import { FormFieldEmits, FormFieldProps } from "@glugox/module/types/support"

import { CalendarDate, CalendarDateTime, DateFormatter } from "@internationalized/date"

const props = defineProps<FormFieldProps>()
const emit = defineEmits<FormFieldEmits>()

// ----------------------
// Internal state
// ----------------------
const calendarDate = ref<CalendarDate | null>(null)
const hours = ref<number>(0)
const minutes = ref<number>(0)

// Init from prop
if (props.modelValue) {
    const d = new Date(props.modelValue)
    calendarDate.value = new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate())
    hours.value = d.getHours()
    minutes.value = d.getMinutes()
}

const popoverOpen = ref(false)

const formatter = new DateFormatter("en-US", {
    dateStyle: "medium",
    timeStyle: "short"
})

// Combined CalendarDateTime → JS Date
const jsDate = computed(() => {
    if (!calendarDate.value) return null
    return new Date(
        calendarDate.value.year,
        calendarDate.value.month - 1,
        calendarDate.value.day,
        hours.value,
        minutes.value
    )
})

// Emit when things change
watch([calendarDate, hours, minutes], () => {
    if (!calendarDate.value) {
        emit("update:modelValue", null)
        return
    }
    emit("update:modelValue", jsDate.value?.toISOString() ?? null)
})

watch(
    () => props.modelValue,
    (newVal) => {
        if (!newVal) {
            calendarDate.value = null
            hours.value = 0
            minutes.value = 0
            return
        }
        const d = new Date(newVal)
        if (isNaN(d.getTime())) return // guard invalid dates
        calendarDate.value = new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate())
        hours.value = d.getHours()
        minutes.value = d.getMinutes()
    },
    { immediate: true } // also run on first mount
)
</script>

<template>
    <BaseField v-bind="props">
        <template #default>
            <Popover v-model:open="popoverOpen">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        :class="cn('w-full justify-start text-left font-normal', !jsDate && 'text-muted-foreground')"
                    >
                        <CalendarIcon class="mr-2 h-4 w-4" />
                        <ClockIcon class="mr-2 h-4 w-4" />
                        {{ jsDate ? formatter.format(jsDate) : "Pick date & time" }}
                    </Button>
                </PopoverTrigger>

                <PopoverContent class="w-auto p-0 space-y-2">
                    <!-- Date calendar (CalendarDate only!) -->
                    <Calendar v-model="calendarDate" />

                    <!-- Time inputs -->
                    <div class="p-2 flex gap-2 items-center">
                        <div class="flex items-center flex-col">
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-1" for="dt-hours">
                                Hours
                            </label>
                            <InputField
                                type="number"
                                name="dt-hours"
                                v-model="hours"
                                min="0"
                                max="23"
                                class="w-14"
                            />
                        </div>
                        <div class="flex items-center flex-col">
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-1" for="dt-minutes">
                                Minutes
                            </label>
                            <InputField
                                type="number"
                                name="dt-minutes"
                                v-model="minutes"
                                min="0"
                                max="59"
                                class="w-14"
                            />
                        </div>
                    </div>
                </PopoverContent>
            </Popover>
        </template>
    </BaseField>
</template>
