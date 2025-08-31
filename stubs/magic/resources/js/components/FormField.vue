<template>
    <div class="grid gap-2">
        <Label for="email">{{ field.label }}</Label>
        <Input
            :type="field.type"
            class="mt-1 block w-full"
            :name="field.name"
            :default-value="modelValue"
            required
            placeholder="{{ field.label }}"
        />
        <InputError class="mt-2" :message="error" />
    </div>
    <!--    <div>
            <p class="-mt-4 text-sm text-muted-foreground">
                Your email address is unverified.
                <Link
                    href="#"
                    as="button"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Click here to resend the verification email.
                </Link>
            </p>
        </div>-->
</template>
<script setup lang="ts">
import { Input } from '@/components/ui/input/index.js';
import { Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Field } from '@/types/support.js';
import {Label} from '@/components/ui/label';

interface Props {
    error?: string;
    field: Field;
    item?: Record<string, any>;
    // bind model value
    modelValue?: any;
}

const { error, field, item }: Props = defineProps<Props>();

</script>
