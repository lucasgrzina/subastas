import { createI18n } from 'vue-i18n';
import global from './locales/es/global';
import auth from './locales/es/auth';
import supportMessages from './locales/es/support-messages';
import apiClients from './locales/es/api-clients';
import influencers from './locales/es/influencers';

export const i18n = createI18n({
    legacy: false,
    locale: 'es',
    fallbackLocale: 'es',
    messages: {
        es: {
            global,
            auth,
            ...supportMessages,
            ...apiClients,
            ...influencers,
        },
    },
    missingWarn: import.meta.env.DEV,
});
