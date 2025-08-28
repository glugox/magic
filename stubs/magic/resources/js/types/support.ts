export interface Field {
    name: string
    type: 'string' | 'number' | 'boolean' | 'any' | 'object' | 'Date'
    nullable: boolean
    length: number | null
    precision: number | null
    scale: number | null
    default: any
    comment: string | null
    sortable: boolean
    searchable: boolean
}

export interface Entity {
    name: string
    resourcePath: string
    singularName: string
    pluralName: string
    fields: Field[]
}

export interface PaginationObject {
    data: any[]
    total: number
    current_page: number
    per_page: number
    filters: any
    [key: string]: any
}
export interface TableFilters {
    search?: string
    sortDir?: 'asc' | 'desc'
    page?: number
    per_page?: number
    [key: string]: any
}
