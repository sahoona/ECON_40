window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.initDarkMode = function() {
        if (document.body.dataset.darkModeInitialized) {
            return;
        }
        document.body.dataset.darkModeInitialized = 'true';

        const htmlEl = document.documentElement;

        function setThemeState(isDark) {
            htmlEl.classList.toggle('dark-mode-active', isDark);
            localStorage.setItem('darkMode', isDark);
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                darkModeToggle.setAttribute('aria-pressed', isDark.toString());
            }
        }

        document.addEventListener('click', function(event) {
            const darkModeToggle = event.target.closest('#darkModeToggle');
            if (darkModeToggle) {
                const isCurrentlyDark = htmlEl.classList.contains('dark-mode-active');
                setThemeState(!isCurrentlyDark);
            }
        });

        // Set initial aria-pressed state
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            const isDark = localStorage.getItem('darkMode') === 'true';
            darkModeToggle.setAttribute('aria-pressed', isDark.toString());
        }
    }
})(window.gpChildTheme);
