<?php
/**
 * Database handler for BAE Profile Interests
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BAEI_DB {

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Table names
     */
    public $table_interests;
    public $table_user_interests;
    public $table_categories;
    public $table_requests;

    /**
     * Constructor method
     */
    public function __construct() {
        global $wpdb;

        // Set up table names
        $this->table_interests = $wpdb->base_prefix . 'bae_interests';
        $this->table_user_interests = $wpdb->base_prefix . 'bae_user_interests';
        $this->table_categories = $wpdb->base_prefix . 'bae_interest_categories';
        $this->table_requests = $wpdb->base_prefix . 'bae_interest_requests';

        // Add AJAX handlers for various operations
        add_action('wp_ajax_bae_submit_interest_request', array($this, 'ajax_submit_interest_request'));
        add_action('wp_ajax_bae_add_interest_to_profile', array($this, 'ajax_add_interest_to_profile'));
        add_action('wp_ajax_bae_remove_interest_from_profile', array($this, 'ajax_remove_interest_from_profile'));
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
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Interest Categories Table
        $sql_categories = "CREATE TABLE {$this->table_categories} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Interests Table
        $sql_interests = "CREATE TABLE {$this->table_interests} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            category_id bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            approved tinyint(1) NOT NULL DEFAULT '1',
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY category_id (category_id)
        ) $charset_collate;";

        // User Interests Table
        $sql_user_interests = "CREATE TABLE {$this->table_user_interests} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            interest_id bigint(20) NOT NULL,
            show_in_search tinyint(1) NOT NULL DEFAULT '1',
            added_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_interest (user_id,interest_id),
            KEY user_id (user_id),
            KEY interest_id (interest_id)
        ) $charset_collate;";

        // Interest Requests Table
        $sql_requests = "CREATE TABLE {$this->table_requests} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            category_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            requested_at datetime NOT NULL,
            status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            notes text,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create or update tables
        dbDelta($sql_categories);
        dbDelta($sql_interests);
        dbDelta($sql_user_interests);
        dbDelta($sql_requests);
    }

    /**
     * Add a category
     */
    public function add_category($name, $slug) {
        global $wpdb;

        // Check if category already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_categories} WHERE slug = %s",
            $slug
        ));

        if (!$existing) {
            $wpdb->insert(
                $this->table_categories,
                array(
                    'name' => $name,
                    'slug' => $slug
                ),
                array('%s', '%s')
            );
            return $wpdb->insert_id;
        }

        return $existing;
    }

    /**
     * Get all categories
     */
    public function get_categories() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_categories} ORDER BY name ASC"
        );
    }

    /**
     * Add an interest
     */
    public function add_interest($name, $category_id, $approved = 1) {
        global $wpdb;

        $slug = sanitize_title($name);

        // Check if interest already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_interests} WHERE slug = %s",
            $slug
        ));

        if (!$existing) {
            $wpdb->insert(
                $this->table_interests,
                array(
                    'name' => $name,
                    'slug' => $slug,
                    'category_id' => $category_id,
                    'created_at' => current_time('mysql'),
                    'approved' => $approved
                ),
                array('%s', '%s', '%d', '%s', '%d')
            );
            return $wpdb->insert_id;
        }

        return $existing;
    }

    /**
     * Get interests by category
     */
    public function get_interests_by_category($category_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_interests} 
             WHERE category_id = %d AND approved = 1 
             ORDER BY name ASC",
            $category_id
        ));
    }

    /**
     * Get all approved interests
     */
    public function get_all_interests() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT i.*, c.name as category_name, c.slug as category_slug 
             FROM {$this->table_interests} i
             JOIN {$this->table_categories} c ON i.category_id = c.id
             WHERE i.approved = 1 
             ORDER BY c.name ASC, i.name ASC"
        );
    }

    /**
     * Add an interest to a user's profile
     */
    public function add_user_interest($user_id, $interest_id, $show_in_search = 1) {
        global $wpdb;

        // Check if user already has this interest
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_user_interests} 
             WHERE user_id = %d AND interest_id = %d",
            $user_id, $interest_id
        ));

        if (!$existing) {
            $wpdb->insert(
                $this->table_user_interests,
                array(
                    'user_id' => $user_id,
                    'interest_id' => $interest_id,
                    'show_in_search' => $show_in_search,
                    'added_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s')
            );
            return $wpdb->insert_id;
        } else {
            // Update the show_in_search setting if it exists
            $wpdb->update(
                $this->table_user_interests,
                array('show_in_search' => $show_in_search),
                array('id' => $existing),
                array('%d'),
                array('%d')
            );
            return $existing;
        }
    }

    /**
     * Remove an interest from a user's profile
     */
    public function remove_user_interest($user_id, $interest_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_user_interests,
            array(
                'user_id' => $user_id,
                'interest_id' => $interest_id
            ),
            array('%d', '%d')
        );
    }

    /**
     * Get user interests
     */
    public function get_user_interests($user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT ui.*, i.name, i.slug, c.name as category_name, c.slug as category_slug
             FROM {$this->table_user_interests} ui
             JOIN {$this->table_interests} i ON ui.interest_id = i.id
             JOIN {$this->table_categories} c ON i.category_id = c.id
             WHERE ui.user_id = %d
             ORDER BY c.name ASC, i.name ASC",
            $user_id
        ));
    }

    /**
     * Get user interests by category
     */
    public function get_user_interests_by_category($user_id, $category_slug) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT ui.*, i.name, i.slug
             FROM {$this->table_user_interests} ui
             JOIN {$this->table_interests} i ON ui.interest_id = i.id
             JOIN {$this->table_categories} c ON i.category_id = c.id
             WHERE ui.user_id = %d AND c.slug = %s
             ORDER BY i.name ASC",
            $user_id, $category_slug
        ));
    }

    /**
     * Add an interest request
     */
    public function add_interest_request($name, $category_id, $user_id, $notes = '') {
        global $wpdb;

        $wpdb->insert(
            $this->table_requests,
            array(
                'name' => $name,
                'category_id' => $category_id,
                'user_id' => $user_id,
                'requested_at' => current_time('mysql'),
                'status' => 'pending',
                'notes' => $notes
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s')
        );

        return $wpdb->insert_id;
    }

    /**
     * Get pending interest requests
     */
    public function get_pending_requests() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT r.*, c.name as category_name, u.display_name as user_name
             FROM {$this->table_requests} r
             JOIN {$this->table_categories} c ON r.category_id = c.id
             JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.status = 'pending'
             ORDER BY r.requested_at DESC"
        );
    }

    /**
     * Update interest request status
     */
    public function update_request_status($request_id, $status, $notes = '') {
        global $wpdb;

        return $wpdb->update(
            $this->table_requests,
            array(
                'status' => $status,
                'notes' => $notes
            ),
            array('id' => $request_id),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * AJAX handler for submitting interest request
     */
    public function ajax_submit_interest_request() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_interests_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bb-interests')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to submit interests.', 'bb-interests')));
        }

        // Get current user ID
        $user_id = get_current_user_id();

        // Validate required fields
        if (empty($_POST['interest_name']) || empty($_POST['category_id'])) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'bb-interests')));
        }

        // Sanitize input
        $interest_name = sanitize_text_field($_POST['interest_name']);
        $category_id = intval($_POST['category_id']);
        $notes = !empty($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        // Add the request
        $request_id = $this->add_interest_request($interest_name, $category_id, $user_id, $notes);

        if ($request_id) {
            // Send notification to admin
            $this->notify_admin_of_new_request($request_id, $interest_name, $user_id);

            wp_send_json_success(array(
                'message' => __('Your interest has been submitted for approval. Thank you!', 'bb-interests'),
                'request_id' => $request_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Error submitting your interest. Please try again.', 'bb-interests')));
        }

        wp_die();
    }

    /**
     * Notify admin of new interest request
     */
    private function notify_admin_of_new_request($request_id, $interest_name, $user_id) {
        $user = get_userdata($user_id);
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] New Interest Request: %s', 'bb-interests'), $site_name, $interest_name);
        
        $message = sprintf(
            __('User %s has submitted a new interest request: %s. Review it in your admin dashboard.', 'bb-interests'),
            $user->display_name . ' (' . $user->user_email . ')',
            $interest_name
        );
        
        $admin_url = admin_url('admin.php?page=bb-interests-requests');
        $message .= "\n\n" . sprintf(__('Approve or reject this request: %s', 'bb-interests'), $admin_url);
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * AJAX handler for adding interest to profile
     */
    public function ajax_add_interest_to_profile() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_interests_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bb-interests')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to add interests.', 'bb-interests')));
        }

        // Get current user ID
        $user_id = get_current_user_id();

        // Validate required fields
        if (empty($_POST['interest_id'])) {
            wp_send_json_error(array('message' => __('Interest ID is required.', 'bb-interests')));
        }

        // Sanitize input
        $interest_id = intval($_POST['interest_id']);
        $show_in_search = isset($_POST['show_in_search']) ? 1 : 0;

        // Add the interest to the user's profile
        $result = $this->add_user_interest($user_id, $interest_id, $show_in_search);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Interest added to your profile successfully.', 'bb-interests'),
                'user_interest_id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Error adding interest to your profile. Please try again.', 'bb-interests')));
        }

        wp_die();
    }

    /**
     * AJAX handler for removing interest from profile
     */
    public function ajax_remove_interest_from_profile() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_interests_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bb-interests')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to remove interests.', 'bb-interests')));
        }

        // Get current user ID
        $user_id = get_current_user_id();

        // Validate required fields
        if (empty($_POST['interest_id'])) {
            wp_send_json_error(array('message' => __('Interest ID is required.', 'bb-interests')));
        }

        // Sanitize input
        $interest_id = intval($_POST['interest_id']);

        // Remove the interest from the user's profile
        $result = $this->remove_user_interest($user_id, $interest_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Interest removed from your profile successfully.', 'bb-interests')
            ));
        } else {
            wp_send_json_error(array('message' => __('Error removing interest from your profile. Please try again.', 'bb-interests')));
        }

        wp_die();
    }

    /**
     * Find users by interest
     */
    public function find_users_by_interest($interest_id, $limit = 20, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, u.user_email
             FROM {$wpdb->users} u
             JOIN {$this->table_user_interests} ui ON u.ID = ui.user_id
             WHERE ui.interest_id = %d AND ui.show_in_search = 1
             ORDER BY u.display_name ASC
             LIMIT %d OFFSET %d",
            $interest_id, $limit, $offset
        ));
    }

    /**
     * Count users by interest
     */
    public function count_users_by_interest($interest_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ui.user_id)
             FROM {$this->table_user_interests} ui
             WHERE ui.interest_id = %d AND ui.show_in_search = 1",
            $interest_id
        ));
    }

    /**
     * Get interest by slug
     */
    public function get_interest_by_slug($slug) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.name as category_name, c.slug as category_slug
             FROM {$this->table_interests} i
             JOIN {$this->table_categories} c ON i.category_id = c.id
             WHERE i.slug = %s AND i.approved = 1",
            $slug
        ));
    }
}