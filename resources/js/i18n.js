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

/**
 * Plural-aware translate. Looks up "key|<category>" first, where the category comes from the
 * language's plural rules (English: one/other; Arabic: zero/one/two/few/many/other), then "key".
 */
export function translateCount(translations, locale, key, count, replace = {}) {
    const category = new Intl.PluralRules(locale || 'en').select(count);
    const variant = `${key}|${category}`;
    return translate(translations, translations?.[variant] ? variant : key, replace);
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
        app.config.globalProperties.$tc = function (key, count, replace = {}) {
            return translateCount(this.$page.props.translations, this.$page.props.locale, key, count, replace);
        };
    },
};
