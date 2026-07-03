import type { SharedData, TranslationMessages, TranslationValue } from '@/types';
import { usePage } from '@inertiajs/vue3';

type Replacements = Record<string, string | number>;

const resolveTranslation = (translations: TranslationMessages, key: string): TranslationValue | undefined =>
    key.split('.').reduce<TranslationValue | undefined>((value, segment) => {
        if (!value || typeof value === 'string') {
            return undefined;
        }

        return value[segment];
    }, translations);

const interpolate = (message: string, replacements: Replacements): string =>
    Object.entries(replacements).reduce((result, [key, value]) => result.replaceAll(`:${key}`, String(value)), message);

export function useTranslations() {
    const page = usePage<SharedData>();

    const t = (key: string, replacements: Replacements = {}, fallback?: string): string => {
        const value = resolveTranslation(page.props.translations ?? {}, key);

        if (typeof value !== 'string') {
            return fallback ?? key;
        }

        return interpolate(value, replacements);
    };

    return { t };
}
