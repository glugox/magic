import {onMounted, onUnmounted, ref, watch, unref, Ref, computed} from "vue";
import { useApi } from "@/composables/useApi";
import { useEntityEvents } from "@/composables/useEntityEvents";
import { ApiResponse, PaginatedResponse } from "@/types/support";
import { createLogger } from "@/lib/logger";
import {randomId} from "@/lib/app";

export interface BelongsToOptionsConfig {
    relationMetadata: {
        apiPath: string;
        relatedEntityName: string;
        foreignKey?: string;
    };
    initialId?: string | null | Ref<string | null>;
    normalize?: (data: any) => any;
    autoRefreshOnCreate?: boolean;
    searchLimit?: number;
}

export function useBelongsToOptions(cfg: BelongsToOptionsConfig) {

    const _logger = createLogger(
        "useBelongsToOptions",
        [
            cfg.relationMetadata.relatedEntityName ?? "Entity",
            unref(cfg.initialId) ? `#${unref(cfg.initialId)}` : randomId()
        ]
    );
    _logger.log("init", "Composable initialized");

    const { get } = useApi();
    const { on, off } = useEntityEvents();

    const options = ref<any[]>([]);
    const selectedOption = ref<any | null>(null);
    const searchQuery = ref("");
    const isLoading = ref(false);
    const hasMore = ref(true);
    const page = ref(1);

    const normalize = cfg.normalize ?? ((d: any) => ({ ...d, id: String(d.id) }));
    const limit = cfg.searchLimit ?? 15;
    const initialId = ref(unref(cfg.initialId) ?? null);

    const fetchOptions = async (query = "", append = false) => {
        if (isLoading.value || (!hasMore.value && append)) return;
        isLoading.value = true;

        try {
            _logger.log("fetch", "fetching options", {
                data: { query, page: page.value, append, endpoint: cfg.relationMetadata.apiPath},
            });

            const apiResponse = (await get(`/${cfg.relationMetadata.apiPath}`, {
                search: query,
                limit: String(limit),
                page: String(page.value),
            })) as ApiResponse<PaginatedResponse<unknown>>;


            const paginated = apiResponse.content as PaginatedResponse<unknown>;
            const totalItems = paginated?.meta?.total ?? 0;
            const data = ((paginated?.data ?? []) ?? []).map(normalize);

            options.value = append ? [...options.value, ...data] : data;
            hasMore.value = totalItems > options.value.length;

            if (selectedOption.value && !options.value.find(o => o.id === selectedOption.value.id)) {
                options.value.unshift(selectedOption.value);
            }

            _logger.log("success", `fetched ${paginated?.data.length} items`, {
                data: paginated,
            });
        } catch (e) {
            _logger.log("error", `failed to fetch options`, {
                level: "error",
                data: e,
            });
        } finally {
            isLoading.value = false;
        }
    };

    const reloadOptions = async (query = "") => {
        page.value = 1;
        hasMore.value = true;
        await fetchOptions(query, false);
    };

    const loadMore = async () => {
        if (isLoading.value || !hasMore.value) return;
        page.value++;
        await fetchOptions(searchQuery.value, true);
    };

    const fetchSelected = async (id?: string | null) => {
        const currentId = id ?? initialId.value;
        if (!currentId) return;

        try {
            _logger.log("fetchSelected", `fetching selected record`);

            const apiResponse = await get(`/${cfg.relationMetadata.apiPath}/${currentId}`);
            const record = apiResponse.success ? apiResponse.content : null;

            if (record) {
                selectedOption.value = normalize(record);

                if (!options.value.find(o => o.id === selectedOption.value.id)) {
                    options.value.unshift(selectedOption.value);
                }
            }

            _logger.log("success", `selected record loaded`, {
                data: record,
            });
        } catch (e) {
            _logger.log("error", `failed to fetch selected record`, {
                level: "error",
                data: e,
            });
        }
    };

    const busHandler = (payload: { entity: string; record: any }) => {
        if (payload.entity === cfg.relationMetadata.relatedEntityName) {
            _logger.log("event", `detected creation of related entity, reloading options`, {
                data: payload.record,
            });
            reloadOptions().then(r => r);
        }
    };

    onMounted(async () => {
        await fetchSelected();
        await reloadOptions();
        if (cfg.autoRefreshOnCreate) on("created", busHandler);

        _logger.log("mounted", `composable mounted`);
    });

    onUnmounted(() => {
        if (cfg.autoRefreshOnCreate) off("created", busHandler);
        _logger.log("unmounted", `composable unmounted`);
    });

    watch(
        () => unref(cfg.initialId),
        async (newId, oldId) => {
            if (newId && newId !== oldId) {
                initialId.value = newId;
                await fetchSelected(newId);
                _logger.log("watch", `initialId changed: ${oldId} → ${newId}`);
            } else if (!newId) {
                selectedOption.value = null;
                _logger.log("watch", `initialId reset to null`);
            }
        },
        { immediate: false }
    );

    const searchOptions = async (term: string) => {
        searchQuery.value = term;
        await reloadOptions(term);
        _logger.log("search", `searched term "${term}"`);
    };

    return {
        options,
        selectedOption,
        searchQuery,
        isLoading,
        hasMore,
        loadMore,
        reloadOptions,
        searchOptions,
    };
}
