<?php
/**
 * Admin views - interests.php - Template for managing interests in admin
 */
?>
<div class="wrap bb-interests-admin">
    <h1><?php esc_html_e('Manage Interests', 'bb-interests'); ?></h1>
    
    <div class="bb-admin-tabs">
        <a href="?page=bb-interests" class="nav-tab nav-tab-active"><?php esc_html_e('Interests', 'bb-interests'); ?></a>
        <a href="?page=bb-interests-requests" class="nav-tab"><?php esc_html_e('Requests', 'bb-interests'); ?></a>
        <a href="?page=bb-interests-settings" class="nav-tab"><?php esc_html_e('Settings', 'bb-interests'); ?></a>
    </div>
    
    <div class="bb-admin-content">
        <div class="bb-admin-add-interest">
            <h2><?php esc_html_e('Add New Interest', 'bb-interests'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('bb_add_interest_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="interest-name"><?php esc_html_e('Interest Name', 'bb-interests'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="interest-name" name="interest_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="interest-category"><?php esc_html_e('Category', 'bb-interests'); ?></label>
                        </th>
                        <td>
                            <select id="interest-category" name="category_id" required>
                                <option value=""><?php esc_html_e('Select a category', 'bb-interests'); ?></option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category->id); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="bb_add_interest_submit" class="button button-primary" value="<?php esc_attr_e('Add Interest', 'bb-interests'); ?>">
                </p>
            </form>
        </div>
        
        <div class="bb-admin-interests-list">
            <h2><?php esc_html_e('Existing Interests', 'bb-interests'); ?></h2>
            
            <?php if (empty($interests)) : ?>
                <p><?php esc_html_e('No interests found.', 'bb-interests'); ?></p>
            <?php else : ?>
                <div class="bb-admin-filters">
                    <select id="bb-filter-category">
                        <option value=""><?php esc_html_e('All Categories', 'bb-interests'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" id="bb-search-interests" placeholder="<?php esc_attr_e('Search interests...', 'bb-interests'); ?>">
                </div>
                
                <table class="wp-list-table widefat fixed striped bb-interests-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-name"><?php esc_html_e('Name', 'bb-interests'); ?></th>
                            <th scope="col" class="column-category"><?php esc_html_e('Category', 'bb-interests'); ?></th>
                            <th scope="col" class="column-count"><?php esc_html_e('Users', 'bb-interests'); ?></th>
                            <th scope="col" class="column-actions"><?php esc_html_e('Actions', 'bb-interests'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interests as $interest) : ?>
                            <?php
                            // Count users with this interest
                            $count = $db->count_users_by_interest($interest->id);
                            ?>
                            <tr class="bb-interest-row" data-category="<?php echo esc_attr($interest->category_slug); ?>">
                                <td class="column-name">
                                    <?php echo esc_html($interest->name); ?>
                                </td>
                                <td class="column-category">
                                    <?php echo esc_html($interest->category_name); ?>
                                </td>
                                <td class="column-count">
                                    <a href="<?php echo esc_url(home_url('/members/?interest=' . $interest->slug)); ?>" target="_blank">
                                        <?php echo esc_html($count); ?>
                                    </a>
                                </td>
                                <td class="column-actions">
                                    <a href="#" class="bb-delete-interest" data-interest-id="<?php echo esc_attr($interest->id); ?>" data-interest-name="<?php echo esc_attr($interest->name); ?>">
                                        <?php esc_html_e('Delete', 'bb-interests'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    (function($) {
        // Filter by category
        $('#bb-filter-category').on('change', function() {
            var category = $(this).val();
            
            if (category === '') {
                $('.bb-interest-row').show();
            } else {
                $('.bb-interest-row').hide();
                $('.bb-interest-row[data-category="' + category + '"]').show();
            }
        });
        
        // Search interests
        $('#bb-search-interests').on('keyup', function() {
            var term = $(this).val().toLowerCase();
            
            if (term === '') {
                $('.bb-interest-row').show();
            } else {
                $('.bb-interest-row').each(function() {
                    var name = $(this).find('.column-name').text().toLowerCase();
                    
                    if (name.indexOf(term) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        
        // Delete interest
        $('.bb-delete-interest').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var interestId = $this.data('interest-id');
            var interestName = $this.data('interest-name');
            
            if (confirm(bbInterestsAdmin.confirmDelete.replace('%s', interestName))) {
                $.post(bbInterestsAdmin.ajaxUrl, {
                    action: 'bb_admin_delete_interest',
                    nonce: bbInterestsAdmin.nonce,
                    interest_id: interestId
                }, function(response) {
                    if (response.success) {
                        $this.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                    }
                });
            }
        });
    })(jQuery);
</script>