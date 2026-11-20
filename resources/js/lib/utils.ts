import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

export function getInitials(name: string, fallback = 'U'): string {
    const primaryName = name.split(',')[0] ?? name;
    const rawTokens = primaryName
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    const honorificPattern = /^(ir|dr|ts|sr|ar|prof|assoc|dato'?|datuk|tan|sri|encik|en|puan|pn|mr|ms|mrs)\.?$/i;
    const strippedTokens = rawTokens.filter((token, index) => {
        if (index >= rawTokens.length - 1) {
            return true;
        }

        return !honorificPattern.test(token);
    });

    const parts = (strippedTokens.length > 0 ? strippedTokens : rawTokens).slice(0, 2);

    if (parts.length === 0) {
        return fallback;
    }

    return parts.map((part) => part.charAt(0).toUpperCase()).join('');
}

export function pluralize(count: number, singular: string, plural?: string): string {
    if (count === 1) {
        return singular;
    }

    return plural ?? `${singular}s`;
}

/**
 * Strip trailing ".00" from whole-Ringgit fee labels while preserving non-zero cents.
 */
export function formatDisplayFee(priceLabel: string): string {
    return priceLabel.replace(/\.00$/, '');
}
