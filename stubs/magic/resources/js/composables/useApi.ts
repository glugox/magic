import {ApiResponse} from '@/types/support'

export const useApi = () => {
    const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'

    const request = async <T = any>(
        endpoint: string,
        options: RequestInit = {}
    ): Promise<ApiResponse<T>> => {

        // Development only, delay for 1s to simulate network latency
        if (import.meta.env.DEV) {
            await new Promise((resolve) => setTimeout(resolve, 250))
        }

        const url = `${baseURL}${endpoint}`

        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...options.headers,
                },
                ...options,
            })

            const contentType = response.headers.get('content-type')
            const json = contentType?.includes('application/json')
                ? await response.json().catch(() => null)
                : null

            return json as ApiResponse<T>
        } catch (error: any) {
            return {
                success: false,
                status: 0,
                message: error.message || 'Network error.',
            }
        }
    }

    const getQueryString = (params?: Record<string, string>) =>
        params ? `?${new URLSearchParams(params).toString()}` : ''

    return {
        get: <T = any>(endpoint: string, params?: Record<string, string>) =>
            request<T>(`${endpoint}${getQueryString(params)}`, { method: 'GET' }),

        post: <T = any>(
            endpoint: string,
            body?: Record<string, any>,
            headers: Record<string, string> = {}
        ) =>
            request<T>(endpoint, {
                method: 'POST',
                headers,
                body: body ? JSON.stringify(body) : undefined,
            }),

        put: <T = any>(
            endpoint: string,
            body?: Record<string, any>,
            headers: Record<string, string> = {}
        ) =>
            request<T>(endpoint, {
                method: 'PUT',
                headers,
                body: body ? JSON.stringify(body) : undefined,
            }),

        patch: <T = any>(
            endpoint: string,
            body?: Record<string, any>,
            headers: Record<string, string> = {}
        ) =>
            request<T>(endpoint, {
                method: 'PATCH',
                headers,
                body: body ? JSON.stringify(body) : undefined,
            }),

        delete: <T = any>(
            endpoint: string,
            headers: Record<string, string> = {}
        ) =>
            request<T>(endpoint, {
                method: 'DELETE',
                headers,
            }),
    }
}
