<?php
/**
 * submit-interest-form.php - Template for interest submission form
 */
?>
<div class="bb-interests-submit-form">
    <form id="bb-submit-interest-form" method="post">
        <div class="bb-form-field">
            <label for="interest-name"><?php esc_html_e('Interest Name', 'bb-interests'); ?> <span class="required">*</span></label>
            <input type="text" id="interest-name" name="interest_name" required>
        </div>
        
        <div class="bb-form-field">
            <label for="interest-category"><?php esc_html_e('Category', 'bb-interests'); ?> <span class="required">*</span></label>
            <select id="interest-category" name="category_id" required>
                <option value=""><?php esc_html_e('Select a category', 'bb-interests'); ?></option>
                <?php
                // Get database instance
                $db = BB_Interests_DB::get_instance();
                
                // Get categories
                $categories = $db->get_categories();
                
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->id) . '">' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="bb-form-field">
            <label for="interest-notes"><?php esc_html_e('Notes (optional)', 'bb-interests'); ?></label>
            <textarea id="interest-notes" name="notes" rows="3"></textarea>
            <p class="description"><?php esc_html_e('Add any additional information about why this interest should be added.', 'bb-interests'); ?></p>
        </div>
        
        <div class="bb-form-submit">
            <button type="submit" class="button"><?php echo esc_html($atts['button_text']); ?></button>
            <span class="bb-spinner" style="display: none;"></span>
        </div>
        
        <div class="bb-form-message" style="display: none;"></div>
    </form>
</div>

<script>
    (function($) {
        $('#bb-submit-interest-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $spinner = $form.find('.bb-spinner');
            var $message = $form.find('.bb-form-message');
            
            // Disable submit button and show spinner
            $submit.prop('disabled', true);
            $spinner.show();
            
            // Prepare form data
            var formData = {
                action: 'bb_submit_interest_request',
                nonce: bbInterests.nonce,
                interest_name: $form.find('#interest-name').val(),
                category_id: $form.find('#interest-category').val(),
                notes: $form.find('#interest-notes').val()
            };
            
            // Send AJAX request
            $.post(bbInterests.ajaxUrl, formData, function(response) {
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
                $message.removeClass('success').addClass('error').html('<p><?php esc_html_e('An error occurred. Please try again.', 'bb-interests'); ?></p>').show();
            }).always(function() {
                // Re-enable submit button and hide spinner
                $submit.prop('disabled', false);
                $spinner.hide();
            });
        });
    })(jQuery);
</script>