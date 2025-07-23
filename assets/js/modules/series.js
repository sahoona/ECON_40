window.gpChildTheme = window.gpChildTheme || {};

(function ($, theme) {
    'use strict';

    const gp_settings = window.gp_settings || {};
    let isLoading = false;
    let clickCount = 0;
    const maxClicks = 3;

    function initializeLazyLoadForSeries($container) {
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

    function setupSeriesLoader() {
        let loadedPostHrefs = $('.series-posts-grid .series-post-item').map(function() {
            return $(this).attr('href');
        }).get();

        $(document).on('click', '#load-more-series-btn', function(event) {
            event.preventDefault();
            const $button = $(this);

            if (isLoading) return;
            if (clickCount >= maxClicks) {
                $button.text('No More').prop('disabled', true);
                return;
            }

            isLoading = true;
            $button.text('Loading...').prop('disabled', true);

            const currentPostId = gp_settings.current_post_id;
            const postsPerPage = parseInt($('.series-posts-grid').data('posts-per-page'), 10) || 12;

            $.ajax({
                url: gp_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_more_series_posts',
                    nonce: gp_settings.load_more_series_nonce,
                    current_post_id: currentPostId,
                    posts_per_page: postsPerPage,
                    loaded_post_ids: loadedPostHrefs // Pass the array of already loaded post hrefs
                },
                success: function(response) {
                    if (response.success && response.data.html.trim() !== '') {
                        const $newPosts = $(response.data.html);
                        $('.series-posts-grid').append($newPosts);
                        initializeLazyLoadForSeries($newPosts);

                        // Update the list of loaded post hrefs
                        $newPosts.each(function() {
                            const postHref = $(this).attr('href');
                            if (postHref) {
                                loadedPostHrefs.push(postHref);
                            }
                        });

                        clickCount++;

                        if (clickCount >= maxClicks || !response.data.has_more) {
                            $button.text('No More').prop('disabled', true);
                        } else {
                            $button.text('Series More').prop('disabled', false);
                        }
                    } else {
                        $button.text('No More').prop('disabled', true);
                    }
                },
                error: function() {
                    $button.text('Error. Try Again?').prop('disabled', false);
                },
                complete: function() {
                    isLoading = false;
                }
            });
        });
    }

    $(function() {
        if ($('#load-more-series-btn').length > 0) {
            setupSeriesLoader();
        }
    });

})(jQuery, window.gpChildTheme);
