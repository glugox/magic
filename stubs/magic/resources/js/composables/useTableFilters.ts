import {ref, watch} from "vue";
import {TableFilters} from "@/types/support";

export function useTableFilters(emit: any, initialFilters?: TableFilters) {
    const search = ref(initialFilters?.search ?? "")
    const visibleColumns = ref(initialFilters?.visibleColumns ?? [])
    watch(search, () => emit('update:search', search.value))
    function toggleColumnVisibility(id: string) {
        if (visibleColumns.value.includes(id)) visibleColumns.value = visibleColumns.value.filter(c => c !== id)
        else visibleColumns.value.push(id)

        console.log(visibleColumns.value)
        emit('update:visibleColumns', visibleColumns.value)
    }
    return { search, visibleColumns, toggleColumnVisibility }
}
