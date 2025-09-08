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
/**
 * Check if two arrays are equal
 */
export function arraysEqualIgnoreOrder(a: any[], b: any[]) {
    if (a.length !== b.length) return false

    const sortedA = [...a].sort()
    const sortedB = [...b].sort()

    return sortedA.every((v, i) => v === sortedB[i])
}
