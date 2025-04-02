jQuery(document).ready(function($) {

    // --- Tab Switching ---
    $('.jd-tabs .jd-tab').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).data('tab');

        // Remove active classes from all tabs and their corresponding contents.
        $('.jd-tabs .jd-tab').removeClass('active');
        $('.jd-tab-content').removeClass('active');

        // Activate the clicked tab and display its content.
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });

    // --- Preview Duplicate Handling ---
    $('body').on('click', '.preview-duplicate', function(e) {
        e.preventDefault();
        var previewUrl = $(this).data('preview-url');

        $.get(previewUrl, function(response) {
            if (response.success) {
                // Build the modal HTML with the preview data, including a close button.
                var modalHtml = '<div class="preview-modal-overlay">' +
                                    '<div class="preview-modal">' +
                                        '<span class="preview-modal-close">&times;</span>' +
                                        '<h2>' + response.data.title + '</h2>' +
                                        '<p><strong>' + JustDuplicateL10n.author + ':</strong> ' + response.data.author + '</p>' +
                                        '<p><strong>' + JustDuplicateL10n.date + ':</strong> ' + response.data.date + '</p>' +
                                        '<div class="preview-content">' + response.data.content + '</div>' +
                                        '<div class="preview-actions">' +
                                            '<button class="button confirm-duplicate" data-duplicate-url="' + response.data.duplicate_url + '">' + JustDuplicateL10n.confirmDuplicate + '</button>' +
                                            '<button class="button cancel-preview">' + JustDuplicateL10n.cancel + '</button>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>';
                $('body').append(modalHtml);
            } else {
                alert(JustDuplicateL10n.error + ': ' + response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error(JustDuplicateL10n.ajaxError + ':', status, error);
            alert(JustDuplicateL10n.ajaxError + ': ' + error);
        });
    });

    // --- Modal Close Handling ---
    // Close button (X) in the modal.
    $('body').on('click', '.preview-modal-close', function(e) {
        e.preventDefault();
        $('.preview-modal-overlay').remove();
    });

    // Cancel button handling.
    $('body').on('click', '.cancel-preview', function(e) {
        e.preventDefault();
        $('.preview-modal-overlay').remove();
    });

    // Confirm duplicate button handling.
    $('body').on('click', '.confirm-duplicate', function(e) {
        e.preventDefault();
        var duplicateUrl = $(this).data('duplicate-url');
        window.location.href = duplicateUrl;
    });
});
