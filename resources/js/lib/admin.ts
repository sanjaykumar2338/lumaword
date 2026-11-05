export function formatMicrosAsUsd(micros: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    }).format(micros / 1_000_000);
}

export function formatAdminFeature(feature: string): string {
    return feature.replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

export function chartPercent(value: number, maximum: number): number {
    if (value <= 0 || maximum <= 0) return 0;

    return Math.max(4, Math.round((value / maximum) * 100));
}
