jQuery(document).ready(function ($) {
    const form = $('#aura-submission-form');
    const fileInput = $('#photo-upload');
    const progressBar = $('.progress-bar');
    const previewImage = $('#preview-image');
    const successMessageContainer = $('#aura-submission-messages');
    const creditsContainer = $('.aura-credits-info');

    // Function to update credits and maintain correct messages
    function updateCredits() {
        $.ajax({
            url: auraSubmission.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_credits',
                nonce: auraSubmission.nonce
            },
            success: function (response) {
                if (response.success) {
                    const credits = parseInt(response.data.credits, 10);
                    if (credits < 1) {
                        creditsContainer.html(
                            'You need credits to submit photos. <a href="https://aura-awards.com/product-category/submission-credits/">Purchase credits</a>'
                        );
                    } else {
                        creditsContainer.text(`Available Credits: ${credits}`);
                    }
                } else {
                    console.error('Failed to update credits:', response.data);
                    creditsContainer.text('Error fetching credits');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error while updating credits:', error);
                creditsContainer.text('Error fetching credits');
            }
        });
    }

    // Call updateCredits() on document ready
    updateCredits();

    // Validate file on change
    fileInput.on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage
                    .attr('src', e.target.result)
                    .css('display', 'block');
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.hide();
        }
    });

    // Submit form via AJAX
    form.on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'aura_submit_photo');
        formData.append('nonce', auraSubmission.nonce);

        $.ajax({
            url: auraSubmission.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('.upload-progress').show();
                $('.aura-submit-btn').prop('disabled', true).text('Uploading...');
                successMessageContainer.empty();
            },
            xhr: function () {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener(
                    'progress',
                    function (evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            progressBar.css('width', percentComplete + '%');
                        }
                    },
                    false
                );
                return xhr;
            },
            success: function (response) {
                if (response.success) {
                    successMessageContainer.html('<div class="aura-message aura-success">Upload Successful!</div>');
                    previewImage.hide();
                    form[0].reset();
                    progressBar.css('width', '0%');
                    updateCredits();
                } else {
                    successMessageContainer.html(
                        `<div class="aura-message aura-error">${response.data || 'An error occurred.'}</div>`
                    );
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                successMessageContainer.html('<div class="aura-message aura-error">An error occurred during upload. Please try again.</div>');
            },
            complete: function () {
                $('.upload-progress').hide();
                $('.aura-submit-btn').prop('disabled', false).text('Submit Photo');
            }
        });
    });
});
