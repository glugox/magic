import { ref, computed } from 'vue'

export function useFilter<T extends Record<string, any>>(initialValues: T) {
    // State
    const values = ref({ ...initialValues })

    // Computed: is any value set?
    const isActive = computed(() => {
        return Object.values(values.value).some(v => v !== undefined && v !== '' && v !== null)
    })

    // Reset all values
    function resetFilter() {
        values.value = { ...initialValues }
    }

    return {
        values,
        isActive,
        resetFilter,
    }
}
