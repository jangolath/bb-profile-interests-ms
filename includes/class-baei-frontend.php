<?php
/**
 * BAE Interests Frontend
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BAEI_Frontend {

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
        // Add shortcodes
        add_shortcode('bae_interests_list', array($this, 'shortcode_interests_list'));
        
        // Add widget
        add_action('widgets_init', array($this, 'register_widgets'));
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
     * Register widgets
     */
    public function register_widgets() {
        register_widget('BAEI_Widget');
    }

    /**
     * Shortcode for displaying a list of interests by category
     */
    public function shortcode_interests_list($atts) {
        $atts = shortcode_atts(array(
            'category' => '',       // Category slug
            'limit' => 20,          // Number of interests to show
            'columns' => 3,         // Number of columns
            'show_count' => true,   // Show count of users with each interest
        ), $atts, 'bae_interests_list');
        
        // Sanitize attributes
        $category = sanitize_text_field($atts['category']);
        $limit = intval($atts['limit']);
        $columns = intval($atts['columns']);
        $show_count = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get interests
        $interests = array();
        
        if (!empty($category)) {
            // Get category ID from slug
            global $wpdb;
            $category_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$db->table_categories} WHERE slug = %s",
                $category
            ));
            
            if ($category_id) {
                $interests = $db->get_interests_by_category($category_id);
            }
        } else {
            // Get all interests
            $interests = $db->get_all_interests();
        }
        
        // Limit the number of interests
        if ($limit > 0 && count($interests) > $limit) {
            $interests = array_slice($interests, 0, $limit);
        }
        
        // Start output buffer
        ob_start();
        
        if (empty($interests)) {
            echo '<p>' . __('No interests found.', 'bae-interests') . '</p>';
            return ob_get_clean();
        }
        
        echo '<div class="bae-interests-list bae-interests-columns-' . esc_attr($columns) . '">';
        
        foreach ($interests as $interest) {
            echo '<div class="bae-interest-item">';
            echo '<a href="' . esc_url(home_url('/members/?interest=' . $interest->slug)) . '" class="bae-interest-link">';
            echo esc_html($interest->name);
            
            if ($show_count) {
                $count = $db->count_users_by_interest($interest->id);
                echo ' <span class="bae-interest-count">(' . esc_html($count) . ')</span>';
            }
            
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
}

/**
 * BAE Interests Widget
 */
class BAEI_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'bae_interests_widget',
            __('Interests', 'bae-interests'),
            array(
                'description' => __('Display a list of popular interests.', 'bae-interests'),
                'classname' => 'widget_bae_interests'
            )
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get popular interests
        global $wpdb;
        $interests = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, COUNT(ui.id) as user_count 
             FROM {$db->table_interests} i
             JOIN {$db->table_user_interests} ui ON i.id = ui.interest_id
             WHERE i.approved = 1 AND ui.show_in_search = 1
             GROUP BY i.id
             ORDER BY user_count DESC
             LIMIT %d",
            intval($instance['limit'])
        ));
        
        if (empty($interests)) {
            echo '<p>' . __('No interests found.', 'bae-interests') . '</p>';
        } else {
            echo '<ul class="bae-interests-widget-list">';
            
            foreach ($interests as $interest) {
                echo '<li class="bae-interest-widget-item">';
                echo '<a href="' . esc_url(home_url('/members/?interest=' . $interest->slug)) . '" class="bae-interest-link">';
                echo esc_html($interest->name);
                
                if (!empty($instance['show_count'])) {
                    echo ' <span class="bae-interest-count">(' . esc_html($interest->user_count) . ')</span>';
                }
                
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Popular Interests', 'bae-interests');
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 10;
        $show_count = isset($instance['show_count']) ? (bool) $instance['show_count'] : true;
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'bae-interests'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php esc_html_e('Number of interests to show:', 'bae-interests'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($limit); ?>" size="3">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_count); ?> id="<?php echo esc_attr($this->get_field_id('show_count')); ?>" name="<?php echo esc_attr($this->get_field_name('show_count')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>"><?php esc_html_e('Display user count', 'bae-interests'); ?></label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 10;
        $instance['show_count'] = isset($new_instance['show_count']) ? (bool) $new_instance['show_count'] : false;
        
        return $instance;
    }
}

/**
 * BuddyBoss Interests Search
 */
class BB_Interests_Search {

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
        // Add interest parameter to member directory
        add_action('bp_before_members_loop', array($this, 'filter_members_by_interest'));
        
        // Add interest filter to member directory filters
        add_action('bp_members_directory_member_sub_types', array($this, 'add_interest_filter_to_directory'));
        
        // Add AJAX handler for search
        add_action('wp_ajax_bb_search_interests', array($this, 'ajax_search_interests'));
        add_action('wp_ajax_nopriv_bb_search_interests', array($this, 'ajax_search_interests'));
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
     * Filter members by interest
     */
    public function filter_members_by_interest() {
        // Check if we're on the members directory and have an interest parameter
        if (bp_is_directory() && bp_current_component() == 'members' && isset($_GET['interest'])) {
            // Sanitize the interest slug
            $interest_slug = sanitize_text_field($_GET['interest']);
            
            // Get database instance
            $db = BB_Interests_DB::get_instance();
            
            // Get interest by slug
            $interest = $db->get_interest_by_slug($interest_slug);
            
            if ($interest) {
                // Get users with this interest
                $users = $db->find_users_by_interest($interest->id, 9999); // Large number to get all users
                
                if (!empty($users)) {
                    // Get array of user IDs
                    $user_ids = array();
                    foreach ($users as $user) {
                        $user_ids[] = $user->ID;
                    }
                    
                    // Add filter to BP_User_Query to limit to these users
                    add_filter('bp_user_query_uid_clauses', function($clauses) use ($user_ids) {
                        global $wpdb;
                        
                        if (!empty($user_ids)) {
                            $ids_sql = implode(',', array_map('intval', $user_ids));
                            $clauses['where'][] = "u.ID IN ($ids_sql)";
                        } else {
                            // No users found with this interest, return empty result
                            $clauses['where'][] = "1=0";
                        }
                        
                        return $clauses;
                    });
                    
                    // Add title to the page
                    add_filter('bp_page_title', function($title) use ($interest) {
                        return sprintf(__('Members interested in: %s', 'bb-interests'), $interest->name);
                    });
                    
                    // Add breadcrumb
                    add_filter('bp_get_navs', function($navs) use ($interest) {
                        if (isset($navs['directory'])) {
                            $navs['directory']['members']['subnav'][$interest->slug] = array(
                                'name' => sprintf(__('Interested in: %s', 'bb-interests'), $interest->name),
                                'slug' => $interest->slug,
                                'parent_slug' => 'members',
                                'position' => 999
                            );
                        }
                        return $navs;
                    });
                }
            }
        }
    }

    /**
     * Add interest filter to member directory
     */
    public function add_interest_filter_to_directory() {
        // Get option to see if interest filtering is enabled
        $enable_search = get_option('bb_interests_enable_search', 1);
        
        if (!$enable_search) {
            return;
        }
        
        // Get database instance
        $db = BB_Interests_DB::get_instance();
        
        // Get categories
        $categories = $db->get_categories();
        
        if (empty($categories)) {
            return;
        }
        
        ?>
        <div class="subnav-filters filters no-ajax" id="subnav-filters-interests">
            <div class="bpbf-interest-filter subnav-search">
                <div class="bp-search">
                    <form action="" method="get" class="bp-interests-search-form" id="interests-search-form">
                        <label for="interests-search" class="bp-screen-reader-text"><?php esc_html_e('Search Interests...', 'bb-interests'); ?></label>
                        <input type="text" id="interests-search" placeholder="<?php esc_attr_e('Search Interests...', 'bb-interests'); ?>" autocomplete="off">
                        
                        <div class="bb-interests-search-results" style="display: none;">
                            <ul></ul>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for searching interests
     */
    public function ajax_search_interests() {
        // Validate required fields
        if (empty($_GET['term'])) {
            wp_send_json_error(array('message' => __('Search term is required.', 'bb-interests')));
        }
        
        // Sanitize input
        $term = sanitize_text_field($_GET['term']);
        
        // Get database instance
        $db = BB_Interests_DB::get_instance();
        
        // Search interests
        global $wpdb;
        $interests = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, c.name as category_name
             FROM {$db->table_interests} i
             JOIN {$db->table_categories} c ON i.category_id = c.id
             WHERE i.name LIKE %s AND i.approved = 1
             ORDER BY i.name ASC
             LIMIT 10",
            '%' . $wpdb->esc_like($term) . '%'
        ));
        
        $results = array();
        
        foreach ($interests as $interest) {
            $results[] = array(
                'id' => $interest->id,
                'name' => $interest->name,
                'slug' => $interest->slug,
                'category' => $interest->category_name,
                'url' => home_url('/members/?interest=' . $interest->slug)
            );
        }
        
        wp_send_json_success($results);
        
        wp_die();
    }
}