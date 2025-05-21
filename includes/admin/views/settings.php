<?php
/**
 * Admin views - settings.php - Template for interest settings in admin
 */
?>
<div class="wrap bb-interests-admin">
    <h1><?php esc_html_e('Interest Settings', 'bb-interests'); ?></h1>
    
    <div class="bb-admin-tabs">
        <a href="?page=bb-interests" class="nav-tab"><?php esc_html_e('Interests', 'bb-interests'); ?></a>
        <a href="?page=bb-interests-requests" class="nav-tab"><?php esc_html_e('Requests', 'bb-interests'); ?></a>
        <a href="?page=bb-interests-settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'bb-interests'); ?></a>
    </div>
    
    <div class="bb-admin-content">
        <form method="post" action="">
            <?php wp_nonce_field('bb_interests_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Search Features', 'bb-interests'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php esc_html_e('Search Features', 'bb-interests'); ?></span>
                            </legend>
                            <label for="enable-search">
                                <input name="enable_search" type="checkbox" id="enable-search" value="1" <?php checked($enable_search); ?>>
                                <?php esc_html_e('Enable interest search in member directory', 'bb-interests'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="per-page"><?php esc_html_e('Results Per Page', 'bb-interests'); ?></label>
                    </th>
                    <td>
                        <input name="per_page" type="number" id="per-page" value="<?php echo esc_attr($per_page); ?>" class="small-text" min="1" max="100">
                        <p class="description"><?php esc_html_e('Number of members to show per page in interest search results.', 'bb-interests'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Interest Approval', 'bb-interests'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php esc_html_e('Interest Approval', 'bb-interests'); ?></span>
                            </legend>
                            <label for="require-approval">
                                <input name="require_approval" type="checkbox" id="require-approval" value="1" <?php checked($require_approval); ?>>
                                <?php esc_html_e('Require admin approval for new interest submissions', 'bb-interests'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="bb_interests_settings_submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'bb-interests'); ?>">
            </p>
        </form>
    </div>
</div>