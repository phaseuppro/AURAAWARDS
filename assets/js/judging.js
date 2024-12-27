jQuery(document).ready(function ($) {
    let currentPhotoId = null;

    function calculateJuryPoints() {
        let totalStars = 0;
        $('.rating-stars').each(function () {
            const stars = $(this).find('.active').length;
            totalStars += stars;
        });

        const juryPoints = totalStars * 4;
        const badge = getBadgeType(juryPoints);

        $('#jury-points').text(`Jury Points: ${juryPoints}`);
        $('#badge-result').text(`Assigned Badge: ${badge}`);

        const position = $('#badge-panel button.active').data('position');
        if (position) {
            previewBadge(position, badge.toLowerCase());
        }
    }

    function getBadgeType(points) {
        if (points >= 90) return 'Platinum';
        if (points >= 70) return 'Gold';
        if (points >= 50) return 'Silver';
        if (points >= 30) return 'Bronze';
        return 'Participant';
    }

    function previewBadge(position, badge) {
        if (!$('#selected-photo img').length) return;
        
        const badgeUrl = `${auraJudging.badgesUrl}${badge}-badge.png`;
        $('.badge-preview').remove();
        
        const preview = $('<img>', {
            class: 'badge-preview',
            src: badgeUrl,
            css: {
                position: 'absolute',
                width: '100px',
                height: 'auto',
                ...getPositionStyles(position)
            }
        });
        
        $('#selected-photo').css('position', 'relative').append(preview);
    }

    function getPositionStyles(position) {
        const positions = {
            'top-left': { top: '20px', left: '20px' },
            'top-right': { top: '20px', right: '20px' },
            'bottom-left': { bottom: '20px', left: '20px' },
            'bottom-right': { bottom: '20px', right: '20px' }
        };
        return positions[position] || positions['top-left'];
    }

    function loadThumbnails() {
        $.ajax({
            url: auraJudging.ajaxurl,
            method: 'POST',
            data: {
                action: 'aura_load_thumbnails',
                nonce: auraJudging.nonce
            },
            success: function(response) {
                if (response.success) {
                    const thumbnailList = $('#thumbnail-list');
                    thumbnailList.empty();
                    response.data.forEach(function(thumb) {
                        thumbnailList.append(`
                            <img src="${thumb.thumbnail}" 
                                class="thumbnail" 
                                data-id="${thumb.id}" 
                                data-fullsize="${thumb.fullsize}" 
                                title="${thumb.title}" />
                        `);
                    });
                }
            },
            error: function() {
                alert('Failed to load thumbnails.');
            }
        });
    }

    $('#thumbnail-list').on('click', '.thumbnail', function() {
        currentPhotoId = $(this).data('id');
        const fullsizeSrc = $(this).data('fullsize');

        $('.thumbnail').removeClass('active');
        $(this).addClass('active');

        $('#selected-photo')
            .html(`<img src="${fullsizeSrc}" data-id="${currentPhotoId}" class="full-size-image" />`);

        const position = $('#badge-panel button.active').data('position');
        if (position) {
            const currentPoints = parseInt($('#jury-points').text().match(/\d+/)[0]);
            const badge = getBadgeType(currentPoints);
            previewBadge(position, badge.toLowerCase());
        }
    });

    $('.rating-stars').on('click', 'span', function() {
        $(this)
            .siblings()
            .removeClass('active')
            .end()
            .prevAll()
            .addBack()
            .addClass('active');

        calculateJuryPoints();
    });

    $('#badge-panel button').on('click', function() {
        $('#badge-panel button').removeClass('active');
        $(this).addClass('active');
        
        const position = $(this).data('position');
        const currentPoints = parseInt($('#jury-points').text().match(/\d+/)[0]);
        const badge = getBadgeType(currentPoints);
        
        previewBadge(position, badge.toLowerCase());
    });

    $('#reject-submission').on('click', function() {
        if (!currentPhotoId) {
            alert('Please select a photo to reject.');
            return;
        }

        if (!confirm('Are you sure you want to reject this submission? This action is irreversible.')) {
            return;
        }

        $.ajax({
            url: auraJudging.ajaxurl,
            method: 'POST',
            data: {
                action: 'aura_reject_submission',
                nonce: auraJudging.nonce,
                post_id: currentPhotoId
            },
            success: function(response) {
                if (response.success) {
                    resetInterface();
                    loadThumbnails();
                    alert('Submission rejected successfully.');
                } else {
                    alert(response.data || 'Failed to reject submission.');
                }
            },
            error: function() {
                alert('An error occurred while rejecting the submission.');
            }
        });
    });

    $('#judge-save').on('click', function() {
        if (!currentPhotoId) {
            alert('Please select a photo to judge.');
            return;
        }

        const ratings = {};
        $('.rating-stars').each(function() {
            ratings[$(this).data('criterion')] = $(this).find('.active').length || 0;
        });

        const badgePosition = $('#badge-panel button.active').data('position');
        if (!badgePosition) {
            alert('Please select a badge position.');
            return;
        }

        $.ajax({
            url: auraJudging.ajaxurl,
            method: 'POST',
            data: {
                action: 'aura_save_judgment',
                nonce: auraJudging.nonce,
                post_id: currentPhotoId,
                ratings: ratings,
                position: badgePosition
            },
            success: function(response) {
                if (response.success) {
                    resetInterface();
                    loadThumbnails();
                    alert('Judgment saved successfully.');
                } else {
                    alert(response.data || 'Failed to save judgment.');
                }
            },
            error: function() {
                alert('An error occurred while saving the judgment.');
            }
        });
    });

    function resetInterface() {
        currentPhotoId = null;
        $('#selected-photo').empty();
        $('.rating-stars span').removeClass('active');
        $('#badge-panel button').removeClass('active');
        $('.badge-preview').remove();
        $('#jury-points').text('Jury Points: 0');
        $('#badge-result').text('Assigned Badge: Participant');
    }

    loadThumbnails();
});
