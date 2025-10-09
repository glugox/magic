import {ref} from "vue";
import {DataTableState} from "@/types/support";

export function useTableProps(emit: any, initialState?: DataTableState) {
    const visibleColumns = ref(initialState?.settings?.visibleColumns ?? [])


    function toggleColumnVisibility(id: string) {
        if (visibleColumns.value.includes(id)) visibleColumns.value = visibleColumns.value.filter(c => c !== id)
        else visibleColumns.value.push(id)

        console.log(visibleColumns.value)
        emit('update:visibleColumns', visibleColumns.value)
    }

    return {visibleColumns, toggleColumnVisibility}
}
