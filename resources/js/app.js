import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import VueKonva from 'vue-konva';

createInertiaApp({
    pages: './Pages',
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(VueKonva)
            .mount(el);
    },
});
