import { onMounted, onUnmounted, ref } from "vue"
import { useApi } from "@/composables/useApi"
import { useEntityEvents } from "@/composables/useEntityEvents"
import {PaginatedResponse} from "@/types/support";

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

/**
 * Composable to manage options for a belongs-to relationship with search and pagination.
 *
 * @param cfg Configuration for the belongs-to options.
 * @returns Reactive references and methods to manage the options.
 */
export function useBelongsToOptions(cfg: BelongsToOptionsConfig) {
    const { get } = useApi()
    const { on, off } = useEntityEvents()

    const options = ref<any[]>([])
    const selectedOption = ref<any | null>(null)
    const searchQuery = ref("")
    const isLoading = ref(false)
    const hasMore = ref(true)
    const page = ref(1)

    const normalize = cfg.normalize ?? ((d: any) => ({ ...d, id: String(d.id) }))
    const limit = cfg.searchLimit ?? 15

    /** Fetch page (append or replace) */
    const fetchOptions = async (query = "", append = false) => {
        if (isLoading.value || (!hasMore.value && append)) return
        isLoading.value = true

        try {

            const res = await get(`/${cfg.relationMetadata.apiPath}`, {
                search: query,
                limit: String(limit),
                page: String(page.value),
            }) as PaginatedResponse<any>

            const totalItems = res?.meta?.total ?? 0
            const data = ((res?.data ?? []) ?? []).map(normalize)

            // Append or replace options
            options.value = append ? [...options.value, ...data] : data

            // Determine if more pages are available
            hasMore.value = totalItems > options.value.length

            // Keep selected visible
            if (
                selectedOption.value &&
                !options.value.find(o => o.id === selectedOption.value.id)
            ) {
                options.value.unshift(selectedOption.value)
            }
        } finally {
            isLoading.value = false
        }
    }

    /** Reload from page 1 */
    const reloadOptions = async (query = "") => {
        page.value = 1
        hasMore.value = true
        await fetchOptions(query, false)
    }

    /** Load next page */
    const loadMore = async () => {
        if (isLoading.value || !hasMore.value) return
        page.value++
        await fetchOptions(searchQuery.value, true)
    }

    /**
     * Fetch and set the initially selected option based on initialId.
     */
    const fetchSelected = async () => {
        if (!cfg.initialId) return
        try {
            const res = await get(`/${cfg.relationMetadata.apiPath}/${cfg.initialId}`)
            const record = res?.data ?? res
            if (record) {
                selectedOption.value = normalize(record)
                if (!options.value.find(o => o.id === selectedOption.value.id)) {
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
            reloadOptions()
        }
    }

    onMounted(async () => {
        await fetchSelected()
        await reloadOptions()
        if (cfg.autoRefreshOnCreate) on("created", busHandler)
    })

    onUnmounted(() => {
        if (cfg.autoRefreshOnCreate) off("created", busHandler)
    })

    const searchOptions = async (term: string) => {
        searchQuery.value = term
        await reloadOptions(term)
    }

    return {
        options,
        selectedOption,
        searchQuery,
        isLoading,
        hasMore,
        loadMore,
        reloadOptions,
        searchOptions,
    }
}
