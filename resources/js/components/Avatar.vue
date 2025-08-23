<template>
    <div
        class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold"
        :style="{ backgroundColor: bgColor, opacity: 0.5 }"
    >
        {{ initials }}
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ name: string }>()

// Computes the initials from the provided name
const initials = computed(() => {
    const str = (props.name ?? '').trim()
    if (!str) return "?" // fallback for completely empty
    // split on whitespace, keep only non-empty parts
    const parts = str.split(/\s+/).filter(Boolean)
    if (parts.length === 1) {
        // single word → take first 2 letters
        return parts[0].slice(0, 2).toUpperCase()
    }
    // multiple words → take first char of first 2 words
    return parts.slice(0, 2).map(w => w[0]).join('').toUpperCase()
})

const bgColor = computed(() => {
    const hash = Array.from(props.name).reduce((acc, c) => acc + c.charCodeAt(0), 0)
    const colors = ['#D30100', '#0066DD', '#00AA00', '#FF9900', '#9900FF']
    return colors[hash % colors.length]
})
</script>
