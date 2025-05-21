<?php
/**
 * BuddyBoss Profile Component for Interests
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BB_Interests_Profile_Component {

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
        // Add the interests tab to BuddyBoss profiles
        add_action('bp_setup_nav', array($this, 'setup_profile_nav'), 20);
        
        // Add the interests tab content
        add_action('bp_screens', array($this, 'profile_screen_interests'));
        
        // Add AJAX handlers for profile operations
        add_action('wp_ajax_bb_load_more_interests', array($this, 'ajax_load_more_interests'));
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
     * Set up profile navigation
     */
    public function setup_profile_nav() {
        global $bp;

        // Determine position (try to add after existing tabs)
        $position = 100;
        
        // Add main nav item
        bp_core_new_nav_item(array(
            'name' => __('Interests', 'bb-interests'),
            'slug' => 'interests',
            'position' => $position,
            'screen_function' => array($this, 'profile_screen_interests'),
            'default_subnav_slug' => 'view',
            'item_css_id' => 'interests'
        ));
        
        // Add sub nav items
        bp_core_new_subnav_item(array(
            'name' => __('View', 'bb-interests'),
            'slug' => 'view',
            'parent_slug' => 'interests',
            'parent_url' => trailingslashit(bp_displayed_user_domain() . 'interests'),
            'screen_function' => array($this, 'profile_screen_interests'),
            'position' => 10,
            'user_has_access' => true
        ));
        
        // Only add the manage subnav for the logged-in user's profile
        if (bp_is_my_profile()) {
            bp_core_new_subnav_item(array(
                'name' => __('Manage', 'bb-interests'),
                'slug' => 'manage',
                'parent_slug' => 'interests',
                'parent_url' => trailingslashit(bp_displayed_user_domain() . 'interests'),
                'screen_function' => array($this, 'profile_screen_manage_interests'),
                'position' => 20,
                'user_has_access' => true
            ));
        }
    }

    /**
     * Display the interests screen
     */
    public function profile_screen_interests() {
        // Set the current nav item
        if (bp_is_user() && bp_current_component() == 'interests') {
            add_action('bp_template_title', array($this, 'profile_template_title'));
            add_action('bp_template_content', array($this, 'profile_template_content'));
            
            // Call BuddyPress template function
            bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
        }
    }

    /**
     * Display the manage interests screen
     */
    public function profile_screen_manage_interests() {
        // Set the current nav item
        if (bp_is_user() && bp_current_component() == 'interests' && bp_current_action() == 'manage') {
            add_action('bp_template_title', array($this, 'profile_manage_template_title'));
            add_action('bp_template_content', array($this, 'profile_manage_template_content'));
            
            // Call BuddyPress template function
            bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
        }
    }

    /**
     * Display the interests tab title
     */
    public function profile_template_title() {
        if (bp_is_my_profile()) {
            echo __('My Interests', 'bb-interests');
        } else {
            echo sprintf(__('%s\'s Interests', 'bb-interests'), bp_get_displayed_user_fullname());
        }
    }

    /**
     * Display the interests tab content
     */
    public function profile_template_content() {
        // Get the displayed user ID
        $user_id = bp_displayed_user_id();
        
        // Get database instance
        $db = BB_Interests_DB::get_instance();
        
        // Get all categories
        $categories = $db->get_categories();
        
        // Include the profile view template
        include BB_INTERESTS_PLUGIN_DIR . 'templates/profile-interests.php';
    }

    /**
     * Display the manage interests tab title
     */
    public function profile_manage_template_title() {
        echo __('Manage My Interests', 'bb-interests');
    }

    /**
     * Display the manage interests tab content
     */
    public function profile_manage_template_content() {
        // Only allow the user to manage their own interests
        if (!bp_is_my_profile()) {
            echo '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span>';
            echo '<p>' . __('You do not have permission to manage this user\'s interests.', 'bb-interests') . '</p>';
            echo '</div>';
            return;
        }
        
        // Get the current user ID
        $user_id = get_current_user_id();
        
        // Get database instance
        $db = BB_Interests_DB::get_instance();
        
        // Get all interests
        $all_interests = $db->get_all_interests();
        
        // Get user's interests
        $user_interests = $db->get_user_interests($user_id);
        
        // Convert user interests to a simple array of interest IDs
        $user_interest_ids = array();
        foreach ($user_interests as $ui) {
            $user_interest_ids[$ui->interest_id] = $ui->show_in_search;
        }
        
        // Get all categories
        $categories = $db->get_categories();
        
        // Include the manage interests template
        include BB_INTERESTS_PLUGIN_DIR . 'templates/manage-interests.php';
    }

    /**
     * AJAX handler for loading more interests
     */
    public function ajax_load_more_interests() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_interests_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bb-interests')));
        }
        
        // Validate required fields
        if (empty($_POST['category_id']) || !isset($_POST['offset'])) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'bb-interests')));
        }
        
        // Sanitize input
        $category_id = intval($_POST['category_id']);
        $offset = intval($_POST['offset']);
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        // Get database instance
        $db = BB_Interests_DB::get_instance();
        
        // Get interests by category with pagination
        global $wpdb;
        $interests = $wpdb->get_results($wpdb->prepare(
            "SELECT i.* FROM {$db->table_interests} i
             WHERE i.category_id = %d AND i.approved = 1
             ORDER BY i.name ASC
             LIMIT 20 OFFSET %d",
            $category_id, $offset
        ));
        
        // Get user's interests if user_id is provided
        $user_interest_ids = array();
        if ($user_id > 0) {
            $user_interests = $db->get_user_interests($user_id);
            foreach ($user_interests as $ui) {
                $user_interest_ids[$ui->interest_id] = $ui->show_in_search;
            }
        }
        
        // Prepare the HTML response
        $html = '';
        foreach ($interests as $interest) {
            $has_interest = isset($user_interest_ids[$interest->id]);
            $show_in_search = $has_interest ? $user_interest_ids[$interest->id] : 1;
            
            $html .= '<div class="bb-interest-item">';
            $html .= '<a href="' . esc_url(home_url('/members/?interest=' . $interest->slug)) . '" class="bb-interest-link">';
            $html .= esc_html($interest->name);
            $html .= '</a>';
            
            if (bp_is_my_profile() || is_admin()) {
                $html .= '<div class="bb-interest-actions">';
                if ($has_interest) {
                    $html .= '<button class="bb-remove-interest" data-interest-id="' . esc_attr($interest->id) . '">';
                    $html .= '<span class="dashicons dashicons-no-alt"></span>';
                    $html .= '</button>';
                } else {
                    $html .= '<button class="bb-add-interest" data-interest-id="' . esc_attr($interest->id) . '">';
                    $html .= '<span class="dashicons dashicons-plus"></span>';
                    $html .= '</button>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Check if there are more interests to load
        $more = count($interests) == 20;
        
        wp_send_json_success(array(
            'html' => $html,
            'more' => $more,
            'offset' => $offset + count($interests)
        ));
        
        wp_die();
    }
}