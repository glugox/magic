import { ref, watch, Ref } from "vue";
import { deepCopy } from "@/lib/app";

/**
 * A composable for managing filter state in a Vue component.
 *
 * Handles syncing between a parent-provided filter value (via a `ref`),
 * a local editable copy, dirty-state tracking, and change emission.
 */
export function useFilter(
    propsFilterValueRef: Ref<FilterValue | null>, // The filter value passed in from the parent (as a ref)
    emitChange: (val: FilterValue | null) => void, // Callback to notify parent of updates
    options?: { defaultValue?: any }              // Optional config (e.g., a default filter value)
) {
    /**
     * `localValue` is the internal working copy of the filter value.
     *
     * - Initialized as:
     *   1. The parent-provided value (if available), otherwise
     *   2. A deep copy of the `defaultValue` (if given), otherwise
     *   3. `null`.
     *
     * We deep copy so that changes here don’t mutate the parent directly.
     */
    const localValue = ref(
        deepCopy(propsFilterValueRef.value ?? options?.defaultValue ?? null)
    );

    /**
     * Watch for changes in the parent-provided value (`propsFilterValueRef`).
     *
     * - If the parent updates the filter, we sync `localValue`.
     * - If it becomes `null`, we also reset accordingly.
     * - We deep copy `defaultValue` to avoid reference issues.
     */
    watch(
        propsFilterValueRef,
        (newVal) => {
            localValue.value = newVal ?? deepCopy(options?.defaultValue) ?? null;
        }
    );

    /**
     * `isDirty` tracks whether the current filter value differs
     * from its default.
     */
    const isDirty = ref(false);

    /**
     * Watch for changes in `localValue` (the working filter).
     *
     * - Updates `isDirty` whenever `localValue` differs from `defaultValue`.
     *   Comparison is done via `JSON.stringify` for deep equality.
     * - Calls `emitChange` so the parent is always notified of the latest state.
     *
     * `{ deep: true }` ensures nested object changes are tracked.
     */
    watch(
        localValue,
        (val) => {
            isDirty.value =
                val != null &&
                JSON.stringify(val) !== JSON.stringify(options?.defaultValue);

            emitChange(val);
        },
        { deep: true }
    );

    /**
     * Reset function:
     *
     * - Clears the filter back to `null` (not defaultValue).
     * - Emits the reset value (`null`) so the parent can react.
     *
     * If you want reset to go back to `defaultValue` instead,
     * uncomment the `localValue.value = options?.defaultValue ?? null;` line.
     */
    const reset = () => {
        // localValue.value = options?.defaultValue ?? null; // alternative behavior
        emitChange(null);
    };

    /**
     * Return the composable API:
     * - `localValue` → the editable local filter state
     * - `isDirty` → whether the filter differs from default
     * - `reset` → helper to clear/reset the filter
     */
    return { localValue, isDirty, reset };
}
