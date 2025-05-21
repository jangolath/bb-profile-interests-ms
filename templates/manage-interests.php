<?php
/**
 * manage-interests.php - Template for managing user interests
 */
?>
<div class="bb-interests-manage">
    <?php if (empty($categories)) : ?>
        <p><?php esc_html_e('No interest categories found.', 'bb-interests'); ?></p>
    <?php else : ?>
        <p class="bb-interests-manage-info">
            <?php esc_html_e('Select interests to add them to your profile. Uncheck "Show in search" if you want to keep an interest private.', 'bb-interests'); ?>
        </p>
        
        <div class="bb-interests-categories-tabs">
            <ul class="bb-interests-tabs">
                <?php foreach ($categories as $index => $category) : ?>
                    <li>
                        <a href="#bb-category-<?php echo esc_attr($category->id); ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="bb-interests-tabs-content">
                <?php foreach ($categories as $index => $category) : ?>
                    <div id="bb-category-<?php echo esc_attr($category->id); ?>" class="bb-interests-tab-pane <?php echo $index === 0 ? 'active' : ''; ?>">
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
                            echo '<p>' . esc_html__('No interests found in this category.', 'bb-interests') . '</p>';
                        } else {
                            echo '<div class="bb-interests-grid">';
                            
                            foreach ($interests as $interest) {
                                $has_interest = isset($user_interest_ids[$interest->id]);
                                $show_in_search = $has_interest ? $user_interest_ids[$interest->id] : 1;
                                
                                echo '<div class="bb-interest-manage-item ' . ($has_interest ? 'selected' : '') . '">';
                                echo '<div class="bb-interest-name">';
                                echo '<label>';
                                echo '<input type="checkbox" class="bb-interest-checkbox" data-interest-id="' . esc_attr($interest->id) . '" ' . ($has_interest ? 'checked' : '') . '>';
                                echo esc_html($interest->name);
                                echo '</label>';
                                echo '</div>';
                                
                                echo '<div class="bb-interest-search-option" ' . ($has_interest ? '' : 'style="display: none;"') . '>';
                                echo '<label>';
                                echo '<input type="checkbox" class="bb-interest-search-checkbox" data-interest-id="' . esc_attr($interest->id) . '" ' . ($show_in_search ? 'checked' : '') . '>';
                                echo esc_html__('Show in search', 'bb-interests');
                                echo '</label>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            
                            // Add "Load more" button if there might be more interests
                            if (count($interests) === 50) {
                                echo '<div class="bb-load-more-interests">';
                                echo '<button class="button bb-load-more-button" data-category-id="' . esc_attr($category->id) . '" data-offset="50">';
                                echo esc_html__('Load More', 'bb-interests');
                                echo '</button>';
                                echo '<span class="bb-spinner" style="display: none;"></span>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bb-interests-request-link">
            <p>
                <?php esc_html_e('Don\'t see what you\'re looking for?', 'bb-interests'); ?>
                <a href="#" class="bb-request-interest-link">
                    <?php esc_html_e('Request a new interest', 'bb-interests'); ?>
                </a>
            </p>
        </div>
        
        <div class="bb-request-interest-form" style="display: none;">
            <h3><?php esc_html_e('Request a New Interest', 'bb-interests'); ?></h3>
            
            <form id="bb-request-interest-form" method="post">
                <div class="bb-form-field">
                    <label for="request-interest-name"><?php esc_html_e('Interest Name', 'bb-interests'); ?> <span class="required">*</span></label>
                    <input type="text" id="request-interest-name" name="interest_name" required>
                </div>
                
                <div class="bb-form-field">
                    <label for="request-interest-category"><?php esc_html_e('Category', 'bb-interests'); ?> <span class="required">*</span></label>
                    <select id="request-interest-category" name="category_id" required>
                        <option value=""><?php esc_html_e('Select a category', 'bb-interests'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="bb-form-field">
                    <label for="request-interest-notes"><?php esc_html_e('Notes (optional)', 'bb-interests'); ?></label>
                    <textarea id="request-interest-notes" name="notes" rows="3"></textarea>
                    <p class="description"><?php esc_html_e('Add any additional information about why this interest should be added.', 'bb-interests'); ?></p>
                </div>
                
                <div class="bb-form-submit">
                    <button type="submit" class="button"><?php esc_html_e('Submit Request', 'bb-interests'); ?></button>
                    <button type="button" class="button button-secondary bb-cancel-request"><?php esc_html_e('Cancel', 'bb-interests'); ?></button>
                    <span class="bb-spinner" style="display: none;"></span>
                </div>
                
                <div class="bb-form-message" style="display: none;"></div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    (function($) {
        // Tab switching
        $('.bb-interests-tabs a').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.attr('href');
            
            // Activate tab
            $('.bb-interests-tabs a').removeClass('active');
            $this.addClass('active');
            
            // Show tab content
            $('.bb-interests-tab-pane').removeClass('active');
            $(target).addClass('active');
        });
        
        // Toggle interest
        $('.bb-interest-checkbox').on('change', function() {
            var $this = $(this);
            var interestId = $this.data('interest-id');
            var $item = $this.closest('.bb-interest-manage-item');
            var $searchOption = $item.find('.bb-interest-search-option');
            
            if ($this.is(':checked')) {
                // Add interest
                $item.addClass('selected');
                $searchOption.show();
                
                $.post(bbInterests.ajaxUrl, {
                    action: 'bb_add_interest_to_profile',
                    nonce: bbInterests.nonce,
                    interest_id: interestId,
                    show_in_search: $item.find('.bb-interest-search-checkbox').is(':checked') ? 1 : 0
                });
            } else {
                // Remove interest
                $item.removeClass('selected');
                $searchOption.hide();
                
                $.post(bbInterests.ajaxUrl, {
                    action: 'bb_remove_interest_from_profile',
                    nonce: bbInterests.nonce,
                    interest_id: interestId
                });
            }
        });
        
        // Toggle search visibility
        $('.bb-interest-search-checkbox').on('change', function() {
            var $this = $(this);
            var interestId = $this.data('interest-id');
            
            $.post(bbInterests.ajaxUrl, {
                action: 'bb_add_interest_to_profile',
                nonce: bbInterests.nonce,
                interest_id: interestId,
                show_in_search: $this.is(':checked') ? 1 : 0
            });
        });
        
        // Load more interests
        $('.bb-load-more-button').on('click', function() {
            var $this = $(this);
            var categoryId = $this.data('category-id');
            var offset = $this.data('offset');
            var $container = $this.closest('.bb-interests-tab-pane').find('.bb-interests-grid');
            var $spinner = $this.siblings('.bb-spinner');
            
            // Disable button and show spinner
            $this.prop('disabled', true);
            $spinner.show();
            
            $.post(bbInterests.ajaxUrl, {
                action: 'bb_load_more_interests',
                nonce: bbInterests.nonce,
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
                    $container.find('.bb-interest-checkbox').off('change').on('change', function() {
                        var $checkbox = $(this);
                        var interestId = $checkbox.data('interest-id');
                        var $item = $checkbox.closest('.bb-interest-manage-item');
                        var $searchOption = $item.find('.bb-interest-search-option');
                        
                        if ($checkbox.is(':checked')) {
                            $item.addClass('selected');
                            $searchOption.show();
                            
                            $.post(bbInterests.ajaxUrl, {
                                action: 'bb_add_interest_to_profile',
                                nonce: bbInterests.nonce,
                                interest_id: interestId,
                                show_in_search: $item.find('.bb-interest-search-checkbox').is(':checked') ? 1 : 0
                            });
                        } else {
                            $item.removeClass('selected');
                            $searchOption.hide();
                            
                            $.post(bbInterests.ajaxUrl, {
                                action: 'bb_remove_interest_from_profile',
                                nonce: bbInterests.nonce,
                                interest_id: interestId
                            });
                        }
                    });
                    
                    $container.find('.bb-interest-search-checkbox').off('change').on('change', function() {
                        var $checkbox = $(this);
                        var interestId = $checkbox.data('interest-id');
                        
                        $.post(bbInterests.ajaxUrl, {
                            action: 'bb_add_interest_to_profile',
                            nonce: bbInterests.nonce,
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
        $('.bb-request-interest-link').on('click', function(e) {
            e.preventDefault();
            $('.bb-request-interest-form').slideToggle();
        });
        
        // Cancel request
        $('.bb-cancel-request').on('click', function() {
            $('.bb-request-interest-form').slideUp();
            $('#bb-request-interest-form').trigger('reset');
            $('#bb-request-interest-form .bb-form-message').hide();
        });
        
        // Submit request form
        $('#bb-request-interest-form').on('submit', function(e) {
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
                interest_name: $form.find('#request-interest-name').val(),
                category_id: $form.find('#request-interest-category').val(),
                notes: $form.find('#request-interest-notes').val()
            };
            
            // Send AJAX request
            $.post(bbInterests.ajaxUrl, formData, function(response) {
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
                $message.removeClass('success').addClass('error').html('<p><?php esc_html_e('An error occurred. Please try again.', 'bb-interests'); ?></p>').show();
            }).always(function() {
                // Re-enable submit button and hide spinner
                $submit.prop('disabled', false);
                $spinner.hide();
            });
        });
    })(jQuery);
</script>