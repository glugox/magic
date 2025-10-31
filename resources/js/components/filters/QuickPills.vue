<script lang="ts">
import {defineComponent} from 'vue'
import {Badge} from "@/components/ui/badge";

export default defineComponent({
    name: "QuickPills",
    components: {Badge}
})
</script>

<template>
    <!-- Quick Pills -->
    <div class="flex flex-wrap gap-2">
        <Badge variant="secondary" class="cursor-pointer hover:shadow-sm transition bg-emerald-50 border border-emerald-300 text-emerald-800">
            Active Users ✕
        </Badge>
        <Badge variant="secondary" class="cursor-pointer hover:shadow-sm transition">
            Admins
        </Badge>
        <Badge variant="secondary" class="cursor-pointer hover:shadow-sm transition bg-emerald-50 border border-emerald-300 text-emerald-800">
            With Orders ✕
        </Badge>
        <Badge variant="secondary" class="cursor-pointer hover:shadow-sm transition">
            With Shipments
        </Badge>
    </div>
</template>

<style scoped>

</style>
