window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.setupFloatingButtons = function() {
        const floatingButtonsContainer = document.querySelector('.floating-buttons-container');
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');

        if (!floatingButtonsContainer || !scrollToTopBtn) {
            return;
        }

        // Scroll to top functionality
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Hide/show based on scroll activity
        let timeout;
        window.addEventListener('scroll', () => {
            floatingButtonsContainer.classList.add('show-back-to-top');
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                floatingButtonsContainer.classList.remove('show-back-to-top');
            }, 2000);
        });
    }
})(window.gpChildTheme);
