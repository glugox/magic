<template>
    <Accordion type="single" collapsible class="mb-3">
        <AccordionItem :value="entity.name" class="border rounded-lg shadow bg-emerald-950/90">
            <AccordionTrigger class="px-4 py-3 text-lg font-semibold text-emerald-200 hover:text-emerald-100">
                <div class="flex items-center gap-2">
                    <Database class="w-5 h-5 text-emerald-400" />
                    <span>{{ entity.name }}</span>
                    <span class="ml-2 text-xs text-emerald-400">
            ({{ entity.relations.length }} relations)
          </span>
                </div>
            </AccordionTrigger>

            <AccordionContent class="px-4 pb-4">

                <!-- Parent Context -->
                <div v-if="parentEntity" class="mb-4 p-3 rounded-lg bg-emerald-900/40">
                    <h3 class="text-sm font-semibold text-emerald-300 mb-2">Parent Context</h3>
                    <div class="text-sm">
                        <div>
                            <strong>Parent:</strong> {{ parentEntity.name }}
                        </div>
                        <div v-if="relation">
                            <strong>Relation:</strong>
                            <Badge variant="outline" class="ml-1 text-emerald-200 border-emerald-400">
                                {{ relation.type }}
                            </Badge>
                            <span class="ml-1 text-emerald-400">({{ relation.relationName }})</span>
                            → <span class="text-emerald-300">{{ relation.relatedEntity().name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="col-span-1">
                        <div class="font-semibold text-emerald-300">Entity Name</div>
                        <div>{{ entity.name }}</div>
                    </div>
                    <div class="col-span-2">
                        <div class="font-semibold text-emerald-300 mb-1">{{controllerName}}</div>
                        <div class="text-emerald-700">App\Http\Controllers\{{ controllerName }}.php</div>
                        <div class="text-emerald-700">resources\js\actions\App\Http\Controllers\{{ controllerName }}.ts</div>
                        <!-- Controller / Form Actions -->
                        <!-- Collapsible: Controller & Routes -->
                        <Accordion type="single" collapsible class="mb-3" default-value="controller">
                            <AccordionItem value="controller">
                                <AccordionTrigger class="text-emerald-300">Controller & Routes</AccordionTrigger>
                                <AccordionContent class="p-3 bg-emerald-900/40 rounded-lg">
                                    <div v-if="controller">
                                        <div class="mb-2 text-sm">
                                            <div class="text-emerald-700">App\Http\Controllers\{{ controllerName }}.php</div>
                                            <div class="text-emerald-700">resources/js/actions/App/Http/Controllers/{{ controllerName }}.ts</div>
                                        </div>
                                        <ul class="space-y-2">
                                            <li
                                                v-for="(info, action) in formActionUrls"
                                                :key="action"
                                                class="flex items-center justify-between px-2 py-1 bg-emerald-950/30 rounded"
                                            >
                                                <!-- Action name -->
                                                <span class="font-medium capitalize text-emerald-200 w-24">{{ action }}</span>

                                                <!-- HTTP method badge + URL -->
                                                <div class="flex items-center space-x-2">
                                                      <span
                                                          class="text-[10px] px-2 py-0.5 rounded-full font-semibold uppercase"
                                                          :class="{
                                                          'bg-green-700 text-green-100': info?.method === 'get',
                                                          'bg-blue-700 text-blue-100': info?.method === 'post',
                                                          'bg-yellow-600 text-yellow-100': info?.method === 'put' || info?.method === 'patch',
                                                          'bg-red-700 text-red-100': info?.method === 'delete',
                                                        }"
                                                      >
                                                        {{ info?.method ?? 'N/A' }}
                                                      </span>
                                                    <span class="text-xs text-emerald-400 font-mono">{{ info?.url ?? 'N/A' }}</span>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-emerald-400 text-sm">No controller defined</div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>
                </div>

                <!-- Collapsible: Fields -->
                <Accordion type="single" collapsible class="mb-3">
                    <AccordionItem value="fields">
                        <AccordionTrigger class="text-emerald-300">Fields ({{entity.fields.length}})</AccordionTrigger>
                        <AccordionContent class="p-3 bg-emerald-900/40 rounded-lg">
                            <ul class="divide-y divide-emerald-800">
                                <li v-for="field in entity.fields" :key="field.name" class="py-2">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-medium text-emerald-200">{{ field.label }}</span>
                                            <span class="ml-2 text-xs text-emerald-400">({{ field.type }})</span>
                                        </div>
                                        <Badge
                                            v-if="field.required"
                                            class="bg-red-700 text-red-100"
                                        >required</Badge
                                        >
                                    </div>
                                    <div v-if="field.rules?.create" class="text-xs text-emerald-500 mt-1">
                                        Rules: {{ field.rules.create.join(", ") }}
                                    </div>
                                </li>
                            </ul>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <!-- Relations -->
                <!-- Collapsible: Relations -->
                <Accordion type="single" collapsible class="mb-3">
                    <AccordionItem value="relations">
                        <AccordionTrigger class="text-emerald-300">
                            Relations ({{ entity.relations.length }})
                        </AccordionTrigger>
                        <AccordionContent class="p-3 bg-emerald-900/40 rounded-lg">
                            <ul class="space-y-2">
                                <li
                                    v-for="relation in entity.relations"
                                    :key="relation.relationName"
                                    class="flex items-center justify-between bg-emerald-950/30 px-3 py-2 rounded-lg"
                                >
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ relation.relationName }}</span>
                                        <span class="text-xs text-emerald-400">→ {{ relation.relatedEntityName }}</span>
                                    </div>
                                    <Badge variant="outline" class="text-emerald-200 border-emerald-400">
                                        {{ relation.type }}
                                    </Badge>
                                </li>
                            </ul>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <!-- Debug Info -->
                <Accordion type="single" collapsible class="mt-6">
                    <AccordionItem value="raw-data">
                        <AccordionTrigger class="text-emerald-300">Raw Data</AccordionTrigger>
                        <AccordionContent>
                          <pre class="text-xs bg-emerald-950/70 p-2 rounded-lg overflow-auto">
                            {{ entity }}
                          </pre>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </AccordionContent>
        </AccordionItem>
    </Accordion>
</template>

<script lang="ts" setup>
import { computed } from "vue"
import { usePage } from "@inertiajs/vue3"
import { Badge } from "@/components/ui/badge"
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from "@/components/ui/accordion"
import { Database } from "lucide-vue-next"

import { useEntityContext } from "@/composables/useEntityContext"
import type { ResourceFormProps } from "@/types/support"

const props = defineProps<ResourceFormProps>()

const page = usePage()
const currentRoute = computed(() => page.url)
const pageProps = computed(() => page.props as Record<string, unknown>)

const { relation, controller, controllerName, formActionUrls } = useEntityContext(
    props.entity,
    props.parentEntity,
    props.parentId,
    props.item
)

const controllerInfo = computed(() => {
    if (controller.value) {
        return Object.keys(controller.value).join(", ")
    }
    return "No controller defined"
})
</script>
