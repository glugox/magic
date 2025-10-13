import {computed, onMounted, ref, watch} from "vue";
import {useApi} from "@/composables/useApi";
import {ApiResourceData, ResourceBaseProps, ResourceData} from "@/types/support";
import {InertiaForm} from "@inertiajs/vue3";

/**
 * Universal data loader for ResourceForm.
 * Can take all props directly from the form and decide when to load.
 */
export function useEntityLoader(props: ResourceBaseProps, form?: InertiaForm<ResourceData>) {
    const { get } = useApi();

    //  Compute the effective ID
    const id = computed<number | string | null>(() => props.id ?? props.item?.id ?? null);

    //  Decide whether we should load the data from API
    const shouldLoadById = computed(() => {
        if (!id.value) return false;

        // No item provided at all → load
        if (!props.item) return true;

        // Item has only "id" key → load
        const keys = Object.keys(props.item);
        if (keys.length === 1 && keys[0] === "id") return true;

        // Otherwise → already loaded
        return false;
    });

    // 🔗 Reactive state
    const record = ref<ApiResourceData | null>({
        data: props.item as ResourceData,
    });
    const loading = ref(false);
    const error = ref<string | null>(null);

    //  Build URL from entity controller
    const buildUrl = (): string | null => {
        if (!id.value) return null;
        return props.entity.controller.show(id.value).url;
    };

    // 🚀 Load function
    async function load(forceId?: number | string) {
        const targetId = forceId ?? id.value;
        const url = buildUrl();

        console.log("useEntityLoader loading", { url, targetId });

        if (!url || !targetId) return;

        loading.value = true;
        error.value = null;

        try {
            record.value = await get(url);
        } catch (e: any) {
            error.value = e?.message ?? "Failed to load data";
            console.error("useEntityLoader", e);
        } finally {
            loading.value = false;
        }
    }

    // 🔁 Watch for id changes
    watch(id, (newId, oldId) => {
        if (newId && newId !== oldId && shouldLoadById.value) {
            load(newId);
        }
    });


    if(form) {
        // Sync when record is fetched
        watch(record, (val) => {
            if (!val) return;
            Object.keys(form.data()).forEach((key) => {
                if (val.data && val.data[key] !== undefined) form[key] = val.data[key];
            });
        });
    }


    // 🧩 Automatically load if necessary
    onMounted(() => {
        if (shouldLoadById.value) load();
    });

    return {
        record,
        loading,
        error,
        load,
        shouldLoadById,
    };
}
