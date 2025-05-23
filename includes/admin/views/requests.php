<?php
/**
 * Admin views - requests.php - Template for managing interest requests in admin
 */
?>
<div class="wrap bae-interests-admin">
    <h1><?php esc_html_e('Interest Requests', 'bae-interests'); ?></h1>
    
    <div class="bae-admin-tabs">
        <a href="?page=bae-profile-interests&tab=interests" class="nav-tab"><?php esc_html_e('Interests', 'bae-interests'); ?></a>
        <a href="?page=bae-profile-interests&tab=requests" class="nav-tab nav-tab-active"><?php esc_html_e('Requests', 'bae-interests'); ?></a>
        <a href="?page=bae-profile-interests&tab=settings" class="nav-tab"><?php esc_html_e('Settings', 'bae-interests'); ?></a>
    </div>
    
    <div class="bae-admin-content">
        <div class="bae-admin-requests-list">
            <h2><?php esc_html_e('Pending Requests', 'bae-interests'); ?></h2>
            
            <?php if (empty($requests)) : ?>
                <p><?php esc_html_e('No pending requests found.', 'bae-interests'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped bae-requests-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-name"><?php esc_html_e('Interest Name', 'bae-interests'); ?></th>
                            <th scope="col" class="column-category"><?php esc_html_e('Category', 'bae-interests'); ?></th>
                            <th scope="col" class="column-user"><?php esc_html_e('Requested By', 'bae-interests'); ?></th>
                            <th scope="col" class="column-date"><?php esc_html_e('Date', 'bae-interests'); ?></th>
                            <th scope="col" class="column-actions"><?php esc_html_e('Actions', 'bae-interests'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request) : ?>
                            <tr class="bae-request-row" id="request-<?php echo esc_attr($request->id); ?>">
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
                                    <?php echo human_time_diff(strtotime($request->requested_at), current_time('timestamp')) . ' ' . __('ago', 'bae-interests'); ?>
                                </td>
                                <td class="column-actions">
                                    <a href="#" class="bae-approve-request" data-request-id="<?php echo esc_attr($request->id); ?>">
                                        <?php esc_html_e('Approve', 'bae-interests'); ?>
                                    </a> | 
                                    <a href="#" class="bae-reject-request" data-request-id="<?php echo esc_attr($request->id); ?>">
                                        <?php esc_html_e('Reject', 'bae-interests'); ?>
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

<div id="bae-request-modal" style="display: none;">
    <div class="bae-request-modal-content">
        <h3 id="bae-request-modal-title"></h3>
        
        <div class="bae-request-modal-body">
            <div class="bae-request-modal-field">
                <label for="bae-request-modal-notes"><?php esc_html_e('Notes (optional)', 'bae-interests'); ?></label>
                <textarea id="bae-request-modal-notes" rows="3"></textarea>
                <p class="description"><?php esc_html_e('These notes will be included in the notification email sent to the user.', 'bae-interests'); ?></p>
            </div>
        </div>
        
        <div class="bae-request-modal-footer">
            <button id="bae-request-modal-submit" class="button button-primary"></button>
            <button id="bae-request-modal-cancel" class="button"><?php esc_html_e('Cancel', 'bae-interests'); ?></button>
            <span class="bae-spinner" style="display: none;"></span>
        </div>
    </div>
</div>

<script>
    (function($) {
        // Approve request
        $('.bae-approve-request').on('click', function(e) {
            e.preventDefault();
            
            var requestId = $(this).data('request-id');
            var requestName = $(this).closest('tr').find('.column-name').text().trim();
            
            $('#bae-request-modal-title').text(
                '<?php echo esc_js(__('Approve Interest Request: %s', 'bae-interests')); ?>'.replace('%s', requestName)
            );
            $('#bae-request-modal-submit').text('<?php echo esc_js(__('Approve', 'bae-interests')); ?>');
            $('#bae-request-modal-submit').data('action', 'approve');
            $('#bae-request-modal-submit').data('request-id', requestId);
            $('#bae-request-modal-notes').val('');
            
            // Show modal
            $('#bae-request-modal').fadeIn();
        });
        
        // Reject request
        $('.bae-reject-request').on('click', function(e) {
            e.preventDefault();
            
            var requestId = $(this).data('request-id');
            var requestName = $(this).closest('tr').find('.column-name').text().trim();
            
            $('#bae-request-modal-title').text(
                '<?php echo esc_js(__('Reject Interest Request: %s', 'bae-interests')); ?>'.replace('%s', requestName)
            );
            $('#bae-request-modal-submit').text('<?php echo esc_js(__('Reject', 'bae-interests')); ?>');
            $('#bae-request-modal-submit').data('action', 'reject');
            $('#bae-request-modal-submit').data('request-id', requestId);
            $('#bae-request-modal-notes').val('');
            
            // Show modal
            $('#bae-request-modal').fadeIn();
        });
        
        // Cancel modal
        $('#bae-request-modal-cancel').on('click', function() {
            $('#bae-request-modal').fadeOut();
        });
        
        // Submit modal
        $('#bae-request-modal-submit').on('click', function() {
            var $this = $(this);
            var action = $this.data('action');
            var requestId = $this.data('request-id');
            var notes = $('#bae-request-modal-notes').val();
            var $spinner = $('.bae-spinner');
            
            // Disable buttons and show spinner
            $this.prop('disabled', true);
            $('#bae-request-modal-cancel').prop('disabled', true);
            $spinner.show();
            
            if (action === 'approve') {
                // Approve request
                $.post(baeInterestsAdmin.ajaxUrl, {
                    action: 'bae_admin_approve_interest',
                    nonce: baeInterestsAdmin.nonce,
                    request_id: requestId,
                    notes: notes
                }, function(response) {
                    if (response.success) {
                        // Remove request row
                        $('#request-' + requestId).fadeOut(function() {
                            $(this).remove();
                        });
                        
                        // Hide modal
                        $('#bae-request-modal').fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                }).always(function() {
                    // Re-enable buttons and hide spinner
                    $this.prop('disabled', false);
                    $('#bae-request-modal-cancel').prop('disabled', false);
                    $spinner.hide();
                });
            } else if (action === 'reject') {
                // Reject request
                $.post(baeInterestsAdmin.ajaxUrl, {
                    action: 'bae_admin_reject_interest',
                    nonce: baeInterestsAdmin.nonce,
                    request_id: requestId,
                    notes: notes
                }, function(response) {
                    if (response.success) {
                        // Remove request row
                        $('#request-' + requestId).fadeOut(function() {
                            $(this).remove();
                        });
                        
                        // Hide modal
                        $('#bae-request-modal').fadeOut();
                    } else {
                        alert(response.data.message);
                    }
                }).always(function() {
                    // Re-enable buttons and hide spinner
                    $this.prop('disabled', false);
                    $('#bae-request-modal-cancel').prop('disabled', false);
                    $spinner.hide();
                });
            }
        });
    })(jQuery);
</script>