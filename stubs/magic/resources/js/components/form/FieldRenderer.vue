<template>
    <component
        :is="fieldComponent"
        v-bind="props"
        :model-value="modelValue"
        :crud-action-type="crudActionType"
        @update:modelValue="updateModelValue"
    />
</template>

<script setup lang="ts">
import StringField from './StringField.vue'
import NumberField from './NumberField.vue'
import DecimalField from './DecimalField.vue'
import DateField from './DateField.vue'
import BelongsToField from './BelongsToField.vue'
import { Field, CrudActionType } from '@/types/support'
import TextAreaField from "@/components/form/TextAreaField.vue";

interface Props {
    error?: string
    field: Field
    crudActionType: CrudActionType
    modelValue?: any
}
const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const componentsMap: Record<string, any> = {
    // ──────── Core Identifiers ────────
    id: StringField,         //  need IdField
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
    dateTime: DateField,        // ✅ need DateTimeField
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
    enum: StringField,           // ✅ need EnumField
    boolean: StringField,        // ✅ need BooleanField
    foreignId: StringField,      // ✅ need ForeignIdField

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
