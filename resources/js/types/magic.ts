interface PaginationObject {
    data: any[]
    total: number
    current_page: number
    per_page: number
    filters: any
    [key: string]: any
}
interface TableFilters {
    search?: string
    sortDir?: 'asc' | 'desc'
    page?: number
    per_page?: number
    [key: string]: any
}
