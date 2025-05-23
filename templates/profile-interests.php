<?php
/**
 * profile-interests.php - Template for displaying user interests on profile
 */
?>
<div class="bae-interests-profile">
    <?php if (empty($categories)) : ?>
        <p><?php esc_html_e('No interest categories found.', 'bae-interests'); ?></p>
    <?php else : ?>
        <?php foreach ($categories as $category) : ?>
            <?php
            // Get user interests for this category
            $interests = $db->get_user_interests_by_category($user_id, $category->slug);
            
            // Skip empty categories
            if (empty($interests)) {
                continue;
            }
            ?>
            <div class="bae-interests-category">
                <h3><?php echo esc_html($category->name); ?></h3>
                
                <div class="bae-interests-list">
                    <?php foreach ($interests as $interest) : ?>
                        <div class="bae-interest-item">
                            <a href="<?php echo esc_url(home_url('/members/?interest=' . $interest->slug)); ?>" class="bae-interest-link">
                                <?php echo esc_html($interest->name); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (bp_is_my_profile()) : ?>
            <div class="bae-interests-actions">
                <a href="<?php echo esc_url(bp_displayed_user_domain() . 'interests/manage/'); ?>" class="button bae-manage-interests-button">
                    <?php esc_html_e('Manage My Interests', 'bae-interests'); ?>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>