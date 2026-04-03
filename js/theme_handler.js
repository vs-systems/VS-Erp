/**
 * theme_handler.js - Gestor de temas Light/Dark para VS System
 * Aplica el tema basado en localStorage o preferencias del sistema.
 */

(function () {
    function applyTheme() {
        const sysDefault = window.vsys_default_theme || 'auto';
        const theme = localStorage.getItem('vsys_theme') || sysDefault;
        const html = document.documentElement;

        if (theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    }

    // Ejecutar inmediatamente
    applyTheme();

    // Escuchar cambios del sistema si el modo es auto
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('vsys_theme') === 'auto') {
            applyTheme();
        }
    });

    // Función global para cambiar el tema
    window.setVsysTheme = function (newTheme) {
        localStorage.setItem('vsys_theme', newTheme);
        applyTheme();
    };
})();
