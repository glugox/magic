<template>
    <component
        :is="fieldComponent"
        v-bind="props"
        @update:modelValue="updateModelValue"
        @open-related="$emit('openRelated', $event)"
    />
</template>

<script setup lang="ts">
import StringField from '@/components/form/StringField.vue'
import NumberField from '@/components/form/NumberField.vue'
import DecimalField from '@/components/form/DecimalField.vue'
import DateField from '@/components/form/DateField.vue'
import BooleanField from "@/components/form/BooleanField.vue";
import BelongsToField from '@/components/form/BelongsToField.vue'
import {FormFieldProps} from '@/types/support'
import TextAreaField from "@/components/form/TextAreaField.vue";
import EnumField from "@/components/form/EnumField.vue";
import DateTimeField from "@/components/form/DateTimeField.vue";
import IdField from '@/components/form/IdField.vue';

const props = defineProps<FormFieldProps>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'openRelated', entry: any): void;
}>()

const componentsMap: Record<string, any> = {
    // ──────── Core Identifiers ────────
    id: IdField,         //  need IdField
    uuid: StringField,       //  need UuidField
    slug: StringField,
    username: StringField,

    // ──────── Numeric Types ────────
    bigIncrements: NumberField,
    bigInteger: NumberField,
    integer: NumberField,
    smallInteger: NumberField,
    tinyInteger: NumberField,
    unsignedBigInteger: NumberField,
    unsignedInteger: NumberField,
    unsignedSmallInteger: NumberField,
    unsignedTinyInteger: NumberField,
    decimal: DecimalField,              // ✅ need DecimalField
    double: DecimalField,               // ✅ need DecimalField
    float: DecimalField,                // ✅ need DecimalField

    // ──────── Text Types ────────
    string: StringField,
    char: StringField,
    text: TextAreaField,
    longText: TextAreaField,
    mediumText: TextAreaField,

    // ──────── Date / Time ────────
    date: DateField,
    dateTime: DateTimeField,        // ✅ need DateTimeField
    time: StringField,          // ✅ need TimeField
    timestamp: StringField,     // ✅ need TimestampField
    year: StringField,          // ✅ need YearField

    // ──────── JSON / Binary ────────
    json: TextAreaField,         // ✅ need JsonField
    jsonb: TextAreaField,        // ✅ need JsonField
    binary: TextAreaField,       // ✅ need BinaryField

    // ──────── Auth / Security ────────
    email: StringField,
    password: StringField,    // ✅ need SecretField
    secret: StringField,      // ✅ need SecretField
    token: StringField,       // ✅ need SecretField

    // ──────── Networking ────────
    ipAddress: StringField,      // ✅ need IpAddressField
    url: StringField,            // ✅ need UrlField
    phone: StringField,          // ✅ need PhoneField

    // ──────── Media / File ────────
    file: StringField,            // ✅ need FileField
    image: StringField,           // ✅ need ImageField

    // ──────── Enum / Special ────────
    enum: EnumField,           // ✅ need EnumField
    boolean: BooleanField,        // ✅ need BooleanField
    foreignId: BelongsToField,

    // ──────── Relations ────────
    belongsTo: BelongsToField,
    hasOne: null,
    hasMany: null,
    belongsToMany: null,
}

const fieldComponent = componentsMap[props.field.type] ?? StringField

function updateModelValue(value: any) {
    emit('update:modelValue', value)
}
</script>
