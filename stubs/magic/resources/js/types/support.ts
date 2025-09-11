export type CrudActionType = 'create' | 'read' | 'update' | 'delete';

// Define database Id type
export type DbId = number;

export interface Field {
    name: string
    type: FieldType
    label: string
    required: boolean
    nullable: boolean
    sometimes: boolean
    length: number | null
    precision: number | null
    scale: number | null
    rules: ValidationRuleSet
    default: any
    comment: string | null
    sortable: boolean
    searchable: boolean
    values: string[] | null
}

export interface Relation {
    type: 'belongsTo' | 'hasMany' | 'hasOne' | 'belongsToMany' | 'morphTo' | 'morphMany' | 'morphOne' | 'morphToMany' | 'morphedByMany'
    localEntityName: string | null
    relatedEntityName?: string | null
    relatedEntity?: string | null
    foreignKey?: string | null
    localKey?: string | null
    relatedKey?: string | null
    relationName?: string | null
    apiPath?: string | null
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
    fields: Field[]
    relations: Relation[]
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
    relation?: Relation
    item?: Record<string, any>
    error?: string
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


export interface TableFilters {
    search?: string
    sortKey?: string
    sortDir?: 'asc' | 'desc'
    page?: number
    per_page?: number
    selectedIds?: number[]
    [key: string]: any
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
