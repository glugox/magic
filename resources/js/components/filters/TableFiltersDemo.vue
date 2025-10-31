<template>
    <Accordion type="single" collapsible class="mb-4">
        <AccordionItem
            value="filters"
            class="border rounded-xl shadow-sm bg-inherit border-sidebar-border/70 dark:border-sidebar-border overflow-visible"
        >
            <!-- Closed Topbar -->
            <AccordionTrigger class="px-4 py-2 text-sm font-medium text-foreground hover:text-foreground/80">
                <div class="flex items-center gap-2">
                    <Filter class="w-5 h-5 text-muted-foreground" />
                    <span>Filters</span>
                    <span class="ml-1 text-xs text-muted-foreground">(Adjust view)</span>
                </div>
            </AccordionTrigger>

            <!-- Expanded Filter Content -->
            <AccordionContent class="px-4 py-4 space-y-4 overflow-visible">

                <!-- Quick Pills -->
                <div class="flex flex-wrap gap-2">
                    <Badge
                        variant="secondary"
                        class="cursor-pointer hover:shadow-sm transition bg-emerald-50 border border-emerald-300 text-emerald-800"
                    >
                        Active Users ✕
                    </Badge>
                    <Badge variant="secondary" class="cursor-pointer hover:shadow-sm transition">
                        Admins
                    </Badge>
                    <Badge
                        variant="secondary"
                        class="cursor-pointer hover:shadow-sm transition bg-emerald-50 border border-emerald-300 text-emerald-800"
                    >
                        With Orders ✕
                    </Badge>
                    <Badge variant="secondary" class="cursor-pointer hover:shadow-sm transition">
                        With Shipments
                    </Badge>
                </div>

                <!-- Filters Grid (12-column layout) -->
                <div class="grid grid-cols-12 gap-4">

                    <!-- Status Enum -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-2">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors">
                            <Label>Status</Label>
                            <Select>
                                <SelectTrigger class="mt-2 w-full">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active" class="cursor-pointer rounded px-2 py-1.5 transition-colors hover:bg-muted/50 data-[state=checked]:bg-emerald-50 data-[state=checked]:border-l-2 data-[state=checked]:border-emerald-500">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="inactive" class="cursor-pointer rounded px-2 py-1.5 hover:bg-muted/50">Inactive</SelectItem>
                                    <SelectItem value="discontinued" class="cursor-pointer rounded px-2 py-1.5 hover:bg-muted/50">Discontinued</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Role Multi-Select -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-3">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors bg-background">
                            <Label>Role</Label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button variant="outline" role="combobox" class="mt-2 w-full justify-between text-foreground">
                                        Select roles
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-[240px] p-2 bg-background border border-border" stay-open>
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <!-- Selected pills -->
                                        <Badge variant="secondary" class="cursor-pointer bg-emerald-100 text-emerald-800 border border-emerald-300">Admin ✕</Badge>
                                        <Badge variant="secondary" class="cursor-pointer bg-emerald-100 text-emerald-800 border border-emerald-300">Manager ✕</Badge>
                                    </div>
                                    <div class="mt-2 border-t pt-2 text-sm space-y-1">
                                        <div class="cursor-pointer hover:bg-muted/50 px-2 py-1 rounded transition-colors data-[selected=true]:bg-emerald-50 data-[selected=true]:border-l-2 data-[selected=true]:border-emerald-500">
                                            Admin
                                        </div>
                                        <div class="cursor-pointer hover:bg-muted/50 px-2 py-1 rounded">Manager</div>
                                        <div class="cursor-pointer hover:bg-muted/50 px-2 py-1 rounded">Viewer</div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <!-- Warehouse Enum -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-2">
                        <div class="p-3 rounded-lg border hover:bg-muted/50 transition-colors border-emerald-300">
                            <Label>Warehouse</Label>
                            <Select>
                                <SelectTrigger class="mt-2 w-full">
                                    <SelectValue placeholder="Select warehouse" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="central" class="hover:bg-muted/50">Central</SelectItem>
                                    <SelectItem value="east" class="hover:bg-muted/50">East</SelectItem>
                                    <SelectItem value="west" class="hover:bg-muted/50">West</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-3">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors bg-background">
                            <Label>Price</Label>
                            <div class="flex gap-2 mt-2">
                                <Input type="number" placeholder="Min" class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground" value="10" />
                                <Input type="number" placeholder="Max" class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground" value="200" />
                            </div>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-3">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors">
                            <Label>Placed Between</Label>
                            <div class="flex gap-2 mt-2">
                                <Input type="date" class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground" />
                                <Input type="date" class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground" />
                            </div>
                        </div>
                    </div>

                    <!-- Carrier Enum -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-2">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors">
                            <Label>Carrier</Label>
                            <Select>
                                <SelectTrigger class="mt-2 w-full">
                                    <SelectValue placeholder="Select carrier" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="fedex" class="hover:bg-muted/50">FedEx</SelectItem>
                                    <SelectItem value="ups" class="hover:bg-muted/50">UPS</SelectItem>
                                    <SelectItem value="dhl" class="hover:bg-muted/50">DHL</SelectItem>
                                    <SelectItem value="usps" class="hover:bg-muted/50">USPS</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-1 flex items-center">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors w-full text-center">
                            <Label>Active</Label>
                            <Switch class="mt-2 mx-auto" />
                        </div>
                    </div>

                    <!-- SKU -->
                    <div class="col-span-12 sm:col-span-6 md:col-span-3">
                        <div class="p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors">
                            <Label>SKU</Label>
                            <Input placeholder="Search SKU" class="mt-2 bg-background border border-input text-foreground placeholder:text-muted-foreground" />
                        </div>
                    </div>

                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-3">
                    <Button variant="primary" class="px-4 py-1.5 text-sm">Apply</Button>
                    <Button variant="outline" class="px-4 py-1.5 text-sm">Reset</Button>
                </div>
            </AccordionContent>
        </AccordionItem>
    </Accordion>
</template>

<script setup lang="ts">
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from "@/components/ui/accordion"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Filter } from "lucide-vue-next"
</script>
