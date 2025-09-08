<script setup lang="ts">

import {Pagination, PaginationContent, PaginationFirst, PaginationPrevious, PaginationItem, PaginationNext, PaginationLast, PaginationEllipsis} from "@/components/ui/pagination";
import {Button} from "@/components/ui/button";
import {ref} from "vue";

// props
const { total, perPage, page } = defineProps<{
    total: number
    perPage: number
    page?: number
}>()

// emits
const emit = defineEmits<{
    (e: 'update:page', value: number): void
}>()

const currentPage = ref(page ?? 1)
const lastPage = Math.ceil(total / perPage)

</script>

<template>
    <div class="flex justify-between mt-4 items-center">
        <Pagination
            :total="total"
            :items-per-page="perPage"
            :default-page="page"
            :sibling-count="1"
            :show-edges="true"
            @update:page="(p) => { emit('update:page', p) }"
        >
            <PaginationContent v-slot="{ items }" class="flex space-x-1">
                <PaginationFirst as-child>
                    <Button size="sm" :disabled="page === 1">« First</Button>
                </PaginationFirst>

                <PaginationPrevious as-child>
                    <Button size="sm" :disabled="page === 1">‹ Prev</Button>
                </PaginationPrevious>

                <template v-for="item in items" :key="item.type + '-' + item">
                    <PaginationItem v-if="item.type === 'page'" :value="item.value" as-child>
                        <Button
                            size="sm"
                            :variant="item.value === page ? 'default' : 'outline'"
                        >
                            {{ item.value }}
                        </Button>
                    </PaginationItem>
                    <PaginationEllipsis v-else :index="item" />
                </template>

                <PaginationNext as-child>
                    <Button size="sm" :disabled="page === lastPage">Next ›</Button>
                </PaginationNext>

                <PaginationLast as-child>
                    <Button size="sm" :disabled="page === lastPage">Last »</Button>
                </PaginationLast>
            </PaginationContent>
        </Pagination>
    </div>
</template>

<style scoped>

</style>
