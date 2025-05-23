<?php
/**
 * manage-interests.php - Template for managing user interests
 */
?>
<div class="bae-interests-manage">
    <?php if (empty($categories)) : ?>
        <p><?php esc_html_e('No interest categories found.', 'bae-interests'); ?></p>
    <?php else : ?>
        <p class="bae-interests-manage-info">
            <?php esc_html_e('Select interests to add them to your profile. Uncheck "Show in search" if you want to keep an interest private.', 'bae-interests'); ?>
        </p>
        
        <div class="bae-interests-categories-tabs">
            <ul class="bae-interests-tabs">
                <?php foreach ($categories as $index => $category) : ?>
                    <li>
                        <a href="#bae-category-<?php echo esc_attr($category->id); ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="bae-interests-tabs-content">
                <?php foreach ($categories as $index => $category) : ?>
                    <div id="bae-category-<?php echo esc_attr($category->id); ?>" class="bae-interests-tab-pane <?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php
                        // Get interests for this category
                        global $wpdb;
                        $interests = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$db->table_interests} 
                             WHERE category_id = %d AND approved = 1 
                             ORDER BY name ASC 
                             LIMIT 50",
                            $category->id
                        ));
                        
                        if (empty($interests)) {
                            echo '<p>' . esc_html__('No interests found in this category.', 'bae-interests') . '</p>';
                        } else {
                            echo '<div class="bae-interests-grid">';
                            
                            foreach ($interests as $interest) {
                                $has_interest = isset($user_interest_ids[$interest->id]);
                                $show_in_search = $has_interest ? $user_interest_ids[$interest->id] : 1;
                                
                                echo '<div class="bae-interest-manage-item ' . ($has_interest ? 'selected' : '') . '">';
                                echo '<div class="bae-interest-name">';
                                echo '<label>';
                                echo '<input type="checkbox" class="bae-interest-checkbox" data-interest-id="' . esc_attr($interest->id) . '" ' . ($has_interest ? 'checked' : '') . '>';
                                echo esc_html($interest->name);
                                echo '</label>';
                                echo '</div>';
                                
                                echo '<div class="bae-interest-search-option" ' . ($has_interest ? '' : 'style="display: none;"') . '>';
                                echo '<label>';
                                echo '<input type="checkbox" class="bae-interest-search-checkbox" data-interest-id="' . esc_attr($interest->id) . '" ' . ($show_in_search ? 'checked' : '') . '>';
                                echo esc_html__('Show in search', 'bae-interests');
                                echo '</label>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            
                            // Add "Load more" button if there might be more interests
                            if (count($interests) === 50) {
                                echo '<div class="bae-load-more-interests">';
                                echo '<button class="button bae-load-more-button" data-category-id="' . esc_attr($category->id) . '" data-offset="50">';
                                echo esc_html__('Load More', 'bae-interests');
                                echo '</button>';
                                echo '<span class="bae-spinner" style="display: none;"></span>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bae-interests-request-link">
            <p>
                <?php esc_html_e('Don\'t see what you\'re looking for?', 'bae-interests'); ?>
                <a href="#" class="bae-request-interest-link">
                    <?php esc_html_e('Request a new interest', 'bae-interests'); ?>
                </a>
            </p>
        </div>
        
        <div class="bae-request-interest-form" style="display: none;">
            <h3><?php esc_html_e('Request a New Interest', 'bae-interests'); ?></h3>
            
            <form id="bae-request-interest-form" method="post">
                <div class="bae-form-field">
                    <label for="request-interest-name"><?php esc_html_e('Interest Name', 'bae-interests'); ?> <span class="required">*</span></label>
                    <input type="text" id="request-interest-name" name="interest_name" required>
                </div>
                
                <div class="bae-form-field">
                    <label for="request-interest-category"><?php esc_html_e('Category', 'bae-interests'); ?> <span class="required">*</span></label>
                    <select id="request-interest-category" name="category_id" required>
                        <option value=""><?php esc_html_e('Select a category', 'bae-interests'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="bae-form-field">
                    <label for="request-interest-notes"><?php esc_html_e('Notes (optional)', 'bae-interests'); ?></label>
                    <textarea id="request-interest-notes" name="notes" rows="3"></textarea>
                    <p class="description"><?php esc_html_e('Add any additional information about why this interest should be added.', 'bae-interests'); ?></p>
                </div>
                
                <div class="bae-form-submit">
                    <button type="submit" class="button"><?php esc_html_e('Submit Request', 'bae-interests'); ?></button>
                    <button type="button" class="button button-secondary bae-cancel-request"><?php esc_html_e('Cancel', 'bae-interests'); ?></button>
                    <span class="bae-spinner" style="display: none;"></span>
                </div>
                
                <div class="bae-form-message" style="display: none;"></div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    (function($) {
        // Tab switching
        $('.bae-interests-tabs a').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.attr('href');
            
            // Activate tab
            $('.bae-interests-tabs a').removeClass('active');
            $this.addClass('active');
            
            // Show tab content
            $('.bae-interests-tab-pane').removeClass('active');
            $(target).addClass('active');
        });
        
        // Toggle interest
        $('.bae-interest-checkbox').on('change', function() {
            var $this = $(this);
            var interestId = $this.data('interest-id');
            var $item = $this.closest('.bae-interest-manage-item');
            var $searchOption = $item.find('.bae-interest-search-option');
            
            if ($this.is(':checked')) {
                // Add interest
                $item.addClass('selected');
                $searchOption.show();
                
                $.post(baeInterests.ajaxUrl, {
                    action: 'bae_add_interest_to_profile',
                    nonce: baeInterests.nonce,
                    interest_id: interestId,
                    show_in_search: $item.find('.bae-interest-search-checkbox').is(':checked') ? 1 : 0
                });
            } else {
                // Remove interest
                $item.removeClass('selected');
                $searchOption.hide();
                
                $.post(baeInterests.ajaxUrl, {
                    action: 'bae_remove_interest_from_profile',
                    nonce: baeInterests.nonce,
                    interest_id: interestId
                });
            }
        });
        
        // Toggle search visibility
        $('.bae-interest-search-checkbox').on('change', function() {
            var $this = $(this);
            var interestId = $this.data('interest-id');
            
            $.post(baeInterests.ajaxUrl, {
                action: 'bae_add_interest_to_profile',
                nonce: baeInterests.nonce,
                interest_id: interestId,
                show_in_search: $this.is(':checked') ? 1 : 0
            });
        });
        
        // Load more interests
        $('.bae-load-more-button').on('click', function() {
            var $this = $(this);
            var categoryId = $this.data('category-id');
            var offset = $this.data('offset');
            var $container = $this.closest('.bae-interests-tab-pane').find('.bae-interests-grid');
            var $spinner = $this.siblings('.bae-spinner');
            
            // Disable button and show spinner
            $this.prop('disabled', true);
            $spinner.show();
            
            $.post(baeInterests.ajaxUrl, {
                action: 'bae_load_more_interests',
                nonce: baeInterests.nonce,
                category_id: categoryId,
                offset: offset,
                user_id: <?php echo get_current_user_id(); ?>
            }, function(response) {
                if (response.success) {
                    // Append new interests
                    $container.append(response.data.html);
                    
                    // Update offset
                    $this.data('offset', response.data.offset);
                    
                    // Hide button if no more interests
                    if (!response.data.more) {
                        $this.parent().hide();
                    }
                    
                    // Re-initialize event handlers for new interests
                    $container.find('.bae-interest-checkbox').off('change').on('change', function() {
                        var $checkbox = $(this);
                        var interestId = $checkbox.data('interest-id');
                        var $item = $checkbox.closest('.bae-interest-manage-item');
                        var $searchOption = $item.find('.bae-interest-search-option');
                        
                        if ($checkbox.is(':checked')) {
                            $item.addClass('selected');
                            $searchOption.show();
                            
                            $.post(baeInterests.ajaxUrl, {
                                action: 'bae_add_interest_to_profile',
                                nonce: baeInterests.nonce,
                                interest_id: interestId,
                                show_in_search: $item.find('.bae-interest-search-checkbox').is(':checked') ? 1 : 0
                            });
                        } else {
                            $item.removeClass('selected');
                            $searchOption.hide();
                            
                            $.post(baeInterests.ajaxUrl, {
                                action: 'bae_remove_interest_from_profile',
                                nonce: baeInterests.nonce,
                                interest_id: interestId
                            });
                        }
                    });
                    
                    $container.find('.bae-interest-search-checkbox').off('change').on('change', function() {
                        var $checkbox = $(this);
                        var interestId = $checkbox.data('interest-id');
                        
                        $.post(baeInterests.ajaxUrl, {
                            action: 'bae_add_interest_to_profile',
                            nonce: baeInterests.nonce,
                            interest_id: interestId,
                            show_in_search: $checkbox.is(':checked') ? 1 : 0
                        });
                    });
                }
            }).always(function() {
                // Re-enable button and hide spinner
                $this.prop('disabled', false);
                $spinner.hide();
            });
        });
        
        // Toggle request form
        $('.bae-request-interest-link').on('click', function(e) {
            e.preventDefault();
            $('.bae-request-interest-form').slideToggle();
        });
        
        // Cancel request
        $('.bae-cancel-request').on('click', function() {
            $('.bae-request-interest-form').slideUp();
            $('#bae-request-interest-form').trigger('reset');
            $('#bae-request-interest-form .bae-form-message').hide();
        });
        
        // Submit request form
        $('#bae-request-interest-form').on('submit', function(e) {
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
                interest_name: $form.find('#request-interest-name').val(),
                category_id: $form.find('#request-interest-category').val(),
                notes: $form.find('#request-interest-notes').val()
            };
            
            // Send AJAX request
            $.post(baeInterests.ajaxUrl, formData, function(response) {
                if (response.success) {
                    // Show success message
                    $message.removeClass('error').addClass('success').html('<p>' + response.data.message + '</p>').show();
                    
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