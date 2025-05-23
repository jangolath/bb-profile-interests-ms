<?php
/**
 * Plugin Name: BAE Profile Interests
 * Plugin URI: https://yoursite.com
 * Description: A BuddyBoss Advanced Enhancement plugin that allows members to add searchable interests to their profiles similar to FetLife.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: bae-interests
 * Domain Path: /languages
 * License: GPL v2 or later
 * Network: true
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BAEI_Profile_Interests {

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
        // Define constants
        $this->define_constants();

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));

        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add admin menu to BAE
        add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
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
     * Define constants for the plugin
     */
    private function define_constants() {
        define('BAEI_VERSION', '1.0.0');
        define('BAEI_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('BAEI_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('bae-interests', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        // Check if BuddyBoss is active
        if (!class_exists('BuddyPress')) {
            add_action('admin_notices', array($this, 'buddyboss_required_notice'));
            return;
        }

        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));

        // Include required files
        $this->includes();

        // Initialize components
        $this->init_components();

        // Add shortcodes
        add_shortcode('bae_submit_interest', array($this, 'shortcode_submit_interest'));
    }

    /**
     * Add menu item to BAE network admin menu
     */
    public function add_network_admin_menu() {
        // Make sure the parent exists (if you want fallback in case BAE isn't active)
        if (!menu_page_url('buddyboss-advanced-enhancements', false)) {
            // Optional: Create parent menu if it doesn't exist
            // This ensures your submenu works even if the other BAE plugins are deactivated
            add_menu_page(
                __('BuddyBoss Advanced Enhancements', 'bae-interests'),
                __('BB Advanced', 'bae-interests'),
                'manage_network_options',
                'buddyboss-advanced-enhancements',
                function() {
                    echo '<div class="wrap">';
                    echo '<h1>' . __('BuddyBoss Advanced Enhancements', 'bae-interests') . '</h1>';
                    echo '<p>' . __('Welcome to BuddyBoss Advanced Enhancements. Use the submenu to access specific features.', 'bae-interests') . '</p>';
                    echo '</div>';
                },
                'dashicons-buddicons-buddypress-logo',
                3
            );
        }
        
        // Add this plugin as a submenu
        add_submenu_page(
            'buddyboss-advanced-enhancements',  // Parent slug
            __('Profile Interests', 'bae-interests'),
            __('Interests', 'bae-interests'),
            'manage_network_options',
            'bae-profile-interests',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render the admin page wrapper
     */
    public function render_admin_page() {
        // Get the DB instance
        $db = BAEI_DB::get_instance();
        
        // Get admin instance (will handle the actual admin page content)
        $admin = BAEI_Admin::get_instance();
        
        // Let the admin class render the appropriate page based on the current tab
        $admin->render_admin_page();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Database handler
        require_once BAEI_PLUGIN_DIR . 'includes/class-baei-db.php';
        
        // Admin functions
        require_once BAEI_PLUGIN_DIR . 'includes/admin/class-baei-admin.php';
        
        // Frontend functions
        require_once BAEI_PLUGIN_DIR . 'includes/class-baei-frontend.php';
        
        // Profile component
        require_once BAEI_PLUGIN_DIR . 'includes/class-baei-profile-component.php';
        
        // Search functions
        require_once BAEI_PLUGIN_DIR . 'includes/class-baei-search.php';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize DB
        BAEI_DB::get_instance();
        
        // Initialize admin
        if (is_admin()) {
            BAEI_Admin::get_instance();
        }
        
        // Initialize frontend
        BAEI_Frontend::get_instance();
        
        // Initialize profile component
        BAEI_Profile_Component::get_instance();
        
        // Initialize search
        BAEI_Search::get_instance();
    }

    /**
     * Register plugin assets
     */
    public function register_assets() {
        // CSS
        wp_register_style(
            'bae-interests-css',
            BAEI_PLUGIN_URL . 'assets/css/bb-interests.css',
            array(),
            BAEI_VERSION
        );
        wp_enqueue_style('bae-interests-css');

        // JavaScript
        wp_register_script(
            'bae-interests-js',
            BAEI_PLUGIN_URL . 'assets/js/bb-interests.js',
            array('jquery'),
            BAEI_VERSION,
            true
        );
        
        // Pass variables to script
        wp_localize_script('bae-interests-js', 'baeInterests', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bae_interests_nonce'),
            'confirmRemove' => __('Are you sure you want to remove this interest?', 'bae-interests'),
            'userId' => get_current_user_id()
        ));
        
        wp_enqueue_script('bae-interests-js');
    }

    /**
     * Register admin assets
     */
    public function register_admin_assets($hook) {
        // Only load on plugin admin pages
        if (strpos($hook, 'bae-profile-interests') === false) {
            return;
        }

        // Admin CSS
        wp_register_style(
            'bae-interests-admin-css',
            BAEI_PLUGIN_URL . 'assets/css/bb-interests-admin.css',
            array(),
            BAEI_VERSION
        );
        wp_enqueue_style('bae-interests-admin-css');

        // Admin JavaScript
        wp_register_script(
            'bae-interests-admin-js',
            BAEI_PLUGIN_URL . 'assets/js/bb-interests-admin.js',
            array('jquery'),
            BAEI_VERSION,
            true
        );
        
        // Pass variables to script
        wp_localize_script('bae-interests-admin-js', 'baeInterestsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bae_interests_admin_nonce'),
            'confirmDelete' => __('Are you sure you want to delete this interest? This cannot be undone.', 'bae-interests')
        ));
        
        wp_enqueue_script('bae-interests-admin-js');
    }

    /**
     * Show notice if BuddyBoss is not active
     */
    public function buddyboss_required_notice() {
        $class = 'notice notice-error';
        $message = __('BAE Profile Interests requires BuddyBoss Platform to be installed and activated.', 'bae-interests');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    /**
     * Shortcode for interest submission form
     */
    public function shortcode_submit_interest($atts) {
        $atts = shortcode_atts(array(
            'button_text' => __('Submit Interest', 'bae-interests'),
            'success_message' => __('Thank you for your submission. The admin will review it shortly.', 'bae-interests'),
        ), $atts, 'bae_submit_interest');

        // Only show form to logged in users
        if (!is_user_logged_in()) {
            return '<div class="bae-interests-login-required">' . __('Please log in to submit interests.', 'bae-interests') . '</div>';
        }

        ob_start();
        include BAEI_PLUGIN_DIR . 'templates/submit-interest-form.php';
        return ob_get_clean();
    }

    /**
     * Activation hook
     */
    public function activate() {
        // Create necessary database tables
        if (class_exists('BAEI_DB')) {
            BAEI_DB::get_instance()->create_tables();
        }
        
        // Set up default interest categories if they don't exist
        $this->setup_default_categories();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set up default interest categories
     */
    private function setup_default_categories() {
        // Categories to create
        $default_categories = array(
            'into_giving' => __('Into Giving', 'bae-interests'),
            'into_receiving' => __('Into Receiving', 'bae-interests'),
            'everything_about' => __('Everything About', 'bae-interests'),
            'curious_about' => __('Curious About', 'bae-interests')
        );
        
        // Get DB instance
        require_once BAEI_PLUGIN_DIR . 'includes/class-baei-db.php';
        $db = BAEI_DB::get_instance();
        
        // Add each category if it doesn't exist
        foreach ($default_categories as $slug => $name) {
            $db->add_category($name, $slug);
        }
    }

    /**
     * Deactivation hook
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function bae_profile_interests_init() {
    return BAEI_Profile_Interests::get_instance();
}
add_action('plugins_loaded', 'bae_profile_interests_init');