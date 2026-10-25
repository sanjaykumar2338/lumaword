import type { TimezoneOption } from '../types';

export function detectBrowserTimezone(): string | undefined {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch {
        return undefined;
    }
}

export function isSupportedTimezone(timezone: string, options: TimezoneOption[]): boolean {
    return options.some((option) => option.value === timezone);
}

export function resolveReminderTimezone(
    timezoneConfigured: boolean,
    savedTimezone: string,
    options: TimezoneOption[],
    browserTimezone = detectBrowserTimezone(),
): string {
    if (timezoneConfigured) {
        return savedTimezone;
    }

    return browserTimezone && isSupportedTimezone(browserTimezone, options) ? browserTimezone : 'UTC';
}

export function filterTimezoneOptions(options: TimezoneOption[], query: string): TimezoneOption[] {
    const normalizedQuery = query.trim().toLocaleLowerCase();

    if (!normalizedQuery) {
        return options;
    }

    return options.filter((option) =>
        `${option.value} ${option.label}`.toLocaleLowerCase().includes(normalizedQuery),
    );
}
