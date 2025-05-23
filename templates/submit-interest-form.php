<?php
/**
 * submit-interest-form.php - Template for interest submission form
 */
?>
<div class="bae-interests-submit-form">
    <form id="bae-submit-interest-form" method="post">
        <div class="bae-form-field">
            <label for="interest-name"><?php esc_html_e('Interest Name', 'bae-interests'); ?> <span class="required">*</span></label>
            <input type="text" id="interest-name" name="interest_name" required>
        </div>
        
        <div class="bae-form-field">
            <label for="interest-category"><?php esc_html_e('Category', 'bae-interests'); ?> <span class="required">*</span></label>
            <select id="interest-category" name="category_id" required>
                <option value=""><?php esc_html_e('Select a category', 'bae-interests'); ?></option>
                <?php
                // Get database instance
                $db = BAEI_DB::get_instance();
                
                // Get categories
                $categories = $db->get_categories();
                
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->id) . '">' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="bae-form-field">
            <label for="interest-notes"><?php esc_html_e('Notes (optional)', 'bae-interests'); ?></label>
            <textarea id="interest-notes" name="notes" rows="3"></textarea>
            <p class="description"><?php esc_html_e('Add any additional information about why this interest should be added.', 'bae-interests'); ?></p>
        </div>
        
        <div class="bae-form-submit">
            <button type="submit" class="button"><?php echo esc_html($atts['button_text']); ?></button>
            <span class="bae-spinner" style="display: none;"></span>
        </div>
        
        <div class="bae-form-message" style="display: none;"></div>
    </form>
</div>

<script>
    (function($) {
        $('#bae-submit-interest-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $spinner = $form.find('.bae-spinner');
            var $message = $form.find('.bae-form-message');
            
            // Disable submit button and show spinner
            $submit.prop('disabled', true);
            $spinner.show();
            
            // Prepare form data
            var formData = {
                action: 'bae_submit_interest_request',
                nonce: baeInterests.nonce,
                interest_name: $form.find('#interest-name').val(),
                category_id: $form.find('#interest-category').val(),
                notes: $form.find('#interest-notes').val()
            };
            
            // Send AJAX request
            $.post(baeInterests.ajaxUrl, formData, function(response) {
                if (response.success) {
                    // Show success message
                    $message.removeClass('error').addClass('success').html('<p>' + <?php echo json_encode($atts['success_message']); ?> + '</p>').show();
                    
                    // Reset form
                    $form.trigger('reset');
                } else {
                    // Show error message
                    $message.removeClass('success').addClass('error').html('<p>' + response.data.message + '</p>').show();
                }
            }).fail(function() {
                // Show generic error message
                $message.removeClass('success').addClass('error').html('<p><?php esc_html_e('An error occurred. Please try again.', 'bae-interests'); ?></p>').show();
            }).always(function() {
                // Re-enable submit button and hide spinner
                $submit.prop('disabled', false);
                $spinner.hide();
            });
        });
    })(jQuery);
</script>