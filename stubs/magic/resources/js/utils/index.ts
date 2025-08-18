import type { Ref } from "vue"
import type { Updater } from "@tanstack/vue-table"

export function valueUpdater<T>(
    updaterOrValue: Updater<T>,
    refValue: Ref<T>
) {
    if (typeof updaterOrValue === "function") {
        // TanStack Table passes a function updater: (oldValue) => newValue
        refValue.value = (updaterOrValue as (old: T) => T)(refValue.value)
    } else {
        // Direct value passed
        refValue.value = updaterOrValue
    }
}
