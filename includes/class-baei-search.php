<?php
/**
 * BAE Interests Search Handler
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BAEI_Search {

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
        add_action('wp_ajax_bae_search_interests', array($this, 'ajax_search_interests'));
        add_action('wp_ajax_nopriv_bae_search_interests', array($this, 'ajax_search_interests'));
        
        // Add search parameter handling
        add_action('bp_ajax_querystring', array($this, 'handle_search_querystring'), 20, 2);
        
        // Add search to BuddyBoss member directory
        add_filter('bp_directory_members_search_query_arg', array($this, 'add_interest_search_arg'));
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
            $db = BAEI_DB::get_instance();
            
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
                        return sprintf(__('Members interested in: %s', 'bae-interests'), $interest->name);
                    });
                    
                    // Add breadcrumb info
                    add_action('bp_before_directory_members_content', function() use ($interest) {
                        echo '<div class="bae-interest-filter-info">';
                        echo '<p>' . sprintf(__('Showing members interested in: <strong>%s</strong>', 'bae-interests'), esc_html($interest->name)) . '</p>';
                        echo '<a href="' . esc_url(bp_get_members_directory_permalink()) . '" class="bae-clear-filter">' . __('Clear filter', 'bae-interests') . '</a>';
                        echo '</div>';
                    });
                } else {
                    // No users found, show empty state
                    add_filter('bp_user_query_uid_clauses', function($clauses) {
                        $clauses['where'][] = "1=0";
                        return $clauses;
                    });
                    
                    add_action('bp_before_directory_members_content', function() use ($interest) {
                        echo '<div class="bae-interest-filter-info bae-no-results">';
                        echo '<p>' . sprintf(__('No members found interested in: <strong>%s</strong>', 'bae-interests'), esc_html($interest->name)) . '</p>';
                        echo '<a href="' . esc_url(bp_get_members_directory_permalink()) . '" class="bae-clear-filter">' . __('Clear filter', 'bae-interests') . '</a>';
                        echo '</div>';
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
        $enable_search = get_option('bae_interests_enable_search', 1);
        
        if (!$enable_search) {
            return;
        }
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Get categories
        $categories = $db->get_categories();
        
        if (empty($categories)) {
            return;
        }
        
        ?>
        <div class="subnav-filters filters no-ajax" id="subnav-filters-interests">
            <div class="bae-interest-filter subnav-search">
                <div class="bp-search">
                    <form action="" method="get" class="bae-interests-search-form" id="interests-search-form">
                        <label for="interests-search" class="bp-screen-reader-text"><?php esc_html_e('Search Interests...', 'bae-interests'); ?></label>
                        <input type="text" id="interests-search" placeholder="<?php esc_attr_e('Search Interests...', 'bae-interests'); ?>" autocomplete="off">
                        
                        <div class="bae-interests-search-results" style="display: none;">
                            <ul></ul>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .bae-interests-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .bae-interests-search-results ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .bae-interests-search-results li {
            margin: 0;
            padding: 0;
        }
        
        .bae-interests-search-results a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #eee;
        }
        
        .bae-interests-search-results a:hover {
            background: #f5f5f5;
            color: #007cba;
        }
        
        .bae-interests-search-results .category {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        
        .bae-interest-filter-info {
            background: #e7f3ff;
            border: 1px solid #72aee6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .bae-interest-filter-info.bae-no-results {
            background: #fef7f0;
            border-color: #f56e28;
        }
        
        .bae-clear-filter {
            color: #0073aa;
            text-decoration: none;
            font-weight: 500;
        }
        
        .bae-clear-filter:hover {
            text-decoration: underline;
        }
        </style>
        <?php
    }

    /**
     * Handle search querystring modifications
     */
    public function handle_search_querystring($qs, $object) {
        if ($object !== 'members') {
            return $qs;
        }
        
        // Check if we have an interest filter
        if (isset($_GET['interest'])) {
            $interest_slug = sanitize_text_field($_GET['interest']);
            
            // Parse existing querystring
            parse_str($qs, $args);
            
            // Add our interest filter
            $args['interest'] = $interest_slug;
            
            // Rebuild querystring
            $qs = http_build_query($args);
        }
        
        return $qs;
    }

    /**
     * Add interest search argument to member directory
     */
    public function add_interest_search_arg($search_terms) {
        // If we have an interest parameter, modify the search
        if (isset($_GET['interest'])) {
            $interest_slug = sanitize_text_field($_GET['interest']);
            
            // Get database instance
            $db = BAEI_DB::get_instance();
            
            // Get interest by slug
            $interest = $db->get_interest_by_slug($interest_slug);
            
            if ($interest) {
                // Add interest name to search terms
                $search_terms[] = $interest->name;
            }
        }
        
        return $search_terms;
    }

    /**
     * AJAX handler for searching interests
     */
    public function ajax_search_interests() {
        // Validate required fields
        if (empty($_GET['term'])) {
            wp_send_json_error(array('message' => __('Search term is required.', 'bae-interests')));
        }
        
        // Sanitize input
        $term = sanitize_text_field($_GET['term']);
        
        // Get database instance
        $db = BAEI_DB::get_instance();
        
        // Search interests
        global $wpdb;
        $interests = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, c.name as category_name, COUNT(ui.id) as user_count
             FROM {$db->table_interests} i
             JOIN {$db->table_categories} c ON i.category_id = c.id
             LEFT JOIN {$db->table_user_interests} ui ON i.id = ui.interest_id AND ui.show_in_search = 1
             WHERE i.name LIKE %s AND i.approved = 1
             GROUP BY i.id
             ORDER BY user_count DESC, i.name ASC
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
                'user_count' => intval($interest->user_count),
                'url' => home_url('/members/?interest=' . $interest->slug)
            );
        }
        
        wp_send_json_success($results);
        
        wp_die();
    }

    /**
     * Get popular interests
     */
    public function get_popular_interests($limit = 10) {
        $db = BAEI_DB::get_instance();
        
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, c.name as category_name, COUNT(ui.id) as user_count 
             FROM {$db->table_interests} i
             JOIN {$db->table_categories} c ON i.category_id = c.id
             JOIN {$db->table_user_interests} ui ON i.id = ui.interest_id
             WHERE i.approved = 1 AND ui.show_in_search = 1
             GROUP BY i.id
             ORDER BY user_count DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get trending interests (recently added with growing user count)
     */
    public function get_trending_interests($limit = 5) {
        $db = BAEI_DB::get_instance();
        
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, c.name as category_name, COUNT(ui.id) as user_count 
             FROM {$db->table_interests} i
             JOIN {$db->table_categories} c ON i.category_id = c.id
             JOIN {$db->table_user_interests} ui ON i.id = ui.interest_id
             WHERE i.approved = 1 AND ui.show_in_search = 1
             AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY i.id
             HAVING user_count > 1
             ORDER BY user_count DESC, i.created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get related interests based on user overlap
     */
    public function get_related_interests($interest_id, $limit = 5) {
        $db = BAEI_DB::get_instance();
        
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i2.*, c.name as category_name, COUNT(DISTINCT ui2.user_id) as shared_users
             FROM {$db->table_user_interests} ui1
             JOIN {$db->table_user_interests} ui2 ON ui1.user_id = ui2.user_id
             JOIN {$db->table_interests} i2 ON ui2.interest_id = i2.id
             JOIN {$db->table_categories} c ON i2.category_id = c.id
             WHERE ui1.interest_id = %d
             AND ui2.interest_id != %d
             AND i2.approved = 1
             AND ui1.show_in_search = 1
             AND ui2.show_in_search = 1
             GROUP BY i2.id
             ORDER BY shared_users DESC
             LIMIT %d",
            $interest_id, $interest_id, $limit
        ));
    }

    /**
     * Search members by multiple interests
     */
    public function search_members_by_interests($interest_ids, $match_all = false, $limit = 20, $offset = 0) {
        if (empty($interest_ids) || !is_array($interest_ids)) {
            return array();
        }
        
        $db = BAEI_DB::get_instance();
        
        // Sanitize interest IDs
        $interest_ids = array_map('intval', $interest_ids);
        $ids_placeholder = implode(',', array_fill(0, count($interest_ids), '%d'));
        
        global $wpdb;
        
        if ($match_all) {
            // Users must have ALL specified interests
            $query = $wpdb->prepare(
                "SELECT u.ID, u.display_name, u.user_email, COUNT(ui.interest_id) as matching_interests
                 FROM {$wpdb->users} u
                 JOIN {$db->table_user_interests} ui ON u.ID = ui.user_id
                 WHERE ui.interest_id IN ($ids_placeholder)
                 AND ui.show_in_search = 1
                 GROUP BY u.ID
                 HAVING matching_interests = %d
                 ORDER BY u.display_name ASC
                 LIMIT %d OFFSET %d",
                array_merge($interest_ids, array(count($interest_ids), $limit, $offset))
            );
        } else {
            // Users must have ANY of the specified interests
            $query = $wpdb->prepare(
                "SELECT DISTINCT u.ID, u.display_name, u.user_email
                 FROM {$wpdb->users} u
                 JOIN {$db->table_user_interests} ui ON u.ID = ui.user_id
                 WHERE ui.interest_id IN ($ids_placeholder)
                 AND ui.show_in_search = 1
                 ORDER BY u.display_name ASC
                 LIMIT %d OFFSET %d",
                array_merge($interest_ids, array($limit, $offset))
            );
        }
        
        return $wpdb->get_results($query);
    }

    /**
     * Get interest statistics
     */
    public function get_interest_stats($interest_id) {
        $db = BAEI_DB::get_instance();
        
        global $wpdb;
        
        $stats = array();
        
        // Total users with this interest
        $stats['total_users'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$db->table_user_interests} 
             WHERE interest_id = %d AND show_in_search = 1",
            $interest_id
        ));
        
        // Users added in last 30 days
        $stats['recent_users'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$db->table_user_interests} 
             WHERE interest_id = %d AND show_in_search = 1 
             AND added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $interest_id
        ));
        
        // Growth rate
        $stats['growth_rate'] = $stats['total_users'] > 0 ? 
            round(($stats['recent_users'] / $stats['total_users']) * 100, 2) : 0;
        
        return $stats;
    }
}