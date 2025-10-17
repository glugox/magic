import { computed, isRef, onMounted, ref, unref, watch } from "vue";
import { useApi } from "@/composables/useApi";
import { ApiResourceData, ResourceBaseProps, ResourceData } from "@/types/support";
import { InertiaForm } from "@inertiajs/vue3";
import { createLogger } from "@/lib/logger";

/**
 * Universal data loader for ResourceForm.
 * Can take all props directly from the form and decide when to load.
 */
export function useEntityLoader(props: ResourceBaseProps, form?: InertiaForm<ResourceData>) {
    const { get } = useApi();

    // Dedicated logger for this instance
    const _logger = createLogger(
        'useEntityLoader',
        [props.entity.singularName, unref(props.id)])
    ;
    _logger.log('init');

    // Support id as ref or static value
    const id = computed(() => {
        const rawId = isRef((props as any).id) ? (props as any).id.value : (props as any).id;
        const rawItem = isRef((props as any).item) ? (props as any).item.value : (props as any).item;
        return rawId ?? rawItem?.id ?? null;
    });

    _logger.log('init', `resolved ID: ${id.value}`);

    const shouldLoadById = computed(() => {
        if (!id.value) return false;
        if (!props.item) return true;
        const keys = Object.keys(props.item);
        return keys.length === 1 && keys[0] === "id";
    });

    const record = ref<ApiResourceData | null>({ data: props.item as ResourceData });
    const loading = ref(false);
    const loaded = ref(false);
    const error = ref<string | null>(null);

    const buildUrl = (): string | null => {
        if (!id.value) return null;
        return props.entity.controller.show(id.value).url;
    };

    // 🚀 Load function
    async function load(forceId?: number | string) {

        // Manage loading states
        if (loading.value) {
            _logger.log('load', `skipped (already loading)`);
            return;
        }
        if (loaded.value && !forceId) {
            _logger.log('load', `skipped (already loaded)`);
            return;
        }
        loaded.value = false;

        // Determine target ID and URL
        const targetId = forceId ?? id.value;
        const url = buildUrl();

        _logger.log('load', `starting fetch`, {
            data: { url, targetId },
        });

        if (!url || !targetId) {
            _logger.log('load', `skipped (no URL or ID)`, {
                level: 'warn',
                data: { url, targetId },
            });
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const apiResponse = await get<ApiResourceData>(url);
            console.log('API Response in useEntityLoader:', apiResponse); // Debug log
            if (!apiResponse.success) {
                _logger.log('error', `failed to load`, {
                    level: 'error',
                    data: apiResponse,
                });
            } else {
                record.value = apiResponse.content ?? null;
                _logger.log('success', `loaded successfully`, {
                    data: apiResponse.content,
                });
            }
            loaded.value = true;
        } catch (e: any) {
            error.value = e?.message ?? "Failed to load data";
            loaded.value = false;
            _logger.log('error', `${error.value}`, {
                level: 'error',
                data: e,
            });
        } finally {
            loading.value = false;
        }
    }

    // 🔁 Watch for id changes
    watch(id, (newId, oldId) => {
        if (newId && newId !== oldId && shouldLoadById.value) {
            _logger.log('watch', `ID changed: ${oldId} → ${newId}`);
            load(newId);
        }
    });

    if (form) {
        // Sync when record is fetched
        watch(record, (val) => {
            if (!val) return;
            Object.keys(val).forEach((key) => {
                form[key] = (val as any)[key];
            });
        });
    }

    // Automatically load if necessary
    onMounted(() => {
        if (shouldLoadById.value) {
            _logger.log('mounted', `auto-loading on mount`);
            load();
        }
    });

    return {
        record,
        loading,
        loaded,
        error,
        load,
        shouldLoadById,
        localId: id,
    };
}
