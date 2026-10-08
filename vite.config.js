import { readdirSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Each step's client code is a plain ES module in shared/client/, the same file on every platform.
// They are entry points as they are: the step page loads the file the manifest lists.
const stepModules = readdirSync('shared/client')
    .filter((file) => file.endsWith('.js'))
    .map((file) => `shared/client/${file}`);

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // SurveyJS themes: the form, Creator and Dashboard pages each load one
                'resources/css/survey.css',
                'resources/css/creator.css',
                'resources/css/dashboard.css',
                ...stepModules,
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        chunkSizeWarningLimit: 5000,    // Survey Creator and the Dashboard are large; each page loads only its own module
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
