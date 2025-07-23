window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.setupPostedDateToggles = function() {
        const postedOnWrappers = document.querySelectorAll('.posted-on-wrapper');

        postedOnWrappers.forEach(function (wrapper) {
            const datePrimary = wrapper.querySelector('.date-primary');
            const dateSecondary = wrapper.querySelector('.date-secondary');

            const isUpdatable = dateSecondary && datePrimary && datePrimary.innerHTML.trim() !== dateSecondary.innerHTML.trim();

            if (isUpdatable) {
                if (!wrapper.classList.contains('is-updatable')) { // Ensure class is present
                    wrapper.classList.add('is-updatable');
                }
                if (!wrapper.hasAttribute('tabindex')) {
                    wrapper.setAttribute('tabindex', '0');
                }
                if (!wrapper.hasAttribute('role')) {
                    wrapper.setAttribute('role', 'button');
                }
                // Initialize aria-pressed based on current visibility state (presence of 'state-published-visible')
                wrapper.setAttribute('aria-pressed', wrapper.classList.contains('state-published-visible').toString());

                const togglePublishedDate = function () {
                    this.classList.toggle('state-published-visible');
                    const isPublishedVisible = this.classList.contains('state-published-visible');
                    this.setAttribute('aria-pressed', isPublishedVisible.toString());
                };

                const handleKeyboardToggle = function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        togglePublishedDate.call(this);
                    }
                };

                // Prevent multiple listeners if script runs multiple times (e.g., AJAX load)
                if (!wrapper.dataset.dateToggleListenerAttached) {
                    wrapper.addEventListener('click', togglePublishedDate);
                    wrapper.addEventListener('keydown', handleKeyboardToggle);
                    wrapper.dataset.dateToggleListenerAttached = 'true';
                }

            } else {
                wrapper.classList.remove('is-updatable');
                wrapper.removeAttribute('tabindex');
                wrapper.removeAttribute('role');
                wrapper.removeAttribute('aria-pressed');
                // If listeners were attached with named functions, they could be removed here
                // For simplicity, if not updatable, attributes are removed, making it non-interactive
                delete wrapper.dataset.dateToggleListenerAttached;
            }
        });
    }

    // Helper function to announce messages to screen readers
    function announceToScreenReader(message) {
        let announcer = document.getElementById('gp-screen-reader-announcer');
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'gp-screen-reader-announcer';
            announcer.style.position = 'absolute';
            announcer.style.left = '-10000px';
            announcer.style.top = 'auto';
            announcer.style.width = '1px';
            announcer.style.height = '1px';
            announcer.style.overflow = 'hidden';
            announcer.setAttribute('aria-live', 'assertive');
            announcer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcer);
        }
        announcer.textContent = message;
        setTimeout(() => {
            announcer.textContent = '';
        }, 3000); // Clear after 3 seconds
    }

    theme.setupLanguageToggle = function() {
        const switcherContainer = document.getElementById('gp-language-switcher');
        if (!switcherContainer) {
            return;
        }

        const toggleButton = document.getElementById('gp-lang-switcher-button');
        const languageList = document.getElementById('gp-lang-switcher-list');

        if (!toggleButton || !languageList) {
            return;
        }

        toggleButton.addEventListener('click', function(event) {
            event.stopPropagation();

            const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                languageList.setAttribute('hidden', '');
                toggleButton.setAttribute('aria-expanded', 'false');
                switcherContainer.classList.remove('active');
            } else {
                languageList.removeAttribute('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                switcherContainer.classList.add('active');
            }
        });

        document.addEventListener('click', function(event) {
            if (!switcherContainer.contains(event.target)) {
                if (toggleButton.getAttribute('aria-expanded') === 'true') {
                    languageList.setAttribute('hidden', '');
                    toggleButton.setAttribute('aria-expanded', 'false');
                    switcherContainer.classList.remove('active');
                }
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (toggleButton.getAttribute('aria-expanded') === 'true') {
                    languageList.setAttribute('hidden', '');
                    toggleButton.setAttribute('aria-expanded', 'false');
                    switcherContainer.classList.remove('active');
                    toggleButton.focus();
                }
            }
        });
    }

    theme.setupCodeCopyButtons = function() {
        document.querySelectorAll('pre').forEach(pre => {
            const button = document.createElement('button');
            button.className = 'copy-code-button';
            button.textContent = '코드 복사'; // Use textContent for security
            button.setAttribute('aria-label', '코드 블록 내용 복사');
            pre.appendChild(button);

            button.addEventListener('click', () => {
                const code = pre.querySelector('code');
                if (code) {
                    navigator.clipboard.writeText(code.innerText).then(() => {
                        button.textContent = '복사됨!';
                        announceToScreenReader('코드가 클립보드에 복사되었습니다.');
                        setTimeout(() => {
                            button.textContent = '코드 복사';
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy text: ', err);
                        announceToScreenReader('코드 복사에 실패했습니다.');
                    });
                }
            });
        });
    }

})(window.gpChildTheme);
