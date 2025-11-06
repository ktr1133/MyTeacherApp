import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/dashboard.css',
                'resources/css/tags.css',
                'resources/css/reports/performance.css',
                'resources/js/app.js',
                'resources/js/dashboard/dashboard.js',
                'resources/js/dashboard/task-search.js',
                'resources/js/dashboard/group-task.js',
                'resources/js/tags/tags.js',
                'resources/js/reports/performance.js',
            ],
            refresh: true,
        }),
    ],
});
