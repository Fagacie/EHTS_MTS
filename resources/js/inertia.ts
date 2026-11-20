import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const pages = import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} — Ecohydrotech Solution` : 'Ecohydrotech Solution'),
    resolve: async (name) => {
        const loader = pages[`./pages/${name}.vue`];

        if (!loader) {
            throw new Error(`Inertia page [${name}] could not be resolved.`);
        }

        const mod = await loader();
        return mod.default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#272d38',
        showSpinner: false,
    },
});
