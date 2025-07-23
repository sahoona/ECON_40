window.gpChildTheme = window.gpChildTheme || {};

(function($, theme) {
    'use strict';

    theme.setupReactionButtons = function($) {
        document.querySelectorAll('.reaction-btn').forEach(button => {
            const postId = button.dataset.postId;
            const cooldownKey = `gpCooldown_${postId}`;
            const setCooldownState = (isCoolingDown) => {
                document.querySelectorAll(`.reaction-btn[data-post-id="${postId}"]`).forEach(btn => {
                    btn.classList.toggle('cooldown', isCoolingDown);
                    btn.disabled = isCoolingDown;
                });
            };
            const checkCooldown = () => {
                const cooldownEndTime = localStorage.getItem(cooldownKey);
                if (cooldownEndTime && Date.now() < cooldownEndTime) {
                    setCooldownState(true);
                    setTimeout(() => { setCooldownState(false); }, cooldownEndTime - Date.now());
                    return true;
                }
                setCooldownState(false);
                return false;
            };
            checkCooldown();
            button.addEventListener('click', function() {
                if (this.disabled) return;
                const reaction = this.dataset.reaction;
                const countSpan = this.querySelector('.reaction-count');
                countSpan.textContent = parseInt(countSpan.textContent) + 1;
                const cooldownDuration = 10000;
                localStorage.setItem(cooldownKey, Date.now() + cooldownDuration);
                setCooldownState(true);
                setTimeout(() => setCooldownState(false), cooldownDuration);
                $.ajax({
                    url: gp_settings.ajax_url, type: 'POST',
                    data: { action: 'gp_handle_reaction', post_id: postId, reaction: reaction, nonce: gp_settings.reactions_nonce },
                    error: () => {
                        countSpan.textContent = parseInt(countSpan.textContent) - 1;
                        localStorage.removeItem(cooldownKey);
                        setCooldownState(false);
                    }
                });
            });
        });
    }
})(jQuery, window.gpChildTheme);
