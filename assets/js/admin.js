jQuery(document).ready(function($) {
    // Tab functionality
    $('.firebase-woo-auth-tab').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update active tab
        $('.firebase-woo-auth-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding content
        $('.firebase-woo-auth-tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');

        // Hide save button in help tab
        if (tabId === 'help') {
            $('.submit').hide();
        } else {
            $('.submit').show();
        }
    });

    // Form submission with AJAX
    $('#firebase-woo-auth-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('.firebase-woo-auth-save-button');
        const $noticeContainer = $('.firebase-woo-auth-notice-container');
        
        // Prevent multiple submissions
        if ($submitButton.hasClass('loading')) {
            return;
        }
        
        // Show loading state
        $submitButton.addClass('loading');
        
        // Collect form data
        const formData = {};
        $form.find('input, select, textarea').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            if (name && name.includes('firebase_woo_auth_options')) {
                if ($input.attr('type') === 'checkbox') {
                    formData[name.replace('firebase_woo_auth_options[', '').replace(']', '')] = $input.is(':checked') ? '1' : '';
                } else {
                    formData[name.replace('firebase_woo_auth_options[', '').replace(']', '')] = $input.val();
                }
            }
        });

        // Log form data for debugging
        console.log('Submitting form data:', formData);
        
        // AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'firebase_woo_auth_save_settings',
                nonce: firebaseWooAuthAdmin.nonce,
                firebase_woo_auth_options: formData
            },
            success: function(response) {
                console.log('Server response:', response);
                
                // Remove any existing notices
                $noticeContainer.empty();
                
                if (response.success) {
                    // Show success notice
                    $noticeContainer.html(`
                        <div class="firebase-woo-auth-notice success">
                            ${response.data.message}
                        </div>
                    `);
                    
                    // Disable save button after successful save
                    $submitButton.prop('disabled', true);
                } else {
                    // Show error notice
                    $noticeContainer.html(`
                        <div class="firebase-woo-auth-notice error">
                            ${response.data.message}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                // Show error notice with more details
                $noticeContainer.html(`
                    <div class="firebase-woo-auth-notice error">
                        An error occurred while saving the settings. Please try again.
                    </div>
                `);
            },
            complete: function() {
                // Remove loading state
                $submitButton.removeClass('loading');
            }
        });
    });

    // Tooltip initialization
    $('.firebase-woo-auth-tooltip').each(function() {
        const tooltipContent = $(this).data('tooltip');
        $(this).append(`
            <span class="tooltip-icon">?</span>
            <span class="tooltip-content">${tooltipContent}</span>
        `);
    });

    // Copy to clipboard functionality
    $('.firebase-woo-auth-copy-button').on('click', function() {
        const textToCopy = $(this).data('copy');
        navigator.clipboard.writeText(textToCopy).then(function() {
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text('Copied!');
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        }.bind(this));
    });

    // Form validation and change detection
    $('.firebase-woo-auth-form-group input').on('change input', function() {
        const $form = $(this).closest('form');
        const $submitButton = $form.find('.firebase-woo-auth-save-button');
        
        // Enable save button when changes are made
        $submitButton.prop('disabled', false);
    });

    // Hide save button in help tab by default
    if ($('#help').hasClass('active')) {
        $('.submit').hide();
    }

    // Disable save button initially if no changes
    $('.firebase-woo-auth-save-button').prop('disabled', true);
}); 