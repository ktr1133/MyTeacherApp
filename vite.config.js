import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/common.css',
                'resources/css/auth.css',
                'resources/css/auth/register-validation.css',
                'resources/css/avatar/avatar.css',
                'resources/css/batch.css',
                'resources/css/dashboard.css',
                'resources/css/tags.css',
                'resources/css/reports/performance.css',
                'resources/css/tasks/pending-approvals.css',
                'resources/css/tokens/history.css',
                'resources/css/tokens/purchase.css',
                'resources/css/welcome.css',
                'resources/js/app.js',
                'resources/js/admin/common.js',
                'resources/js/auth/register-validation.js',
                'resources/js/avatar/avatar-controller.js',
                'resources/js/avatar/avatar-edit.js',
                'resources/js/avatar/avatar-form.js',
                'resources/js/common/validation-core.js',
                'resources/js/dashboard/dashboard.js',
                'resources/js/dashboard/task-search.js',
                'resources/js/dashboard/group-task.js',
                'resources/js/reports/performance.js',
                'resources/js/profile/profile-validation.js',
                'resources/js/sidebar/sidebar-store.js',
                'resources/js/tags/tags.js',
                'resources/js/tasks/pending-approvals.js',
                'resources/js/tokens/history.js',
                'resources/js/tokens/purchase.js',
            ],
            refresh: true,
        }),
    ],
});