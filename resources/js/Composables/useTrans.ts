import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useTrans() {
    const page = usePage();

    const localization = computed(() => {
        return (page.props as any).localization || {
            locale: 'km',
            currency: 'USD',
            exchange_rate: 4100,
            translations: {},
        };
    });

    const currentLocale = computed(() => localization.value.locale || 'km');
    const currentCurrency = computed(() => localization.value.currency || 'USD');
    const exchangeRate = computed(() => localization.value.exchange_rate || 4100);

    const t = (key: string, replacements: Record<string, string | number> = {}): string => {
        const translations = localization.value.translations || {};
        let text = translations[key] || key;

        // Sort keys descending by length so longer placeholders (e.g. "total")
        // are replaced before shorter substring placeholders (e.g. "to"),
        // preventing "total" from accidentally turning into "7tal".
        const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);

        keys.forEach((placeholder) => {
            text = text.replace(new RegExp(`:${placeholder}(?![a-zA-Z0-9_])`, 'g'), String(replacements[placeholder]));
        });

        return text;
    };

    const formatPrice = (usdAmount: string | number, showBoth = false): string => {
        const numUsd = typeof usdAmount === 'string' ? parseFloat(usdAmount) : usdAmount;
        if (isNaN(numUsd)) return '$0.00';

        const formattedUsd = '$' + numUsd.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        // 100 Riels rounding
        const khrAmount = Math.round((numUsd * exchangeRate.value) / 100) * 100;
        const formattedKhr = '៛' + khrAmount.toLocaleString('en-US');

        if (showBoth) {
            return `${formattedUsd} (${formattedKhr})`;
        }

        if (currentCurrency.value === 'KHR') {
            return formattedKhr;
        }

        return formattedUsd;
    };

    const setLocale = (locale: string) => {
        router.post(`/locale/${locale}`, {}, { preserveScroll: true });
    };

    const setCurrency = (currency: string) => {
        router.post(`/currency/${currency}`, {}, { preserveScroll: true });
    };

    return {
        t,
        formatPrice,
        currentLocale,
        currentCurrency,
        exchangeRate,
        setLocale,
        setCurrency,
    };
}
