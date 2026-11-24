import { defineConfig, loadEnv } from 'vite';
import inertia from '@inertiajs/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const environment = loadEnv(mode, process.cwd(), '');

    return {
        define: {
            'import.meta.env.VITE_GOOGLE_MAPS_API_KEY': JSON.stringify(
                environment.VITE_GOOGLE_MAPS_API_KEY || environment.GOOGLE_MAPS_API_KEY || '',
            ),
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                        optimizedFallbacks: false,
                    }),
                ],
            }),
            inertia(),
            vue(),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
