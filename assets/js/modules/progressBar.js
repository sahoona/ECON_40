window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.setupProgressBar = function() {
        const progressBar = document.getElementById('mybar');
        if (progressBar) {
            window.addEventListener('scroll', () => {
                const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - document.documentElement.clientHeight)) * 100;
                progressBar.style.width = scrollPercent + '%';
            });
        }
    }
})(window.gpChildTheme);
