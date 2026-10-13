import { usePage } from '@inertiajs/vue3';

/**
 * Translate a UI string. Keys are the English text (Laravel JSON translations in lang/{locale}.json);
 * missing keys fall back to English. Replacements use Laravel's ":name" placeholders.
 */
export function translate(translations, key, replace = {}) {
    let text = translations?.[key] ?? key;
    for (const [name, value] of Object.entries(replace)) {
        text = text.replaceAll(`:${name}`, value);
    }
    return text;
}

export function useT() {
    const page = usePage();
    return (key, replace) => translate(page.props.translations, key, replace);
}

export const i18n = {
    install(app) {
        app.config.globalProperties.$t = function (key, replace) {
            return translate(this.$page.props.translations, key, replace);
        };
    },
};
