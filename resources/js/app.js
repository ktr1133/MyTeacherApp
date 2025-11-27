import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import './dashboard/dashboard.js';
import './dashboard/task-modal.js';

Alpine.plugin(focus);
window.Alpine = Alpine;
// DOMContentLoaded後にAlpineを起動
document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});