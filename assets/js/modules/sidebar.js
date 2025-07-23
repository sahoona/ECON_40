window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.setupSidebar = function() {
        const sidebar = document.getElementById('gp-sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.querySelector('.sidebar-close');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (!sidebar || !sidebarToggle) {
            return;
        }

        function toggleSidebar(forceClose = false) {
            const isOpen = sidebar.classList.contains('gp-sidebar-visible');

            if (forceClose || isOpen) {
                sidebar.classList.remove('gp-sidebar-visible');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');

                setTimeout(() => {
                    if (!sidebar.classList.contains('gp-sidebar-visible')) {
                        sidebar.style.display = 'none';
                        if (sidebarOverlay) sidebarOverlay.style.display = 'none';
                    }
                }, 300);
            } else {
                sidebar.style.display = 'block';
                if (sidebarOverlay) sidebarOverlay.style.display = 'block';

                setTimeout(() => {
                    sidebar.classList.add('gp-sidebar-visible');
                    if (sidebarOverlay) sidebarOverlay.classList.add('active');
                    document.body.classList.add('sidebar-open');
                }, 10);

                cloneTocToSidebar();
            }
        }

        function cloneTocToSidebar() {
            const mainToc = document.getElementById('gp-toc-container');
            const sidebarTocContainer = document.querySelector('.sidebar-toc-container');

            if (!mainToc || !sidebarTocContainer) return;

            sidebarTocContainer.innerHTML = '';
            const clonedToc = mainToc.cloneNode(true);
            clonedToc.id = 'sidebar-toc-container';

            const links = clonedToc.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        if (window.innerWidth <= 768) {
                            toggleSidebar(true);
                        }

                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            sidebarTocContainer.appendChild(clonedToc);
        }

        // Event listeners
        sidebarToggle.addEventListener('click', () => toggleSidebar());
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => toggleSidebar(true));
        }
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => toggleSidebar(true));
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('gp-sidebar-visible')) {
                toggleSidebar(true);
            }
        });
    }
})(window.gpChildTheme);
