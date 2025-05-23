<?php
/**
 * Admin views - interests.php - Template for managing interests in admin
 */
?>
<div class="wrap bae-interests-admin">
    <h1><?php esc_html_e('Profile Interests', 'bae-interests'); ?></h1>
    
    <div class="bae-admin-tabs">
        <a href="?page=bae-profile-interests&tab=interests" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'interests') ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Interests', 'bae-interests'); ?></a>
        <a href="?page=bae-profile-interests&tab=requests" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'requests') ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Requests', 'bae-interests'); ?></a>
        <a href="?page=bae-profile-interests&tab=settings" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'settings') ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'bae-interests'); ?></a>
    </div>
    
    <div class="bae-admin-content">
        <div class="bae-admin-add-interest">
            <h2><?php esc_html_e('Add New Interest', 'bae-interests'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('bae_add_interest_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="interest-name"><?php esc_html_e('Interest Name', 'bae-interests'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="interest-name" name="interest_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="interest-category"><?php esc_html_e('Category', 'bae-interests'); ?></label>
                        </th>
                        <td>
                            <select id="interest-category" name="category_id" required>
                                <option value=""><?php esc_html_e('Select a category', 'bae-interests'); ?></option>
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
                    <input type="submit" name="bae_add_interest_submit" class="button button-primary" value="<?php esc_attr_e('Add Interest', 'bae-interests'); ?>">
                </p>
            </form>
        </div>
        
        <div class="bae-admin-interests-list">
            <h2><?php esc_html_e('Existing Interests', 'bae-interests'); ?></h2>
            
            <?php if (empty($interests)) : ?>
                <p><?php esc_html_e('No interests found.', 'bae-interests'); ?></p>
            <?php else : ?>
                <div class="bae-admin-filters">
                    <select id="bae-filter-category">
                        <option value=""><?php esc_html_e('All Categories', 'bae-interests'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" id="bae-search-interests" placeholder="<?php esc_attr_e('Search interests...', 'bae-interests'); ?>">
                </div>
                
                <table class="wp-list-table widefat fixed striped bae-interests-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-name"><?php esc_html_e('Name', 'bae-interests'); ?></th>
                            <th scope="col" class="column-category"><?php esc_html_e('Category', 'bae-interests'); ?></th>
                            <th scope="col" class="column-count"><?php esc_html_e('Users', 'bae-interests'); ?></th>
                            <th scope="col" class="column-actions"><?php esc_html_e('Actions', 'bae-interests'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interests as $interest) : ?>
                            <?php
                            // Count users with this interest
                            $count = $db->count_users_by_interest($interest->id);
                            ?>
                            <tr class="bae-interest-row" data-category="<?php echo esc_attr($interest->category_slug); ?>">
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
                                    <a href="#" class="bae-delete-interest" data-interest-id="<?php echo esc_attr($interest->id); ?>" data-interest-name="<?php echo esc_attr($interest->name); ?>">
                                        <?php esc_html_e('Delete', 'bae-interests'); ?>
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
        $('#bae-filter-category').on('change', function() {
            var category = $(this).val();
            
            if (category === '') {
                $('.bae-interest-row').show();
            } else {
                $('.bae-interest-row').hide();
                $('.bae-interest-row[data-category="' + category + '"]').show();
            }
        });
        
        // Search interests
        $('#bae-search-interests').on('keyup', function() {
            var term = $(this).val().toLowerCase();
            
            if (term === '') {
                $('.bae-interest-row').show();
            } else {
                $('.bae-interest-row').each(function() {
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
        $('.bae-delete-interest').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var interestId = $this.data('interest-id');
            var interestName = $this.data('interest-name');
            
            if (confirm(baeInterestsAdmin.confirmDelete.replace('%s', interestName))) {
                $.post(baeInterestsAdmin.ajaxUrl, {
                    action: 'bae_admin_delete_interest',
                    nonce: baeInterestsAdmin.nonce,
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