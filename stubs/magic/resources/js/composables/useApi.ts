import axios, { AxiosRequestConfig } from 'axios'
import { ApiResponse } from '@/types/support'

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://orchestrator.test'
const baseApiURL = `${baseURL}/api`

// Create an axios instance
const api = axios.create({
    baseURL: baseApiURL,
    withCredentials: true, // important for sanctum + cookies
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
})

// Track CSRF initialization
let csrfReady = false

async function initCsrf() {
    if (!csrfReady) {
        await axios.get(`${baseURL}/sanctum/csrf-cookie`, { withCredentials: true })
        csrfReady = true
    }
}

export const useApi = () => {
    const request = async <T = any>(
        endpoint: string,
        options: AxiosRequestConfig = {}
    ): Promise<ApiResponse<T>> => {
        if (import.meta.env.DEV) {
            await new Promise((resolve) => setTimeout(resolve, 250))
        }

        try {
            const response = await api.request<T>({
                url: endpoint,
                ...options,
            })

            return response.data as ApiResponse<T>
        } catch (error: any) {
            return {
                success: false,
                status: error.response?.status ?? 0,
                message: error.response?.data?.message || error.message || 'Network error.',
            }
        }
    }

    const getQueryString = (params?: Record<string, string>) =>
        params ? `?${new URLSearchParams(params).toString()}` : ''

    return {
        initCsrf,

        get: async <T = any>(endpoint: string, params?: Record<string, string>) =>
            request<T>(`${endpoint}${getQueryString(params)}`, { method: 'GET' }),

        post: async <T = any>(endpoint: string, body?: Record<string, any>) =>
            request<T>(endpoint, { method: 'POST', data: body }),

        put: async <T = any>(endpoint: string, body?: Record<string, any>) =>
            request<T>(endpoint, { method: 'PUT', data: body }),

        patch: async <T = any>(endpoint: string, body?: Record<string, any>) =>
            request<T>(endpoint, { method: 'PATCH', data: body }),

        delete: async <T = any>(endpoint: string) =>
            request<T>(endpoint, { method: 'DELETE' }),
    }
}
