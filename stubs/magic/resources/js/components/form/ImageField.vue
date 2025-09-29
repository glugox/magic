<template>
    <BaseField v-bind="props">
        <template #default="{ validate }">
            <div class="space-y-2">
                <!-- File input -->
                <input
                    ref="fileInput"
                    :name="field.name"
                    :placeholder="`Select ${field.label}...`"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="(e) => onFileChange(e, validate)"
                />

                <!-- Image preview -->
                <Card class="w-40">
                    <AspectRatio :ratio="1/1" class="bg-muted rounded-md overflow-hidden">
                        <template v-if="preview">
                            <img :src="preview" alt="Preview" class="object-cover w-full h-full" />
                        </template>
                        <template v-else>
                            <div class="flex items-center justify-center h-full text-muted-foreground">
                                No image
                            </div>
                        </template>
                    </AspectRatio>
                </Card>

                <!-- Actions -->
                <div class="flex gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        :disabled="isUploading"
                        @click="openFileDialog"
                    >
                        {{ isUploading ? 'Uploading...' : 'Upload' }}
                    </Button>
                    <Button
                        v-if="preview"
                        variant="destructive"
                        size="sm"
                        :disabled="isUploading"
                        @click="removeImage(validate)"
                    >
                        Remove
                    </Button>
                </div>

                <p v-if="errorMessage" class="text-destructive text-sm">{{ errorMessage }}</p>
            </div>
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import BaseField from './BaseField.vue'
import {FormFieldEmits, FormFieldProps} from '@/types/support'
import axios from 'axios'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { AspectRatio } from '@/components/ui/aspect-ratio'

const props = defineProps<FormFieldProps>()
const emit = defineEmits<FormFieldEmits>()

const model = ref(props.modelValue ?? null)
const preview = ref<string | null>(props.modelValue || null)
const fileInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)
const errorMessage = ref<string | null>(null)

watch(model, val => emit('update:modelValue', val))

onBeforeUnmount(() => {
    if (preview.value && preview.value.startsWith('blob:')) {
        URL.revokeObjectURL(preview.value)
    }
})

function openFileDialog() {
    fileInput.value?.click()
}

async function onFileChange(e: Event, validate: Function) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return

    // Revoke previous blob
    if (preview.value && preview.value.startsWith('blob:')) {
        URL.revokeObjectURL(preview.value)
    }

    preview.value = URL.createObjectURL(file)
    errorMessage.value = null
    isUploading.value = true

    try {
        const formData = new FormData()
        formData.append('image', file)
        formData.append('attachable_type', props.field.attachableType)
        formData.append('attachable_id', props.field.attachableId)

        const response = await axios.post('/api/attachments', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        model.value = response.data.file_path
        preview.value = response.data.url
        validate(model.value)
    } catch (err: any) {
        errorMessage.value = err?.response?.data?.message || 'Upload failed'
        console.error('Image upload failed:', err)
    } finally {
        isUploading.value = false
    }
}

function removeImage(validate: Function) {
    model.value = null
    if (preview.value && preview.value.startsWith('blob:')) {
        URL.revokeObjectURL(preview.value)
    }
    preview.value = null
    validate(null)
}
</script>
