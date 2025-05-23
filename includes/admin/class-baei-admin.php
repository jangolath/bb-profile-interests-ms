<?php
/**
 * Admin interface for BAE Profile Interests
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BAEI_Admin {

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Constructor method
     */
    public function __construct() {        
        // Register admin AJAX handlers
        add_action('wp_ajax_bae_admin_approve_interest', array($this, 'ajax_approve_interest'));
        add_action('wp_ajax_bae_admin_reject_interest', array($this, 'ajax_reject_interest'));
        add_action('wp_ajax_bae_admin_add_interest', array($this, 'ajax_add_interest'));
        add_action('wp_ajax_bae_admin_delete_interest', array($this, 'ajax_delete_interest'));
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Render the admin page based on the current tab
     */
    public function render_admin_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'interests';
        
        if ($tab === 'requests') {
            $this->requests_page();
        } elseif ($tab === 'settings') {
            $this->settings_page();
        } else {
            $this->interests_page();
        }
    }

    /**
     * Interests management page
     */
    public function interests_page() {
        // Process form submissions
        if (isset($_POST['bae_add_interest_submit']) && current_user_can('manage_network_options')) {
            check_admin_referer('bae_add_interest_nonce');
            
            if (!empty($_POST['interest_name']) && !empty($_POST['category_id'])) {
                $name = sanitize_text_field($_POST['interest_name']);
                $category_id = intval($_POST['category_id']);
                
                $db = BAEI_DB::get_instance();
                $result = $db->add_interest($name, $category_id);
                
                if ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                        esc_html__('Interest added successfully.', 'bae-interests') . 
                        '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . 
                        esc_html__('Error adding interest. Please try again.', 'bae-interests') . 
                        '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                    esc_html__('Please fill in all required fields.', 'bae-interests') . 
                    '</p></div>';
            }
        }
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get all interests
        $interests = $db->get_all_interests();
        
        // Get all categories
        $categories = $db->get_categories();
        
        // Include the admin view
        include BAEI_PLUGIN_DIR . 'includes/admin/views/interests.php';
    }

    /**
     * Interest requests page
     */
    public function requests_page() {
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get pending requests
        $requests = $db->get_pending_requests();
        
        // Include the admin view
        include BAEI_PLUGIN_DIR . 'includes/admin/views/requests.php';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        // Process form submissions
        if (isset($_POST['bae_interests_settings_submit']) && current_user_can('manage_network_options')) {
            check_admin_referer('bae_interests_settings_nonce');
            
            // Update settings
            update_option('bae_interests_enable_search', isset($_POST['enable_search']) ? 1 : 0);
            update_option('bae_interests_per_page', intval($_POST['per_page']));
            update_option('bae_interests_require_approval', isset($_POST['require_approval']) ? 1 : 0);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                esc_html__('Settings saved successfully.', 'bae-interests') . 
                '</p></div>';
        }
        
        // Get current settings
        $enable_search = get_option('bae_interests_enable_search', 1);
        $per_page = get_option('bae_interests_per_page', 20);
        $require_approval = get_option('bae_interests_require_approval', 1);
        
        // Include the admin view
        include BAEI_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    /**
     * AJAX handler for approving interest requests
     */
    public function ajax_approve_interest() {
        // Check if user is an admin
        if (!current_user_can('manage_network_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'bae-interests')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bae_interests_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bae-interests')));
        }
        
        // Validate required fields
        if (empty($_POST['request_id'])) {
            wp_send_json_error(array('message' => __('Request ID is required.', 'bae-interests')));
        }
        
        // Sanitize input
        $request_id = intval($_POST['request_id']);
        $notes = !empty($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get request details
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$db->table_requests} WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            wp_send_json_error(array('message' => __('Interest request not found.', 'bae-interests')));
        }
        
        // Add the interest
        $interest_id = $db->add_interest($request->name, $request->category_id);
        
        if (!$interest_id) {
            wp_send_json_error(array('message' => __('Failed to add interest.', 'bae-interests')));
        }
        
        // Update request status
        $db->update_request_status($request_id, 'approved', $notes);
        
        // Optionally, add the interest to the requester's profile
        $db->add_user_interest($request->user_id, $interest_id);
        
        // Notify the user
        $this->notify_user_of_approval($request->user_id, $request->name);
        
        wp_send_json_success(array(
            'message' => __('Interest request approved successfully.', 'bae-interests'),
            'interest_id' => $interest_id
        ));
        
        wp_die();
    }

    /**
     * AJAX handler for rejecting interest requests
     */
    public function ajax_reject_interest() {
        // Check if user is an admin
        if (!current_user_can('manage_network_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'bae-interests')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bae_interests_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bae-interests')));
        }
        
        // Validate required fields
        if (empty($_POST['request_id'])) {
            wp_send_json_error(array('message' => __('Request ID is required.', 'bae-interests')));
        }
        
        // Sanitize input
        $request_id = intval($_POST['request_id']);
        $notes = !empty($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get request details
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$db->table_requests} WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            wp_send_json_error(array('message' => __('Interest request not found.', 'bae-interests')));
        }
        
        // Update request status
        $result = $db->update_request_status($request_id, 'rejected', $notes);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to reject interest request.', 'bae-interests')));
        }
        
        // Notify the user
        $this->notify_user_of_rejection($request->user_id, $request->name, $notes);
        
        wp_send_json_success(array(
            'message' => __('Interest request rejected successfully.', 'bae-interests')
        ));
        
        wp_die();
    }

    /**
     * AJAX handler for adding a new interest
     */
    public function ajax_add_interest() {
        // Check if user is an admin
        if (!current_user_can('manage_network_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'bae-interests')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bae_interests_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bae-interests')));
        }
        
        // Validate required fields
        if (empty($_POST['interest_name']) || empty($_POST['category_id'])) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'bae-interests')));
        }
        
        // Sanitize input
        $name = sanitize_text_field($_POST['interest_name']);
        $category_id = intval($_POST['category_id']);
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Add the interest
        $interest_id = $db->add_interest($name, $category_id);
        
        if ($interest_id) {
            wp_send_json_success(array(
                'message' => __('Interest added successfully.', 'bae-interests'),
                'interest_id' => $interest_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Error adding interest. Please try again.', 'bae-interests')));
        }
        
        wp_die();
    }

    /**
     * AJAX handler for deleting an interest
     */
    public function ajax_delete_interest() {
        // Check if user is an admin
        if (!current_user_can('manage_network_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'bae-interests')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bae_interests_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bae-interests')));
        }
        
        // Validate required fields
        if (empty($_POST['interest_id'])) {
            wp_send_json_error(array('message' => __('Interest ID is required.', 'bae-interests')));
        }
        
        // Sanitize input
        $interest_id = intval($_POST['interest_id']);
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Delete the interest
        global $wpdb;
        $result = $wpdb->delete(
            $db->table_interests,
            array('id' => $interest_id),
            array('%d')
        );
        
        if ($result) {
            // Also delete all user interests referencing this interest
            $wpdb->delete(
                $db->table_user_interests,
                array('interest_id' => $interest_id),
                array('%d')
            );
            
            wp_send_json_success(array(
                'message' => __('Interest deleted successfully.', 'bae-interests')
            ));
        } else {
            wp_send_json_error(array('message' => __('Error deleting interest. Please try again.', 'bae-interests')));
        }
        
        wp_die();
    }

    /**
     * Notify user that their interest request was approved
     */
    private function notify_user_of_approval($user_id, $interest_name) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Your Interest Request Has Been Approved', 'bae-interests'), $site_name);
        
        $message = sprintf(
            __('Good news! Your request to add "%s" to our interests list has been approved. The interest has been added to your profile automatically.', 'bae-interests'),
            $interest_name
        );
        
        $profile_url = bp_core_get_user_domain($user_id) . 'profile/interests/';
        $message .= "\n\n" . sprintf(__('View your profile: %s', 'bae-interests'), $profile_url);
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Notify user that their interest request was rejected
     */
    private function notify_user_of_rejection($user_id, $interest_name, $notes = '') {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Update on Your Interest Request', 'bae-interests'), $site_name);
        
        $message = sprintf(
            __('Thank you for your interest submission "%s". Unfortunately, our admin team has decided not to add this interest at this time.', 'bae-interests'),
            $interest_name
        );
        
        if (!empty($notes)) {
            $message .= "\n\n" . __('Admin notes:', 'bae-interests') . "\n" . $notes;
        }
        
        $message .= "\n\n" . __('You can submit another interest at any time.', 'bae-interests');
        
        wp_mail($user->user_email, $subject, $message);
    }
}