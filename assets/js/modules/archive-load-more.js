(function($) {
    function initializeLazyLoad($container) {
        if (!('IntersectionObserver' in window)) {
            $container.find('img.lazy-load').each(function() {
                if (this.dataset.src) {
                    this.src = this.dataset.src;
                }
                $(this).removeClass('lazy-load');
            });
            return;
        }

        const lazyImages = $container.find('img.lazy-load').toArray();
        if (lazyImages.length === 0) return;

        const lazyImageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const lazyImage = entry.target;
                    if (lazyImage.dataset.src) {
                        lazyImage.src = lazyImage.dataset.src;
                    }
                    lazyImage.classList.remove('lazy-load');
                    observer.unobserve(lazyImage);
                }
            });
        });

        lazyImages.forEach(function(lazyImage) {
            lazyImageObserver.observe(lazyImage);
        });
    }

    $(function() {
        // Only run on archive pages and if the load more button exists.
        if (!$('body').is('.archive') || $('#gp-posts-loader-btn').length === 0) {
            return;
        }

        let currentPage = 2; // Start with page 2, as page 1 is already loaded.
        let isLoading = false;

        $('#gp-posts-loader-btn').on('click', function() {
            const $button = $(this);

            if (isLoading) {
                return;
            }

            isLoading = true;
            $button.text('Loading...');

            $.ajax({
                url: gp_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_more_archive_posts',
                    nonce: gp_settings.load_more_archive_nonce,
                    page: currentPage,
                    query_vars: gp_settings.query_vars
                },
                success: function(response) {
                    if (response.success && response.data.html.trim() !== '') {
                        const $newPosts = $(response.data.html);
                        $('.archive-posts-grid').append($newPosts);
                        initializeLazyLoad($newPosts);
                        currentPage++;
                        if (!response.data.has_more) {
                            $button.text('No more posts').prop('disabled', true);
                        } else {
                            $button.text('Load More');
                        }
                    } else {
                        $button.text('No more posts').prop('disabled', true);
                    }
                },
                error: function() {
                    $button.text('Error. Try Again?');
                },
                complete: function() {
                    isLoading = false;
                }
            });
        });
    });
})(jQuery);
