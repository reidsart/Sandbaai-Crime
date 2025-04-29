<?php
/**
 * Crime Report Management
 * 
 * Handles creation and management of crime reports
 * 
 * @package Sandbaai_Crime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Sandbaai_Crime_Reports {
    
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register custom post type and taxonomy
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post meta
        add_action('save_post_crime_report', array($this, 'save_meta_box_data'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register shortcodes
        add_shortcode('crime_reporting_form', array($this, 'crime_reporting_form_shortcode'));
        add_shortcode('crime_statistics', array($this, 'crime_statistics_shortcode'));
        
        // AJAX handlers for form submission
        add_action('wp_ajax_submit_crime_report', array($this, 'ajax_submit_crime_report'));
        add_action('wp_ajax_nopriv_submit_crime_report', array($this, 'ajax_submit_crime_report'));
        
        // Filter crime report content
        add_filter('the_content', array($this, 'crime_report_content_filter'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Register crime report post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Crime Reports', 'Post Type General Name', 'sandbaai-crime'),
            'singular_name'         => _x('Crime Report', 'Post Type Singular Name', 'sandbaai-crime'),
            'menu_name'             => __('Crime Reports', 'sandbaai-crime'),
            'name_admin_bar'        => __('Crime Report', 'sandbaai-crime'),
            'archives'              => __('Crime Report Archives', 'sandbaai-crime'),
            'attributes'            => __('Crime Report Attributes', 'sandbaai-crime'),
            'all_items'             => __('All Crime Reports', 'sandbaai-crime'),
            'add_new_item'          => __('Add New Crime Report', 'sandbaai-crime'),
            'add_new'               => __('Add New', 'sandbaai-crime'),
            'new_item'              => __('New Crime Report', 'sandbaai-crime'),
            'edit_item'             => __('Edit Crime Report', 'sandbaai-crime'),
            'update_item'           => __('Update Crime Report', 'sandbaai-crime'),
            'view_item'             => __('View Crime Report', 'sandbaai-crime'),
            'view_items'            => __('View Crime Reports', 'sandbaai-crime'),
            'search_items'          => __('Search Crime Report', 'sandbaai-crime'),
            'not_found'             => __('Not found', 'sandbaai-crime'),
            'not_found_in_trash'    => __('Not found in Trash', 'sandbaai-crime'),
        );
        
        $args = array(
            'label'                 => __('Crime Report', 'sandbaai-crime'),
            'description'           => __('Crime reports in Sandbaai', 'sandbaai-crime'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // Will be added under custom menu
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-shield-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('crime_report', $args);
    }

    /**
     * Register crime category taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                       => _x('Crime Categories', 'Taxonomy General Name', 'sandbaai-crime'),
            'singular_name'              => _x('Crime Category', 'Taxonomy Singular Name', 'sandbaai-crime'),
            'menu_name'                  => __('Crime Categories', 'sandbaai-crime'),
            'all_items'                  => __('All Crime Categories', 'sandbaai-crime'),
            'parent_item'                => __('Parent Crime Category', 'sandbaai-crime'),
            'parent_item_colon'          => __('Parent Crime Category:', 'sandbaai-crime'),
            'new_item_name'              => __('New Crime Category Name', 'sandbaai-crime'),
            'add_new_item'               => __('Add New Crime Category', 'sandbaai-crime'),
            'edit_item'                  => __('Edit Crime Category', 'sandbaai-crime'),
            'update_item'                => __('Update Crime Category', 'sandbaai-crime'),
            'view_item'                  => __('View Crime Category', 'sandbaai-crime'),
            'separate_items_with_commas' => __('Separate crime categories with commas', 'sandbaai-crime'),
            'add_or_remove_items'        => __('Add or remove crime categories', 'sandbaai-crime'),
            'choose_from_most_used'      => __('Choose from the most used', 'sandbaai-crime'),
            'popular_items'              => __('Popular Crime Categories', 'sandbaai-crime'),
            'search_items'               => __('Search Crime Categories', 'sandbaai-crime'),
            'not_found'                  => __('Not Found', 'sandbaai-crime'),
            'no_terms'                   => __('No crime categories', 'sandbaai-crime'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
        );
        
        register_taxonomy('crime_category', array('crime_report'), $args);
        
        // Add default categories if they don't exist
        if (!term_exists('Burglary', 'crime_category')) {
            wp_insert_term('Burglary', 'crime_category');
        }
        
        if (!term_exists('Theft', 'crime_category')) {
            wp_insert_term('Theft', 'crime_category');
        }
        
        if (!term_exists('Assault', 'crime_category')) {
            wp_insert_term('Assault', 'crime_category');
        }
        
        if (!term_exists('Suspicious Activity', 'crime_category')) {
            wp_insert_term('Suspicious Activity', 'crime_category');
        }
        
        if (!term_exists('Vandalism', 'crime_category')) {
            wp_insert_term('Vandalism', 'crime_category');
        }
    }

    /**
     * Add meta boxes to crime report post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crime_report_details',
            __('Crime Details', 'sandbaai-crime'),
            array($this, 'render_details_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_location',
            __('Crime Location', 'sandbaai-crime'),
            array($this, 'render_location_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_response',
            __('Response Details', 'sandbaai-crime'),
            array($this, 'render_response_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
    }

    /**
     * Render details meta box
     */
    public function render_details_meta_box($post) {
        wp_nonce_field('crime_report_meta_box', 'crime_report_meta_box_nonce');
        
        $crime_date = get_post_meta($post->ID, '_crime_date', true);
        $crime_time = get_post_meta($post->ID, '_crime_time', true);
        $reporter_id = get_post_meta($post->ID, '_reporter_id', true);
        $reporter_contact = get_post_meta($post->ID, '_reporter_contact', true);
        ?>
        <p>
            <label for="crime_date"><?php _e('Date of Incident:', 'sandbaai-crime'); ?></label><br>
            <input type="date" id="crime_date" name="crime_date" value="<?php echo esc_attr($crime_date); ?>" class="regular-text" />
        </p>
        
        <p>
            <label for="crime_time"><?php _e('Time of Incident:', 'sandbaai-crime'); ?></label><br>
            <input type="time" id="crime_time" name="crime_time" value="<?php echo esc_attr($crime_time); ?>" class="regular-text" />
        </p>
        
        <p>
            <label for="reporter_contact"><?php _e('Reporter Contact Number:', 'sandbaai-crime'); ?></label><br>
            <input type="text" id="reporter_contact" name="reporter_contact" value="<?php echo esc_attr($reporter_contact); ?>" class="regular-text" />
        </p>
        
        <?php if (!empty($reporter_id)) : ?>
            <p>
                <strong><?php _e('Reported by:', 'sandbaai-crime'); ?></strong>
                <?php 
                $user = get_user_by('id', $reporter_id);
                if ($user) {
                    echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
                } else {
                    _e('Unknown user', 'sandbaai-crime');
                }
                ?>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render location meta box
     */
    public function render_location_meta_box($post) {
        $crime_location = get_post_meta($post->ID, '_crime_location', true);
        $crime_zone = get_post_meta($post->ID, '_crime_zone', true);
        $crime_latitude = get_post_meta($post->ID, '_crime_latitude', true);
        $crime_longitude = get_post_meta($post->ID, '_crime_longitude', true);
        
        // Get all available zones
        $zones = $this->get_all_zones();
        ?>
        <p>
            <label for="crime_location"><?php _e('Address/Location:', 'sandbaai-crime'); ?></label><br>
            <input type="text" id="crime_location" name="crime_location" value="<?php echo esc_attr($crime_location); ?>" class="large-text" />
        </p>
        
        <p>
            <label for="crime_zone"><?php _e('Zone:', 'sandbaai-crime'); ?></label><br>
            <select id="crime_zone" name="crime_zone">
                <option value=""><?php _e('Select Zone', 'sandbaai-crime'); ?></option>
                <?php foreach ($zones as $zone_id => $zone_name) : ?>
                    <option value="<?php echo esc_attr($zone_id); ?>" <?php selected($crime_zone, $zone_id); ?>>
                        <?php echo esc_html($zone_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <div class="coordinates-fields">
            <p>
                <label for="crime_latitude"><?php _e('Latitude:', 'sandbaai-crime'); ?></label><br>
                <input type="text" id="crime_latitude" name="crime_latitude" value="<?php echo esc_attr($crime_latitude); ?>" class="medium-text" />
            </p>
            
            <p>
                <label for="crime_longitude"><?php _e('Longitude:', 'sandbaai-crime'); ?></label><br>
                <input type="text" id="crime_longitude" name="crime_longitude" value="<?php echo esc_attr($crime_longitude); ?>" class="medium-text" />
            </p>
        </div>
        
        <!-- Map display would go here in future enhancement -->
        <?php
    }

    /**
     * Render response meta box
     */
    public function render_response_meta_box($post) {
        $crime_status = get_post_meta($post->ID, '_crime_status', true);
        $security_groups = get_post_meta($post->ID, '_security_groups', true);
        $resolution_notes = get_post_meta($post->ID, '_resolution_notes', true);
        
        // Get all security groups
        $all_security_groups = get_posts(array(
            'post_type' => 'security_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        if (!is_array($security_groups)) {
            $security_groups = array();
        }
        ?>
        <p>
            <label for="crime_status"><?php _e('Status:', 'sandbaai-crime'); ?></label><br>
            <select id="crime_status" name="crime_status">
                <option value="reported" <?php selected($crime_status, 'reported'); ?>><?php _e('Reported', 'sandbaai-crime'); ?></option>
                <option value="investigating" <?php selected($crime_status, 'investigating'); ?>><?php _e('Investigating', 'sandbaai-crime'); ?></option>
                <option value="resolved" <?php selected($crime_status, 'resolved'); ?>><?php _e('Resolved', 'sandbaai-crime'); ?></option>
                <option value="unresolved" <?php selected($crime_status, 'unresolved'); ?>><?php _e('Unresolved', 'sandbaai-crime'); ?></option>
                <option value="false_alarm" <?php selected($crime_status, 'false_alarm'); ?>><?php _e('False Alarm', 'sandbaai-crime'); ?></option>
            </select>
        </p>
        
        <p><strong><?php _e('Security Groups Involved:', 'sandbaai-crime'); ?></strong></p>
        <div class="security-groups-selection">
            <?php foreach ($all_security_groups as $group) : ?>
                <p>
                    <label>
                        <input type="checkbox" name="security_groups[]" value="<?php echo esc_attr($group->ID); ?>" 
                            <?php checked(in_array($group->ID, $security_groups)); ?> />
                        <?php echo esc_html($group->post_title); ?>
                    </label>
                </p>
            <?php endforeach; ?>
        </div>
        
        <p>
            <label for="resolution_notes"><?php _e('Resolution Notes:', 'sandbaai-crime'); ?></label><br>
            <textarea id="resolution_notes" name="resolution_notes" rows="4" class="large-text"><?php echo esc_textarea($resolution_notes); ?></textarea>
        </p>
        <?php
    }

    /**
     * Get all available zones in Sandbaai
     */
    private function get_all_zones() {
        // This would ideally come from a zone management system
        // For now, we'll hardcode the 4 quadrants with subzones
        return array(
            'north-east' => __('North East', 'sandbaai-crime'),
            'north-west' => __('North West', 'sandbaai-crime'),
            'south-east' => __('South East', 'sandbaai-crime'),
            'south-west' => __('South West', 'sandbaai-crime'),
            'central' => __('Central', 'sandbaai-crime'),
            'beach-front' => __('Beach Front', 'sandbaai-crime'),
            'mountain-side' => __('Mountain Side', 'sandbaai-crime'),
            'industrial' => __('Industrial Area', 'sandbaai-crime'),
        );
    }

    /**
     * Save meta box data
     */
    public function save_meta_box_data($post_id) {
        // Check if nonce is set
        if (!isset($_POST['crime_report_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['crime_report_meta_box_nonce'], 'crime_report_meta_box')) {
            return;
        }
        
        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save details
        if (isset($_POST['crime_date'])) {
            update_post_meta($post_id, '_crime_date', sanitize_text_field($_POST['crime_date']));
        }
        
        if (isset($_POST['crime_time'])) {
            update_post_meta($post_id, '_crime_time', sanitize_text_field($_POST['crime_time']));
        }
        
        if (isset($_POST['reporter_contact'])) {
            update_post_meta($post_id, '_reporter_contact', sanitize_text_field($_POST['reporter_contact']));
        }
        
        // Save location
        if (isset($_POST['crime_location'])) {
            update_post_meta($post_id, '_crime_location', sanitize_text_field($_POST['crime_location']));
        }
        
        if (isset($_POST['crime_zone'])) {
            update_post_meta($post_id, '_crime_zone', sanitize_key($_POST['crime_zone']));
        }
        
        if (isset($_POST['crime_latitude']) && is_numeric($_POST['crime_latitude'])) {
            update_post_meta($post_id, '_crime_latitude', floatval($_POST['crime_latitude']));
        }
        
        if (isset($_POST['crime_longitude']) && is_numeric($_POST['crime_longitude'])) {
            update_post_meta($post_id, '_crime_longitude', floatval($_POST['crime_longitude']));
        }
        
        // Save response details
        if (isset($_POST['crime_status'])) {
            update_post_meta($post_id, '_crime_status', sanitize_key($_POST['crime_status']));
        }
        
        if (isset($_POST['security_groups']) && is_array($_POST['security_groups'])) {
            $security_groups = array_map('intval', $_POST['security_groups']);
            update_post_meta($post_id, '_security_groups', $security_groups);
        } else {
            update_post_meta($post_id, '_security_groups', array());
        }
        
        if (isset($_POST['resolution_notes'])) {
            update_post_meta($post_id, '_resolution_notes', sanitize_textarea_field($_POST['resolution_notes']));
        }
    }

    /**
     * Add crime reports admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sandbaai-crime', // Parent slug
            __('Crime Reports', 'sandbaai-crime'),
            __('Crime Reports', 'sandbaai-crime'),
            'edit_posts',
            'edit.php?post_type=crime_report'
        );
        
        add_submenu_page(
            'sandbaai-crime', // Parent slug
            __('Crime Categories', 'sandbaai-crime'),
            __('Crime Categories', 'sandbaai-crime'),
            'manage_categories',
            'edit-tags.php?taxonomy=crime_category&post_type=crime_report'
        );
    }

    /**
     * Shortcode for crime reporting form
     */
    public function crime_reporting_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Report a Crime', 'sandbaai-crime'),
        ), $atts);
        
        ob_start();
        include(SANDBAAI_CRIME_PLUGIN_DIR . 'public/views/crime-reporting-form.php');
        return ob_get_clean();
    }

    /**
     * Shortcode for crime statistics display
     */
    public function crime_statistics_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Crime Statistics', 'sandbaai-crime'),
            'show_map' => true,
            'show_chart' => true,
            'limit' => 50,
        ), $atts);
        
        ob_start();
        include(SANDBAAI_CRIME_PLUGIN_DIR . 'public/views/crime-statistics-display.php');
        return ob_get_clean();
    }

    /**
     * Handle crime report AJAX submission
     */
    public function ajax_submit_crime_report() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crime_report_submit')) {
            wp_send_json_error(array('message' => __('Security check failed', 'sandbaai-crime')));
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        $zone = isset($_POST['zone']) ? sanitize_key($_POST['zone']) : '';
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : '';
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : '';
        $contact = isset($_POST['contact']) ? sanitize_text_field($_POST['contact']) : '';
        
        if (empty($title) || empty($description) || empty($category) || empty($date) || empty($location)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'sandbaai-crime')));
        }
        
        // Create post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => current_user_can('publish_posts') ? 'publish' : 'pending',
            'post_type' => 'crime_report',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Set category
        wp_set_object_terms($post_id, array($category), 'crime_category');
        
        // Save metadata
        update_post_meta($post_id, '_crime_date', $date);
        update_post_meta($post_id, '_crime_time', $time);
        update_post_meta($post_id, '_crime_location', $location);
        update_post_meta($post_id, '_crime_zone', $zone);
        update_post_meta($post_id, '_crime_latitude', $latitude);
        update_post_meta($post_id, '_crime_longitude', $longitude);
        update_post_meta($post_id, '_reporter_contact', $contact);
        update_post_meta($post_id, '_crime_status', 'reported');
        
        // Save reporter if logged in
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_post_meta($post_id, '_reporter_id', $user_id);
        }
        
        // Handle image upload if present
        if (!empty($_FILES