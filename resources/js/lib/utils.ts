export function cn(...classes: Array<string | Record<string, boolean> | undefined | null>) {
    return classes
        .flatMap((value) => {
            if (! value) {
                return [];
            }

            if (typeof value === 'string') {
                return value.split(' ');
            }

            return Object.entries(value)
                .filter(([, condition]) => Boolean(condition))
                .map(([className]) => className);
        })
        .filter(Boolean)
        .join(' ')
        .trim();
}

export function urlIsActive(target: string | null | undefined, current: string): boolean {
    if (! target) {
        return false;
    }

    if (target === current) {
        return true;
    }

    const normalizedTarget = target.endsWith('/') ? target : `${target}/`;

    return current.startsWith(normalizedTarget);
}
