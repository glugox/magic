// composables/useBelongsToOptions.ts
import {onMounted, onUnmounted, ref, watch} from "vue"
import {useApi} from "@/composables/useApi"
import {useEntityEvents} from "@/composables/useEntityEvents"

export interface BelongsToOptionsConfig {
    relationMetadata: {
        apiPath: string
        relatedEntityName: string
        foreignKey?: string
    }
    initialId?: string | null
    normalize?: (data: any) => any
    autoRefreshOnCreate?: boolean
    searchLimit?: number
}

export function useBelongsToOptions(cfg: BelongsToOptionsConfig) {
    const { get } = useApi()
    const { on, off } = useEntityEvents()

    const options = ref<any[]>([])
    const selectedOption = ref<any | null>(null)
    const searchQuery = ref("")
    const isLoading = ref(false)

    const normalize = cfg.normalize ?? ((d: any) => ({ ...d, id: String(d.id) }))

    // Fetch list
    const fetchOptions = async (query = "") => {
        isLoading.value = true
        try {


            const res = await get(`/${cfg.relationMetadata.apiPath}`, {
                search: query,
                limit: String(cfg.searchLimit ?? 10),
            })
            options.value = ((res?.data ?? []) ?? []).map(normalize)

            // Keep selected visible
            if (
                selectedOption.value &&
                !options.value.find((o) => o.id === selectedOption.value.id)
            ) {
                options.value.unshift(selectedOption.value)
            }
        } finally {
            isLoading.value = false
        }
    }

    // Fetch single if initial ID provided
    const fetchSelected = async () => {
        if (!cfg.initialId) return
        try {
            const res = await get(`/${cfg.relationMetadata.apiPath}/${cfg.initialId}`)
            const record = res?.data ?? res
            if (record) {
                selectedOption.value = normalize(record)
                if (!options.value.find((o) => o.id === selectedOption.value.id)) {
                    options.value.unshift(selectedOption.value)
                }
            }
        } catch (e) {
            console.error(e)
        }
    }

    // Entity bus refresh
    const busHandler = (payload: { entity: string; record: any }) => {
        if (payload.entity === cfg.relationMetadata.relatedEntityName) {
            fetchOptions().then(() => {
                const newRec = normalize(payload.record.data)
                selectedOption.value = newRec
                if (!options.value.find((o) => o.id === newRec.id)) {
                    options.value.unshift(newRec)
                }
            })
        }
    }

    onMounted(async () => {
        await fetchSelected()
        await fetchOptions()
        if (cfg.autoRefreshOnCreate) on("created", busHandler)
    })

    onUnmounted(() => {
        if (cfg.autoRefreshOnCreate) off("created", busHandler)
    })

    // Debounced search
    let timeout: any
    watch(searchQuery, (q) => {
        clearTimeout(timeout)
        timeout = setTimeout(() => fetchOptions(q), 300)
    })

    return {
        options,
        selectedOption,
        searchQuery,
        isLoading,
        fetchOptions,
    }
}


