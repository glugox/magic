import {FilterValue} from "@/types/support";

export const debounced = (fn: Function, ms = 400) => {
    let t: number | undefined
    return (...args: any[]) => {
        clearTimeout(t)
        // @ts-ignore
        t = setTimeout(() => fn(...args), ms)
    }
}
export function parseBool(value: any): boolean {
    if (typeof value === 'string') {
        return value.toLowerCase() === 'true';
    }

    return Boolean(value);
}

export function randomId(length = 6): string {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Check if two arrays are equal
 */
export function arraysEqualIgnoreOrder(a: any[], b: any[]) {
    if (a.length !== b.length) return false

    const sortedA = [...a].sort()
    const sortedB = [...b].sort()

    return sortedA.every((v, i) => v === sortedB[i])
}

export function deepCopy(value: unknown) {
    if (typeof value !== 'object') {
        return value;
    }
    return JSON.parse(JSON.stringify(value));
}

export function formatDate(date: string | Date): string {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${day}.${month}.${year}`;
}

export function isEqual(a: any, b: any): boolean {
    if (a === b) return true
    if (typeof a !== typeof b) return false
    if (a == null || b == null) return a === b
    if (typeof a === "object") {
        const aKeys = Object.keys(a)
        const bKeys = Object.keys(b)
        if (aKeys.length !== bKeys.length) return false
        return aKeys.every((k) => isEqual(a[k], b[k]))
    }
    return a === b
}
/**
 *  Check if a filter value is considered "empty"
 */
export function isEmptyFilterValue(value: FilterValue) {
    if (value === null || value === '') return true

    if (typeof value === 'object') {
        // Check if all keys are null/undefined
        return Object.values(value).every(v => v == null || (Array.isArray(v) && v.length === 0))
    }

    return false
}

/**
 * Get a cookie by name
 * @param name
 */
export function getCookie(name: string) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
    return match ? decodeURIComponent(match[2]) : null
}

