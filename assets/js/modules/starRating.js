window.gpChildTheme = window.gpChildTheme || {};

(function($, theme) {
    'use strict';

    theme.setupStarRating = function($) {
        const starRatingContainer = document.querySelector('.gp-star-rating-container');
        if (starRatingContainer) {
            const starsWrapper = starRatingContainer.querySelector('.stars-wrapper');
            const starsForeground = starRatingContainer.querySelector('.stars-foreground');
            const ratingText = starRatingContainer.querySelector('.rating-text');

            if (!starsWrapper || !starsForeground || !ratingText) {
                return;
            }

            const postId = starRatingContainer.dataset.postId;
            const storageKey = `gp_star_rating_${postId}`;
            let currentRating = parseFloat(localStorage.getItem(storageKey)) || 0;
            let tempRating = 0;
            const userRatingText = starRatingContainer.querySelector('.user-rating-text');
            const editRatingBtn = starRatingContainer.querySelector('.edit-rating-btn');
            const submitRatingBtn = starRatingContainer.querySelector('.submit-rating-btn');
            let initialAverage = parseFloat(ratingText.getAttribute('data-initial-average')) || 0;

            const updateUserRatingText = (rating) => {
                if (rating > 0) {
                    userRatingText.textContent = `You rated: ${rating.toFixed(1)}`;
                    userRatingText.style.display = 'block';
                } else {
                    userRatingText.style.display = 'none';
                }
            };

            if (currentRating > 0) {
                starRatingContainer.classList.add('voted');
                updateUserRatingText(currentRating);
            }

            const updateStars = (rating) => {
                const percentage = (rating / 5) * 100;
                starsForeground.style.width = `${percentage}%`;
            };

            const getRatingFromEvent = (e) => {
                const rect = starsWrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const width = rect.width;
                const preciseRating = (x / width) * 5;
                const halfStarRating = Math.round(preciseRating * 2) / 2;
                return Math.max(0.5, Math.min(5, halfStarRating));
            };

            starsWrapper.addEventListener('mousemove', function(e) {
                if (starRatingContainer.classList.contains('voted') && !starRatingContainer.classList.contains('editing')) return;
                const hoverRating = getRatingFromEvent(e);
                updateStars(hoverRating);
            });

            starsWrapper.addEventListener('mouseleave', function() {
                if (starRatingContainer.classList.contains('editing')) {
                    updateStars(tempRating);
                } else {
                    updateStars(initialAverage);
                }
            });

            starsWrapper.addEventListener('click', function(e) {
                if (starRatingContainer.classList.contains('voted') && !starRatingContainer.classList.contains('editing')) return;
                const newRating = getRatingFromEvent(e);
                tempRating = newRating;
                updateStars(newRating);
                if (!starRatingContainer.classList.contains('editing')) {
                    submitRating(newRating);
                }
            });

            if (editRatingBtn) {
                editRatingBtn.addEventListener('click', function() {
                    starRatingContainer.classList.add('editing');
                    starRatingContainer.classList.remove('voted');
                    tempRating = currentRating;
                    userRatingText.style.display = 'none';
                });
            }

            if (submitRatingBtn) {
                submitRatingBtn.addEventListener('click', function() {
                    if (tempRating > 0) { submitRating(tempRating); }
                });
            }

            function submitRating(ratingToSubmit) {
                const oldRating = parseFloat(localStorage.getItem(storageKey)) || 0;
                $.ajax({
                    url: gp_settings.ajax_url, type: 'POST',
                    data: { action: 'gp_handle_star_rating', post_id: postId, new_rating: ratingToSubmit, old_rating: oldRating, nonce: gp_settings.star_rating_nonce },
                    success: function(response) {
                        if (response.success) {
                            currentRating = ratingToSubmit;
                            localStorage.setItem(storageKey, currentRating.toString());
                            initialAverage = response.data.average;
                            const newVotes = response.data.votes;
                            ratingText.setAttribute('data-initial-average', initialAverage.toFixed(1));
                            ratingText.querySelector('span:first-child').textContent = initialAverage.toFixed(1);
                            ratingText.title = `${newVotes} votes`;
                            updateStars(ratingToSubmit);
                            updateUserRatingText(currentRating);
                            starRatingContainer.classList.remove('editing');
                            starRatingContainer.classList.add('voted');
                            starRatingContainer.classList.add('submitted');
                            setTimeout(() => { starRatingContainer.classList.remove('submitted'); }, 800);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Handle error
                    }
                });
            }
        }
    }
})(jQuery, window.gpChildTheme);
