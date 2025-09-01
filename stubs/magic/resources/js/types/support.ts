export interface Field {
    name: string
    type: 'string' | 'number' | 'boolean' | 'any' | 'object' | 'Date'
    label: string
    required: boolean
    nullable: boolean
    sometimes: boolean
    length: number | null
    precision: number | null
    scale: number | null
    rules: string[]
    default: any
    comment: string | null
    sortable: boolean
    searchable: boolean
}

export interface Entity {
    name: string
    indexRouteName: string
    singularName: string
    pluralName: string
    fields: Field[]
}

export interface Controller {
    index: Function,
    show: (id: number) => any,
    create: Function,
    store: Function,
    edit: (id: number) => any,
    update: (id: number) => any,
    destroy: (id: number) => any,
}

export interface PaginationObject {
    data: any[]
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
export interface TableFilters {
    search?: string
    sortDir?: 'asc' | 'desc'
    page?: number
    per_page?: number
    [key: string]: any
}
