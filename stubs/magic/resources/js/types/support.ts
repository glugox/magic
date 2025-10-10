import {ColumnDef} from "@tanstack/vue-table";

export type CrudActionType = 'create' | 'read' | 'update' | 'delete';

// Define database Id type
export type DbId = number;

export interface FormEntry {
    id: string
    entity: Entity,
    item: Record<string, any>
    controller: Controller
}

/**
 * A lightweight version of FormEntry used for logging and tracking purposes.
 */
export interface FormEntrySignature {
    id: string
    entityName: string
    itemId: DbId | null
    actionType: CrudActionType
}

export interface EnumFieldOption {
    name: string
    label: string
}

export interface Field {
    name: string
    type: FieldType
    label: string
    required?: boolean
    nullable?: boolean
    sometimes?: boolean
    length?: number | null
    precision?: number | null
    scale?: number | null
    rules: ValidationRuleSet
    default?: any
    comment?: string | null
    sortable?: boolean
    searchable?: boolean
    options?: EnumFieldOption[] | null
    hidden?: boolean
    component?: string | null
    min?: number | null
    max?: number | null
}

export interface Relation {
    type: 'belongsTo' | 'hasMany' | 'hasOne' | 'belongsToMany' | 'morphTo' | 'morphMany' | 'morphOne' | 'morphToMany' | 'morphedByMany'
    localEntityName: string | null
    relatedEntityName?: string | null
    // can be function () => ticketEntity
    relatedEntity?: (() => Entity) | null
    foreignKey?: string | null
    localKey?: string | null
    relatedKey?: string | null
    relationName?: string | null
    apiPath?: string | null
    controller?: Controller | null
}

export interface FilterBaseProps {
    label: string; // Human readable label
    filterValue?: FilterValue;
}

export interface FilterConfig extends FilterBaseProps {
    field: string;              // DB column / key
    type: string;               // "text" | "enum" | "date" | ...
    options?: EnumFieldOption[];
    operators?: string[];       // Optional, e.g. ["equals", "between"]
    hidden?: boolean;           // UI visibility
    dynamic?: (entity: Entity) => boolean; // UI conditional
}

export interface ResourceFormProps {
    item?: Record<string, any>;
    entity: Entity;
    parentEntity?: Entity;
    parentId?: DbId;
    jsonMode?: boolean;
    dialogMode?: boolean;
}


export type ResourceAction = "created" | "updated" | "deleted"

export interface DialogOptions extends ResourceFormProps {
    title?: string
    onSuccess?: (record: any, action: ResourceAction) => void
}

export interface DialogInstance extends DialogOptions {
    id: string;
}

export interface ResourceQueryOptions {

}

export interface ValidationRuleSet {
    create: string[]
    update: string[]
}

export interface Entity {
    name: string
    indexRouteName: string
    singularName: string
    singularNameLower: string
    pluralName: string
    controller: Controller,
    inertiaComponent: string
    fields: Field[]
    relations: Relation[],
    filters?: FilterConfig[]
}

export interface ResourceData {
    name: string,
    [key: string]: any
}

export type Controller = any

export interface FormFieldProps {
    field: Field
    entity: Entity
    crudActionType: CrudActionType
    modelValue?: any
    item?: Record<string, any>
    error?: string
}

export type TableId = string

export interface ResourceTableProps<T> {
    entity: Entity
    parentEntity?: Entity
    columns: ColumnDef<ResourceData>[]
    data: PaginatedResponse<T>
    parentId?: DbId
    state?: DataTableState
}

export type FormFieldEmits = {
    (e: 'update:modelValue', value: string|number|boolean|null): void,
    (e: 'openRelated', relation: Relation): void;
}

export type TablePropsEmits = {
    (e: "update:search", value: string): void
    (e: "update:visibleColumns", value: string[]): void
}

export type TableBulkEmits = {
    (e: "bulk-action", action: "edit" | "delete" | "archive"): void
}

export type TableFilterEmits = {
    (e: "change", payload: FilterValue): void
    (e: "reset"): void
}

export type TableFiltersEmits = {
    (e: "update:filters", payload: DataTableFilters): void,
    (e: "reset"): void
};

export type TableEmits = TablePropsEmits & TableBulkEmits


/**
 * Extend FormFieldProps to set relation as required
 */
/*export type FormFieldPropsWithRelation = FormFieldProps & {
    relation: Relation
}*/

export interface WayfinderRoute {
    url: string
    method: 'get' | 'post' | 'put' | 'patch' | 'delete' | 'head' | 'options'
    definition: {
        methods: ('get' | 'post' | 'put' | 'patch' | 'delete' | 'head' | 'options')[]
        url: string
        [key: string]: any
    }
    [key: string]: any
}

export interface PaginationObject {
    data: ResourceData[]
    total: number
    current_page: number
    per_page: number
    search?: string
    sort_key?: string
    last_page?: number
    sort_dir?: "asc" | "desc"
    prev_page?: number
    next_page?: number
    prev_page_url?: string | null
    next_page_url?: string | null
    [key: string]: any
}

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface PaginationMeta {
    current_page: number
    from: number | null
    last_page: number
    path: string
    per_page: number
    to: number | null
    total: number
}

export interface PaginatedResponse<T> {
    data: T[]
    links: PaginationLink[]
    meta: PaginationMeta
}

export interface Column {
    name: string
    label: string
}

export type PrimitiveFilter =
    | null | string | number | boolean | [number | null, number | null] | [string | null, string | null];

export interface AdvancedFilter {
    field: string;
    type: "range" | "date_range" | "enum" | "boolean" | "custom";
    value: PrimitiveFilter | object;
}

export type FilterValue = PrimitiveFilter | AdvancedFilter;


// Full table filters with dynamic keys
export interface DataTableFilters {
    [key: string]: FilterValue; // dynamic filters
}

// Data table state
export interface DataTableSettings {
    tableId?: TableId
    loading?: boolean
    error?: string
    selectedIds?: number[]
    allColumns?: Column[]
    visibleColumns?: string[]
    sortKey?: string | null
    sortDir?: 'asc' | 'desc' | null
}

export interface DataTableState {
    settings: DataTableSettings
    filters: DataTableFilters
}

export type FieldType =
    | 'id'
    | 'bigIncrements'
    | 'bigInteger'
    | 'binary'
    | 'boolean'
    | 'char'
    | 'date'
    | 'dateTime'
    | 'decimal'
    | 'double'
    | 'email'
    | 'enum'
    | 'file'
    | 'float'
    | 'foreignId'
    | 'image'
    | 'integer'
    | 'ipAddress'
    | 'json'
    | 'jsonb'
    | 'longText'
    | 'mediumText'
    | 'password'
    | 'smallInteger'
    | 'string'
    | 'text'
    | 'time'
    | 'timestamp'
    | 'tinyInteger'
    | 'unsignedBigInteger'
    | 'unsignedInteger'
    | 'unsignedSmallInteger'
    | 'unsignedTinyInteger'
    | 'uuid'
    | 'url'
    | 'year'
    | 'secret'
    | 'token'
    // Relation types
    | 'belongsTo'
    | 'hasMany'
    | 'hasOne'
    | 'belongsToMany'
    | 'username'
    | 'phone'
    | 'slug';
