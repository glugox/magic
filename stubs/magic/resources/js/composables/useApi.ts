import { ref } from 'vue'

export const useApi = () => {
    const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'

    const request = async (endpoint: string, options: RequestInit = {}) => {
        const url = `${baseURL}${endpoint}`

        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        return response.json()
    }

    return {
        get: (endpoint: string, params?: Record<string, string>) => {
            const queryString = params ? new URLSearchParams(params).toString() : ''
            const url = queryString ? `${endpoint}?${queryString}` : endpoint
            return request(url, { method: 'GET' })
        },
        // post with headers
        post: (endpoint: string, body: Record<string, any>, headers: Record<string, string>) => {
            return request(endpoint, {
                method: 'POST',
                headers: {
                    ...headers,
                },
                body: JSON.stringify(body),
            })
        },
        // put with headers
        put: (endpoint: string, body: Record<string, any>, headers: Record<string, string>) => {
            return request(endpoint, {
                method: 'PUT',
                headers: {
                    ...headers,
                },
                body: JSON.stringify(body),
            })
        },
        // patch with headers
        patch: (endpoint: string, body: Record<string, any>, headers: Record<string, string>) => {
            return request(endpoint, {
                method: 'PATCH',
                headers: {
                    ...headers,
                },
                body: JSON.stringify(body),
            })
        },
        // delete with headers
        delete: (endpoint: string, headers: Record<string, string>) => {
            return request(endpoint, {
                method: 'DELETE',
                headers: {
                    ...headers,
                },
            })
        },
    }
}
