import {unref} from "vue";

type LogLevel = 'info' | 'warn' | 'error' | 'debug';

interface LogOptions {
    data?: any;
    level?: LogLevel;
    color?: string;
}

const isDev = import.meta.env.DEV; // Only logs in dev mode

export function log(
    source: string,
    action: string,
    message?: string | null,
    { data, level = 'info', color }: LogOptions = {}
) {
    if (!isDev) return;

    const prefix = `%c[${source}:${action}]`;
    const style = `color: ${color || getColorByLevel(level)}; font-weight: 600;`;

    if (data !== undefined) {
        console[level]?.(`${prefix} ${message ?? ''}`, style, data);
    } else {
        console[level]?.(`${prefix} ${message ?? ''}`, style);
    }
}

export function logSuccess(
    source: string,
    action: string,
    message?: string | null,
    data?: any
) {
    log(source, action, message, { data, level: 'info', color: '#22c55e' });
}

/**
 * Create dedicated log instance for a specific source
 */
/**
 * Create dedicated log instance for a specific source
 */
export function createLogger(source: string, instanceLabel?: string | Array<string | number | null | undefined>) {
    let labelStr: string | undefined;

    if (Array.isArray(instanceLabel)) {
        labelStr = createLogLabel(...(instanceLabel as [string, string?, string?]));
    } else {
        labelStr = instanceLabel;
    }

    const prefixMessage = `${source}${labelStr ? ` (${labelStr})` : ''}`;

    return {
        log: (
            action: string,
            message?: string | null,
            options: LogOptions = {}
        ) => log(prefixMessage, action, message, options)
    };
}

export function createLogLabel(a: string, b?: string | number | null, c?: string | number | null) {
    return [a, b, c].filter(Boolean).join(':');
}


function getColorByLevel(level: LogLevel) {
    switch (level) {
        case 'warn':
            return '#eab308'; // yellow
        case 'error':
            return '#dc2626'; // red
        case 'debug':
            return '#3b82f6'; // blue
        default:
            return '#787878'; // green
    }
}
