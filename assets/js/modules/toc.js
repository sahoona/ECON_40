window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.generateClientSideTOC = function() {
        const tocContainer = document.getElementById('gp-toc-container');
        if (!tocContainer) { return; }

        const tocList = tocContainer.querySelector('.gp-toc-list');
    if (!tocList) { tocContainer.style.display = 'none'; return; }

    const contentSelectors = [
        'article.post .entry-content', '.post-content', '.single-post-content',
        'main[role="main"] article', '#main article'
    ];
    let mainContent = null;
    for (const selector of contentSelectors) {
        mainContent = document.querySelector(selector);
        if (mainContent) break;
    }

    if (!mainContent) { tocContainer.style.display = 'none'; return; }

    let enabled_levels = [];
    if (typeof gp_settings !== 'undefined' && typeof gp_settings.toc_settings !== 'undefined') {
        if (gp_settings.toc_settings.h2 === 'on') enabled_levels.push('h2');
        if (gp_settings.toc_settings.h3 === 'on') enabled_levels.push('h3');
        if (gp_settings.toc_settings.h4 === 'on') enabled_levels.push('h4');
        if (gp_settings.toc_settings.h5 === 'on') enabled_levels.push('h5');
        if (gp_settings.toc_settings.h6 === 'on') enabled_levels.push('h6');
    } else {
        enabled_levels.push('h2');
        enabled_levels.push('h3');
    }

    if (enabled_levels.length === 0) {
        tocContainer.style.display = 'none';
        return;
    }
    const headingSelector = enabled_levels.join(', ');

    const headingsNodeList = mainContent.querySelectorAll(headingSelector);
    const headings = Array.from(headingsNodeList).filter(heading => {
        // Common YARPP wrapper class selectors. Add more if known.
        const yarppSelectors = '.yarpp-related, .yarpp-related-widget, .yarpp-related-posts, .yarpp-related-content';
        return !heading.closest(yarppSelectors);
    });
    if (headings.length === 0) {
        tocContainer.style.display = 'none';
        return;
    }

    tocList.innerHTML = '';
    // tocList.setAttribute('itemscope', ''); // Note: itemscope is on the nav container in PHP now
    // tocList.setAttribute('itemtype', 'https://schema.org/ItemList'); // This is also on the nav container
    let positionCounter = 1;
    let idCounters = {};

    const parentLists = [tocList]; // A stack to keep track of parent <ol> elements

    headings.forEach(heading => {
        const rawTitle = heading.textContent.trim();
        if (!rawTitle) return;

        let baseId = rawTitle.toLowerCase().replace(/[^\p{L}\p{N}\s-]/gu, '').replace(/\s+/g, '-').replace(/-+/g, '-');
        if (!baseId) baseId = 'toc-item';
        let uniqueId = baseId;
        if (idCounters[baseId] !== undefined) {
            idCounters[baseId]++;
            uniqueId = baseId + '-' + idCounters[baseId];
        } else {
            idCounters[baseId] = 1;
        }
        heading.id = uniqueId;

        const listItem = document.createElement('li');
        listItem.setAttribute('itemscope', '');
        listItem.setAttribute('itemprop', 'itemListElement');
        listItem.setAttribute('itemtype', 'https://schema.org/ListItem');

        const metaPosition = document.createElement('meta');
        metaPosition.setAttribute('itemprop', 'position');
        metaPosition.setAttribute('content', positionCounter.toString());
        listItem.appendChild(metaPosition);

        const metaUrl = document.createElement('meta');
        metaUrl.setAttribute('itemprop', 'url');
        const pageUrl = window.location.href.split('#')[0];
        metaUrl.setAttribute('content', pageUrl + '#' + uniqueId);
        listItem.appendChild(metaUrl);

        const link = document.createElement('a');
        link.href = '#' + uniqueId;
        link.textContent = rawTitle;
        link.setAttribute('itemprop', 'name');

        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetElement = document.getElementById(this.getAttribute('href').substring(1));
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        listItem.appendChild(link);

        const level = parseInt(heading.tagName.substring(1));
        listItem.classList.add('toc-heading-level-' + level);

        // A "level" is defined by its position in the array. Level 2 is at index 1, level 3 at index 2, etc.
        const parentLevel = level - 2; // h2 -> 0, h3 -> 1, h4 -> 2

        // Go back up the hierarchy until we find the correct parent list
        while (parentLists.length > parentLevel + 1) {
            parentLists.pop();
        }

        // Get the correct parent <ol>
        let parentList = parentLists[parentLists.length - 1];
        if (!parentList) {
             // Fallback to the root list if something is wrong
             parentList = tocList;
        }

        // Append the new <li> to its parent <ol>
        parentList.appendChild(listItem);

        // If this heading can have children, create a new <ol> for them and push it to the stack
        const nextHeading = headings[headings.indexOf(heading) + 1];
        if (nextHeading && parseInt(nextHeading.tagName.substring(1)) > level) {
            const newList = document.createElement('ol');
            listItem.appendChild(newList);
            parentLists.push(newList);
        }

        positionCounter++;
    });

    if (tocList.children.length > 0) {
        tocContainer.style.display = '';

        // Setup TOC interactivity
        const tocTitle = tocContainer.querySelector('.gp-toc-title');
        const tocToggle = tocContainer.querySelector('.gp-toc-toggle');

        if (tocTitle && tocList) {
            if (tocTitle.dataset.listenerAttached !== 'true') {
                tocTitle.style.cursor = 'pointer';
                tocTitle.addEventListener('click', function(e) {
                    e.preventDefault();
                    tocList.classList.toggle('toc-list-hidden');
                    const isHidden = tocList.classList.contains('toc-list-hidden');
                    const showMoreContainer = tocContainer.querySelector('.gp-toc-show-more-container');

                    if (showMoreContainer) {
                        showMoreContainer.style.display = isHidden ? 'none' : 'block';
                    }

                    if (tocToggle) {
                        if (isHidden) {
                            tocToggle.classList.remove('show');
                            tocToggle.textContent = '[SHOW]';
                        } else {
                            tocToggle.classList.add('show');
                            tocToggle.textContent = '[HIDE]';
                        }
                    }
                });
                tocTitle.dataset.listenerAttached = 'true';
            }
        }

        // "Show More" functionality
        const tocHeight = tocList.offsetHeight;
        if (tocHeight > 400) {
            tocList.classList.add('toc-collapsed');
            const showMoreContainer = document.createElement('div');
            showMoreContainer.classList.add('gp-toc-show-more-container');

            const showMoreButton = document.createElement('button');
            showMoreButton.innerHTML = '<span>View More</span><div class="arrow-wrapper"><span class="arrow down"></span></div>';
            showMoreButton.classList.add('gp-toc-show-more-button', 'gp-toc-glass-button');
            showMoreButton.style.display = 'flex';

            const hideButton = document.createElement('button');
            hideButton.innerHTML = '<div class="arrow-wrapper"><span class="arrow up"></span></div><span>Hide</span>';
            hideButton.classList.add('gp-toc-hide-button', 'gp-toc-glass-button');
            hideButton.style.display = 'none';

            showMoreContainer.appendChild(showMoreButton);
            showMoreContainer.appendChild(hideButton);
            tocContainer.parentNode.insertBefore(showMoreContainer, tocContainer.nextSibling);

            const toggleButtons = () => {
                const isCollapsed = tocList.classList.contains('toc-collapsed');
                showMoreButton.style.display = isCollapsed ? 'block' : 'none';
                hideButton.style.display = isCollapsed ? 'none' : 'block';
            };

            showMoreButton.addEventListener('click', function(e) {
                e.preventDefault();
                tocList.classList.remove('toc-collapsed');
                tocList.classList.add('toc-expanded');
                toggleButtons();
            });

            hideButton.addEventListener('click', function(e) {
                e.preventDefault();
                tocList.classList.remove('toc-expanded');
                tocList.classList.add('toc-collapsed');
                toggleButtons();
            });
        }
    } else {
        tocContainer.style.display = 'none';
    }
    }
})(window.gpChildTheme);
