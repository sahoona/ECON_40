window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    theme.setupURLCopy = function() {
        document.body.addEventListener('click', function(event) {
            const topCopyBtn = event.target.closest('.gp-copy-url-btn');
            const bottomCopyBtn = event.target.closest('.social-share-btn.gp-custom-copy-bottom-btn');

            let buttonToAnimate = null;

            if (topCopyBtn) {
                event.preventDefault();
                buttonToAnimate = topCopyBtn;
            } else if (bottomCopyBtn) {
                event.preventDefault();
                buttonToAnimate = bottomCopyBtn;
            }

            if (buttonToAnimate) {
                const urlToCopy = window.location.href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(urlToCopy).then(() => {
                        buttonToAnimate.classList.add('copied');
                        setTimeout(() => {
                            buttonToAnimate.classList.remove('copied');
                        }, 2000);
                    }).catch(err => {
                        alert('URL 복사에 실패했습니다.');
                    });
                } else {
                    alert('클립보드 복사 기능을 지원하지 않는 브라우저입니다.');
                }
            }
        });
    }
})(window.gpChildTheme);
