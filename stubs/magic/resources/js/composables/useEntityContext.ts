import { ref, computed } from "vue";
import type { Controller, DbId, Entity, Relation } from "@/types/support";

export function useEntityContext(
    entity: Entity,
    parentEntity?: Entity,
    parentId?: DbId,
    item?: Record<string, any>
) {
    const currentEntity = ref(entity);
    const currentParent = ref(parentEntity || null);

    const relation = computed<Relation | null>(
        () => parentEntity?.relations.find((r) => r.relatedEntity().name === entity.name) ?? null
    );

    const controller = computed<Controller>(() => relation.value?.controller ?? entity.controller);

    // Controller class name
    const controllerName = computed(() => {
        return (parentEntity?.name ?? '') + entity.name + 'Controller';
    });

    const controllerStoreArgs = computed(() => (!parentId ? [] : [parentId]));
    const controllerUpdateArgs = computed(() =>
        parentId ? [parentId, item?.id] : {id: item?.id}
    );
    // Compute store URL if possible
    const storeUrl = computed(() => {
        if (controller.value?.store) {
            return controller.value.store(...controllerStoreArgs.value);
        }
        return null;
    });

    // Edit URL is usually the same as update URL
    const editUrl = computed(() => {
        if (controller.value?.edit && item?.id) {
          return controller.value.edit(controllerUpdateArgs.value);
        }
        return null;
    });

    // Compute update URL if possible
    const updateUrl = computed(() => {
        if (controller.value?.update && item?.id) {
            return controller.value.update(controllerUpdateArgs.value);
        }
        return null;
    });
    // Compute destroy URL if possible
    const destroyUrl = computed(() => {
        if (controller.value?.destroy && item?.id) {
            return controller.value.destroy(item.id);
        }
        return null;
    });
    // Compute index URL if possible
    const indexUrl = computed(() => {
        if (controller.value?.index) {
            return controller.value.index(...controllerStoreArgs.value);
        }
        return null;
    });
    // Compute show URL if possible
    const showUrl = computed(() => {
        if (controller.value?.show && item?.id) {
            return controller.value.show(controllerUpdateArgs.value);
        }
        return null;
    });
    // Compute create URL if possible
    const createUrl = computed(() => {
        if (controller.value?.create) {
            return controller.value.create(...controllerStoreArgs.value);
        }
        return null;
    });

    // Bulk destroy URL if possible
    const bulkDestroyUrl = computed(() => {
        if (controller.value?.bulkDestroy) {
            return controller.value.bulkDestroy(...controllerStoreArgs.value);
        }
        return null;
    });

    // Expose “action URLs” for debug
    const formActionUrls = computed(() => {
        const urls: Record<string, string | null> = {};

        if(createUrl.value) urls.create = createUrl.value;
        if(storeUrl.value) urls.store = storeUrl.value;
        if(showUrl.value) urls.show = showUrl.value;
        if(editUrl.value) urls.update = editUrl.value;
        if(editUrl.value) urls.edit = editUrl.value;
        if(destroyUrl.value) urls.destroy = destroyUrl.value;
        if(indexUrl.value) urls.index = indexUrl.value;
        if(bulkDestroyUrl.value) urls.bulkDestroy = bulkDestroyUrl.value;


        return urls;
    });

    const crudActionType = computed(() => (item?.id ? "update" : "create"));

    return {
        entity: currentEntity,
        parentEntity: currentParent,
        relation,
        controller,
        controllerName,
        formActionUrls,
        crudActionType,
        storeUrl,
        updateUrl,
        destroyUrl,
        indexUrl,
        showUrl,
        createUrl,
        bulkDestroyUrl
    };
}
