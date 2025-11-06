import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import './dashboard/dashboard.js';

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();