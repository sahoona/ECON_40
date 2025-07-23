window.gpChildTheme = window.gpChildTheme || {};

(function(theme) {
    'use strict';

    /**
     * 광고 슬롯을 찾아 푸시하는 핵심 함수
     */
    function runAds() {
        try {
            const adSlots = document.querySelectorAll('ins.adsbygoogle:not(.ad-initialized)');
            if (adSlots.length === 0) {
                return;
            }

            adSlots.forEach(slot => {
                // 광고 슬롯이 실제로 화면에 보일 때만 광고를 요청 (더 효율적)
                if (slot.offsetParent !== null) {
                    (window.adsbygoogle = window.adsbygoogle || []).push({});
                    slot.classList.add('ad-initialized');
                }
            });
        } catch (e) {
        }
    }

    /**
     * 애드센스 스크립트가 준비되면 광고 실행 및 동적 콘텐츠 감지를 시작하는 함수
     */
    function initializeAdsAndObserver() {
        console.log('✅ 애드센스 스크립트 로드 확인! 광고 실행 및 동적 로딩 감지를 시작합니다.');

        // 1. 페이지에 이미 있는 광고를 즉시 실행
        runAds();

        // 2. 나중에 동적으로 추가되는 콘텐츠(예: '더보기' 버튼으로 로드되는 글) 내부의 광고를 감지하기 위한 MutationObserver 설정
        const observer = new MutationObserver(mutations => {
            for (const mutation of mutations) {
                if (mutation.addedNodes.length > 0) {
                    // 새로 추가된 요소가 있으면, 그 안에 광고 슬롯이 있는지 다시 확인
                    runAds();
                    break; // 한 번의 변화에 여러 번 실행될 필요 없으므로 중단
                }
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // 페이지를 떠날 때 observer의 감지를 중단하여 리소스 누수 방지
        window.addEventListener('beforeunload', () => {
            if (observer) {
                observer.disconnect();
            }
        });
    }

    /**
     * [핵심 로직] window.adsbygoogle 객체가 생성될 때까지 주기적으로 확인하는 함수
     */
    function waitForAdsbygoogle() {
        // 20번 시도 (10초) 후에도 adsbygoogle 객체가 없으면 포기 (안전장치)
        if (waitForAdsbygoogle.attempts > 20) {
            console.error('❌ 애드센스 스크립트를 10초 이상 기다렸지만 로드되지 않았습니다. 광고 차단 플러그인 등을 확인해 주세요.');
            return;
        }

        if (window.adsbygoogle) {
            // 객체가 존재하면 모든 광고 로직을 초기화!
            initializeAdsAndObserver();
        } else {
            // 객체가 아직 없으면 0.5초 후에 다시 시도
            console.log(`⏳ 애드센스 스크립트 로딩 대기 중... (시도: ${waitForAdsbygoogle.attempts})`);
            waitForAdsbygoogle.attempts++;
            setTimeout(waitForAdsbygoogle, 500);
        }
    }
    // 시도 횟수를 저장할 카운터 초기화
    waitForAdsbygoogle.attempts = 1;


    // 페이지의 기본 HTML 구조가 로드되면 "기다리기" 시작
    document.addEventListener('DOMContentLoaded', waitForAdsbygoogle);

})(window.gpChildTheme);
