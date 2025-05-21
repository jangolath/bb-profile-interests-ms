<?php
/**
 * Admin views - requests.php - Template for managing interest requests in admin
 */
?>
<div class="wrap bb-interests-admin">
    <h1><?php esc_html_e('Interest Requests', 'bb-interests'); ?></h1>
    
    <div class="bb-admin-tabs">
        <a href="?page=bb-interests" class="nav-tab"><?php esc_html_e('Interests', 'bb-interests'); ?></a>
        <a href="?page=bb-interests-requests" class="nav-tab nav-tab-active"><?php esc_html_e('Requests', 'bb-interests'); ?></a>
        <a href="?page=bb-interests-settings" class="nav-tab"><?php esc_html_e('Settings', 'bb-interests'); ?></a>
    </div>
    
    <div class="bb-admin-content">
        <div class="bb-admin-requests-list">
            <h2><?php esc_html_e('Pending Requests', 'bb-interests'); ?></h2>
            
            <?php if (empty($requests)) : ?>
                <p><?php esc_html_e('No pending requests found.', 'bb-interests'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped bb-requests-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-name"><?php esc_html_e('Interest Name', 'bb-interests'); ?></th>
                            <th scope="col" class="column-category"><?php esc_html_e('Category', 'bb-interests'); ?></th>
                            <th scope="col" class="column-user"><?php esc_html_e('Requested By', 'bb-interests'); ?></th>
                            <th scope="col" class="column-date"><?php esc_html_e('Date', 'bb-interests'); ?></th>
                            <th scope="col" class="column-actions"><?php esc_html_e('Actions', 'bb-interests'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request) : ?>
                            <tr class="bb-request-row" id="request-<?php echo esc_attr($request->id); ?>">
                                <td class="column-name">
                                    <?php echo esc_html($request->name); ?>
                                </td>
                                <td class="column-category">
                                    <?php echo esc_html($request->category_name); ?>
                                </td>
                                <td class="column-user">
                                    <a href="<?php echo esc_url(bp_core_get_user_domain($request->user_id)); ?>" target="_blank">
                                        <?php echo esc_html($request->user_name); ?>
                                    </a>
                                </td>
                                <td class="column-date">
                                    <?php echo human_time_diff(strtotime($request->requested_at), current_time('timestamp')) . ' ' . __('ago', 'bb-interests'); ?>
                                </td>
                                <td class="column-actions">
                                    <a href="#" class="bb-approve-request" data-request-id="<?php echo esc_attr($request->id); ?>">
                                        <?php esc_html_e('Approve', 'bb-interests'); ?>
                                    </a> | 
                                    <a href="#" class="bb-reject-request" data-request-id="<?php echo esc_attr($request->id); ?>">
                                        <?php esc_html_e('Reject', 'bb-interests'); ?>
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

<div id="bb-request-modal" style="display: none;">
    <div class="bb-request-modal-content">
        <h3 id="bb-request-modal-title"></h3>
        
        <div class="bb-request-modal-body">
            <div class="bb-request-modal-field">
                <label for="bb-request-modal-notes"><?php esc_html_e('Notes (optional)', 'bb-interests'); ?></label>
                <textarea id="bb-request-modal-notes" rows="3"></textarea>
                <p class="description"><?php esc_html_e('These notes will be included in the notification email sent to the user.', 'bb-interests'); ?></p>
            </div>
        </div>
        
        <div class="bb-request-modal-footer">
            <button id="bb-request-modal-submit" class="button button-primary"></button>
            <button id="bb-request-modal-cancel" class="button"><?php esc_html_e('Cancel', 'bb-interests'); ?></button>
            <span class="bb-spinner" style="display: none;"></span>
        </div>
    </div>
</div>

<script>
    (function($) {
        // Approve request
        $('.bb-approve-request').on('click', function(e) {
            e.preventDefault();
            
            var requestId = $(this).data('request-id');
            var requestName = $(this).closest('tr').find('.column-name').text().trim();
            
            $('#bb-request-modal-title').text(
                '<?php echo esc_js(__('Approve Interest Request: %s', 'bb-interests')); ?>'.replace('%s', requestName)
            );
            $('#bb-request-modal-submit').text('<?php echo esc_js(__('Approve', 'bb-interests')); ?>');
            $('#bb-request-modal-submit').data('action', 'approve');
            $('#bb-request-modal-submit').data('request-id', requestId);
            $('#bb-request-modal-notes').val('');
            
            // Show modal
            $('#bb-request-modal').fadeIn();
        });
        
        // Reject request
        $('.bb-reject-request').on('click', function(e) {
            e.preventDefault();
            
            var requestId = $(this).data('request-id');
            var requestName = $(this).closest('tr').find('.column-name').text().trim();
            
            $('#bb-request-modal-title').text(
                '<?php echo esc_js(__('Reject Interest Request: %s', 'bb-interests')); ?>'.replace('%s', requestName)
            );
            $('#bb-request-modal-submit').text('<?php echo esc_js(__('Reject', 'bb-interests')); ?>');
            $('#bb-request-modal-submit').data('action', 'reject');
            $('#bb-request-modal-submit').data('request-id', requestId);
            $('#bb-request-modal-notes').val('');
            
            // Show modal
            $('#bb-request-modal').fadeIn();
        });
        
        // Cancel modal
        $('#bb-request-modal-cancel').on('click', function() {
            $('#bb-request-modal').fadeOut();
        });
        
        // Submit modal
        $('#bb-request-modal-submit').on('click', function() {
            var $this = $(this);
            var action = $this.data('action');
            var requestId = $this.data('request-id');
            var notes = $('#bb-request-modal-notes').val();
            var $spinner = $('.bb-spinner');
            
            // Disable buttons and show spinner
            $this.prop('disabled', true);
            $('#bb-request-modal-cancel').prop('disabled', true);
            $spinner.show();
            
            if (action === 'approve') {
                // Approve request
                $.post(bbInterestsAdmin.ajaxUrl, {
                    action: 'bb_admin_approve_interest',
                    nonce: bbInterestsAdmin.nonce,
                    request_id: requestId,
                    notes: notes
                }, function(response) {
                    if (response.success) {
                        // Remove request row
                        $('#request-' + requestId).fadeOut(function() {
                            $(this).remove();
                        });
                        
                        // Hide modal
                        $('#bb-request-modal').fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                }).always(function() {
                    // Re-enable buttons and hide spinner
                    $this.prop('disabled', false);
                    $('#bb-request-modal-cancel').prop('disabled', false);
                    $spinner.hide();
                });
            } else if (action === 'reject') {
                // Reject request
                $.post(bbInterestsAdmin.ajaxUrl, {
                    action: 'bb_admin_reject_interest',
                    nonce: bbInterestsAdmin.nonce,
                    request_id: requestId,
                    notes: notes
                }, function(response) {
                    if (response.success) {
                        // Remove request row
                        $('#request-' + requestId).fadeOut(function() {
                            $(this).remove();
                        });
                        
                        // Hide modal
                        $('#bb-request-modal').fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                }).always(function() {
                    // Re-enable buttons and hide spinner
                    $this.prop('disabled', false);
                    $('#bb-request-modal-cancel').prop('disabled', false);
                    $spinner.hide();
                });
            }
        });
    })(jQuery);
</script>