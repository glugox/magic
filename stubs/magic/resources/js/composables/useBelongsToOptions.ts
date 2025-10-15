import {onMounted, onUnmounted, ref, watch, unref, Ref} from "vue"
import { useApi } from "@/composables/useApi"
import { useEntityEvents } from "@/composables/useEntityEvents"
import { PaginatedResponse } from "@/types/support"

export interface BelongsToOptionsConfig {
    relationMetadata: {
        apiPath: string
        relatedEntityName: string
        foreignKey?: string
    }
    initialId?: string | null | Ref<string | null>
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
    const hasMore = ref(true)
    const page = ref(1)

    const normalize = cfg.normalize ?? ((d: any) => ({ ...d, id: String(d.id) }))
    const limit = cfg.searchLimit ?? 15

    const initialId = ref(unref(cfg.initialId) ?? null)

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
            options.value = append ? [...options.value, ...data] : data
            hasMore.value = totalItems > options.value.length

            if (selectedOption.value && !options.value.find(o => o.id === selectedOption.value.id)) {
                options.value.unshift(selectedOption.value)
            }
        } finally {
            isLoading.value = false
        }
    }

    const reloadOptions = async (query = "") => {
        page.value = 1
        hasMore.value = true
        await fetchOptions(query, false)
    }

    const loadMore = async () => {
        if (isLoading.value || !hasMore.value) return
        page.value++
        await fetchOptions(searchQuery.value, true)
    }

    const fetchSelected = async (id?: string | null) => {
        const currentId = id ?? initialId.value
        if (!currentId) return
        try {
            const res = await get(`/${cfg.relationMetadata.apiPath}/${currentId}`)
            const record = res?.data ?? res
            if (record) {
                selectedOption.value = normalize(record)
                if (!options.value.find(o => o.id === selectedOption.value.id)) {
                    options.value.unshift(selectedOption.value)
                }
            }
        } catch (e) {
            console.error("Failed to fetch selected record", e)
        }
    }

    const busHandler = (payload: { entity: string; record: any }) => {
        if (payload.entity === cfg.relationMetadata.relatedEntityName) reloadOptions()
    }

    onMounted(async () => {
        await fetchSelected()
        await reloadOptions()
        if (cfg.autoRefreshOnCreate) on("created", busHandler)
    })

    onUnmounted(() => {
        if (cfg.autoRefreshOnCreate) off("created", busHandler)
    })

    // 👇 Watch initialId (reactive support)
    watch(
        () => unref(cfg.initialId),
        async (newId, oldId) => {
            if (newId && newId !== oldId) {
                initialId.value = newId
                await fetchSelected(newId)
            } else if (!newId) {
                selectedOption.value = null
            }
        },
        { immediate: false }
    )

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
