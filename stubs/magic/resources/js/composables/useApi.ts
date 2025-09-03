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
        // Add post, put, delete methods as needed
    }
}
