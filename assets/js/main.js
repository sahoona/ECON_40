'use strict';

(function($) {
    // Initialize everything on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.gpChildTheme === 'object') {
            for (const func in window.gpChildTheme) {
                if (typeof window.gpChildTheme[func] === 'function') {
                    // Pass jQuery to functions that need it
                    if (['setupStarRating', 'setupReactionButtons', 'setupInfiniteScroll', 'setupSeriesLoadMoreButton'].includes(func)) {
                        window.gpChildTheme[func]($);
                    } else {
                        window.gpChildTheme[func]();
                    }
                }
            }
        }

        const emailPrivacyCheckbox = document.getElementById('wp-comment-email-privacy');
        const emailField = document.getElementById('email');

        if (emailPrivacyCheckbox && emailField) {
            const req = emailField.hasAttribute('aria-required');

            const updateEmailFieldState = () => {
                if (emailPrivacyCheckbox.checked) {
                    emailField.disabled = true;
                    emailField.required = false;
                    emailField.value = '';
                } else {
                    emailField.disabled = false;
                    if (req) {
                        emailField.required = true;
                    }
                }
            };

            updateEmailFieldState();
            emailPrivacyCheckbox.addEventListener('change', updateEmailFieldState);
        }

        // Smooth scroll to comment anchor
        if (window.location.hash && window.location.hash.indexOf('#comment-') === 0) {
            const commentId = window.location.hash;
            const commentElement = document.querySelector(commentId);
            if (commentElement) {
                setTimeout(() => {
                    commentElement.scrollIntoView({ behavior: 'smooth' });
                }, 500);
            }
        }
    });

    // Initialize ads after the window and all its resources have finished loading.
    window.onload = function() {
        if (window.gpChildTheme && typeof window.gpChildTheme.initAllAds === 'function') {
            window.gpChildTheme.initAllAds();
        }
    };

})(jQuery);
