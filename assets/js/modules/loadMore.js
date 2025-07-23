window.gpChildTheme = window.gpChildTheme || {};

(function ($, theme) {
    'use strict';

    const gp_settings = window.gp_settings || {};
    let currentPage = 1;
    let isLoading = false;
    let noMorePosts = false;

    /**
     * 새로 추가된 이미지에 대한 지연 로딩을 처리하는 독립적인 함수
     * @param {jQuery} $container - 새로 추가된 게시물을 감싸는 jQuery 객체
     */
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

    function setupInfiniteScroll() {
        $(document).on('click', '#gp-posts-loader-btn', function (event) {
            event.preventDefault();
            if (isLoading || noMorePosts) return;

            const $button = $(this);
            isLoading = true;
            $button.text('Loading...').prop('disabled', true);

            $.ajax({
                url: gp_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_more_posts_v2', // v2 액션 호출
                    page: currentPage + 1,
                    // [최종 수정] 서버에 보낼 Nonce의 '상자' 이름을 v2로 명확히 지정합니다.
                    '_wpnonce_v2': gp_settings._wpnonce_v2
                },
                success: function (response) {
                    if (response.success) {
                        $('.gp-posts-loader-container').remove();

                        if (response.data.html && response.data.html.trim() !== '') {
                            currentPage++;
                            const $newPosts = $(response.data.html);
                            $('.site-main').append($newPosts);
                            initializeLazyLoad($('.site-main'));
                        }

                        if (response.data.button_html && response.data.button_html.trim() !== '') {
                            $('.site-main').append(response.data.button_html);
                        } else {
                            noMorePosts = true;
                            if (!$('#no-more-posts-message').length) {
                                $('.site-main').after('<p id="no-more-posts-message" style="text-align: center;">This is the last page.</p>');
                            }
                        }
                    } else {
                        $button.text(response.data.message || 'Error. Try Again?');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("===== AJAX 요청 실패 상세 정보 =====");
                    console.error("HTTP 상태 코드: ", jqXHR.status);
                    console.error("상태 메시지: ", textStatus);
                    console.error("서버 에러: ", errorThrown);
                    console.error("서버 응답 내용:", jqXHR.responseText);
                    console.error("=====================================");
                    $button.text('에러 발생! (콘솔 확인)').prop('disabled', false);
                },
                complete: function () {
                    isLoading = false;
                }
            });
        });
    }

    $(function() {
        if ($('#gp-posts-loader-btn').length) {
            setupInfiniteScroll();
        }
    });

})(jQuery, window.gpChildTheme);