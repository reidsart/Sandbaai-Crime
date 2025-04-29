<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the admin area functionality of the plugin.
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin
 * @author     Your Name <your.email@example.com>
 */
class Sandbaai_Crime_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->load_dependencies();
    }

    /**
     * Load dependencies for admin functionality
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Load any admin-specific dependencies here
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Add admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add meta boxes for crime reports
        add_action('add_meta_boxes', array($this, 'add_crime_report_meta_boxes'));
        add_action('save_post_crime_report', array($this, 'save_crime_report_meta'));
        
        // Add meta boxes for security groups
        add_action('add_meta_boxes', array($this, 'add_security_group_meta_boxes'));
        add_action('save_post_security_group', array($this, 'save_security_group_meta'));

        // Add custom columns to admin list tables
        add_filter('manage_crime_report_posts_columns', array($this, 'crime_report_columns'));
        add_action('manage_crime_report_posts_custom_column', array($this, 'crime_report_column_content'), 10, 2);
        
        add_filter('manage_security_group_posts_columns', array($this, 'security_group_columns'));
        add_action('manage_security_group_posts_custom_column', array($this, 'security_group_column_content'), 10, 2);

        // Add sortable columns
        add_filter('manage_edit-crime_report_sortable_columns', array($this, 'crime_report_sortable_columns'));
        add_filter('manage_edit-security_group_sortable_columns', array($this, 'security_group_sortable_columns'));

        // Add filter dropdowns for crime reports
        add_action('restrict_manage_posts', array($this, 'add_crime_report_filters'));
        add_filter('parse_query', array($this, 'filter_crime_reports_by_metadata'));

        // Register AJAX handlers
        add_action('wp_ajax_approve_crime_report', array($this, 'ajax_approve_crime_report'));
        add_action('wp_ajax_export_crime_reports', array($this, 'ajax_export_crime_reports'));
    }

    /**
     * Register the admin menu pages and subpages
     *
     * @since    1.0.0
     * @access   public
     */
    public function register_admin_menu() {
        // Main menu 
        add_menu_page(
            __('Sandbaai Crime', 'sandbaai-crime'),
            __('Sandbaai Crime', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime',
            array($this, 'display_dashboard_page'),
            'dashicons-shield',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Dashboard', 'sandbaai-crime'),
            __('Dashboard', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime',
            array($this, 'display_dashboard_page')
        );

        // Crime reports management 
        add_submenu_page(
            'sandbaai-crime',
            __('Crime Reports', 'sandbaai-crime'),
            __('Crime Reports', 'sandbaai-crime'),
            'manage_options',
            'edit.php?post_type=crime_report'
        );

        // Crime categories management
        add_submenu_page(
            'sandbaai-crime',
            __('Crime Categories', 'sandbaai-crime'),
            __('Crime Categories', 'sandbaai-crime'),
            'manage_options',
            'edit-tags.php?taxonomy=crime_category&post_type=crime_report'
        );

        // Security groups management
        add_submenu_page(
            'sandbaai-crime',
            __('Security Groups', 'sandbaai-crime'),
            __('Security Groups', 'sandbaai-crime'),
            'manage_options',
            'edit.php?post_type=security_group'
        );

        // Reports & Statistics
        add_submenu_page(
            'sandbaai-crime',
            __('Statistics', 'sandbaai-crime'),
            __('Statistics', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime-statistics',
            array($this, 'display_statistics_page')
        );

        // Export functionality
        add_submenu_page(
            'sandbaai-crime',
            __('Export Data', 'sandbaai-crime'),
            __('Export Data', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime-export',
            array($this, 'display_export_page')
        );

        // Settings page
        add_submenu_page(
            'sandbaai-crime',
            __('Settings', 'sandbaai-crime'),
            __('Settings', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Enqueue admin-specific stylesheets and scripts.
     *
     * @since    1.0.0
     * @access   public
     */
    public function enqueue_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'sandbaai-crime') === false && 
            !$this->is_crime_report_admin_page() && 
            !$this->is_security_group_admin_page()) {
            return;
        }

        // CSS
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'css/sandbaai-crime-admin.css',
            array(),
            $this->version,
            'all'
        );

        // Load media scripts for uploader
        wp_enqueue_media();

        // Admin JS
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'js/sandbaai-crime-admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            $this->version,
            false
        );

        // For statistics page, add visualization libraries
        if ($hook === 'sandbaai-crime_page_sandbaai-crime-statistics') {
            wp_enqueue_script(
                $this->plugin_name . '-charts',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                $this->version,
                false
            );
            
            // Google Maps API for location visualization
            wp_enqueue_script(
                $this->plugin_name . '-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . get_option('sandbaai_crime_google_maps_api_key') . '&libraries=visualization',
                array(),
                $this->version,
                false
            );
            
            // Statistics specific JS
            wp_enqueue_script(
                $this->plugin_name . '-statistics',
                plugin_dir_url(__FILE__) . '../public/js/crime-statistics.js',
                array('jquery', $this->plugin_name . '-charts', $this->plugin_name . '-maps'),
                $this->version,
                false
            );
            
            // Localize script with data for charts
            wp_localize_script(
                $this->plugin_name . '-statistics',
                'sandbaaiCrimeStats',
                $this->get_statistics_data()
            );
        }
    }

    /**
     * Check if we're on a crime report admin page
     *
     * @since    1.0.0
     * @access   private
     * @return   boolean
     */
    private function is_crime_report_admin_page() {
        global $post_type, $pagenow;
        return ($post_type === 'crime_report' && in_array($pagenow, array('post.php', 'post-new.php', 'edit.php')));
    }

    /**
     * Check if we're on a security group admin page
     *
     * @since    1.0.0
     * @access   private
     * @return   boolean
     */
    private function is_security_group_admin_page() {
        global $post_type, $pagenow;
        return ($post_type === 'security_group' && in_array($pagenow, array('post.php', 'post-new.php', 'edit.php')));
    }

    /**
     * Display the dashboard page
     *
     * @since    1.0.0
     * @access   public
     */
    public function display_dashboard_page() {
        // Get recent crime reports
        $recent_reports = get_posts(array(
            'post_type' => 'crime_report',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        // Get pending crime reports
        $pending_reports = get_posts(array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_crime_report_status',
                    'value' => 'pending',
                    'compare' => '='
                )
            )
        ));

        // Get crime counts by category
        $crime_categories = get_terms(array(
            'taxonomy' => 'crime_category',
            'hide_empty' => false
        ));
        
        $category_counts = array();
        foreach ($crime_categories as $category) {
            $category_counts[$category->name] = $category->count;
        }

        // Include the dashboard view
        include plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Display the statistics page
     *
     * @since    1.0.0
     * @access   public
     */
    public function display_statistics_page() {
        // Include the statistics view
        include plugin_dir_path(__FILE__) . 'views/statistics.php';
    }

    /**
     * Display the export page
     *
     * @since    1.0.0
     * @access   public
     */
    public function display_export_page() {
        // Include the export view
        include plugin_dir_path(__FILE__) . 'views/export.php';
    }

    /**
     * Display the settings page
     *
     * @since    1.0.0
     * @access   public
     */
    public function display_settings_page() {
        // Save settings if form was submitted
        if (isset($_POST['sandbaai_crime_settings_nonce']) && 
            wp_verify_nonce($_POST['sandbaai_crime_settings_nonce'], 'sandbaai_crime_save_settings')) {
            $this->save_settings();
        }
        
        // Include the settings view
        include plugin_dir_path(__FILE__) . 'views/settings.php';
    }

    /**
     * Save plugin settings
     *
     * @since    1.0.0
     * @access   private
     */
    private function save_settings() {
        // Google Maps API Key
        if (isset($_POST['google_maps_api_key'])) {
            update_option('sandbaai_crime_google_maps_api_key', sanitize_text_field($_POST['google_maps_api_key']));
        }
        
        // WhatsApp notification settings
        if (isset($_POST['whatsapp_api_enabled'])) {
            update_option('sandbaai_crime_whatsapp_enabled', 1);
        } else {
            update_option('sandbaai_crime_whatsapp_enabled', 0);
        }
        
        if (isset($_POST['whatsapp_api_key'])) {
            update_option('sandbaai_crime_whatsapp_api_key', sanitize_text_field($_POST['whatsapp_api_key']));
        }
        
        // Email notification settings
        if (isset($_POST['email_notifications_enabled'])) {
            update_option('sandbaai_crime_email_enabled', 1);
        } else {
            update_option('sandbaai_crime_email_enabled', 0);
        }
        
        if (isset($_POST['notification_email'])) {
            update_option('sandbaai_crime_notification_email', sanitize_email($_POST['notification_email']));
        }
        
        // Zone settings
        if (isset($_POST['zone_coordinates'])) {
            update_option('sandbaai_crime_zone_coordinates', sanitize_textarea_field($_POST['zone_coordinates']));
        }
        
        // Display success message
        add_settings_error(
            'sandbaai_crime_settings',
            'settings_updated',
            __('Settings saved successfully.', 'sandbaai-crime'),
            'updated'
        );
    }

    /**
     * Add meta boxes for crime report posts
     *
     * @since    1.0.0
     * @access   public
     */
    public function add_crime_report_meta_boxes() {
        add_meta_box(
            'crime_report_details',
            __('Crime Report Details', 'sandbaai-crime'),
            array($this, 'render_crime_report_details_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_location',
            __('Location Information', 'sandbaai-crime'),
            array($this, 'render_crime_report_location_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_security_groups',
            __('Security Groups Involved', 'sandbaai-crime'),
            array($this, 'render_crime_report_security_groups_meta_box'),
            'crime_report',
            'side',
            'default'
        );
        
        add_meta_box(
            'crime_report_status',
            __('Report Status', 'sandbaai-crime'),
            array($this, 'render_crime_report_status_meta_box'),
            'crime_report',
            'side',
            'high'
        );
    }

    /**
     * Render the crime report details meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_crime_report_details_meta_box($post) {
        // Retrieve current values
        $crime_date = get_post_meta($post->ID, '_crime_date', true);
        $crime_time = get_post_meta($post->ID, '_crime_time', true);
        $crime_result = get_post_meta($post->ID, '_crime_result', true);
        $crime_description = get_post_meta($post->ID, '_crime_description', true);
        
        // Add nonce for security
        wp_nonce_field('save_crime_report_meta', 'crime_report_meta_nonce');
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/crime-report-details.php';
    }

    /**
     * Render the crime report location meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_crime_report_location_meta_box($post) {
        // Retrieve current values
        $address = get_post_meta($post->ID, '_crime_address', true);
        $zone = get_post_meta($post->ID, '_crime_zone', true);
        $latitude = get_post_meta($post->ID, '_crime_latitude', true);
        $longitude = get_post_meta($post->ID, '_crime_longitude', true);
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/crime-report-location.php';
    }

    /**
     * Render the crime report security groups meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_crime_report_security_groups_meta_box($post) {
        // Get all security groups
        $security_groups = get_posts(array(
            'post_type' => 'security_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get currently assigned security groups
        $assigned_groups = get_post_meta($post->ID, '_crime_security_groups', true);
        if (!is_array($assigned_groups)) {
            $assigned_groups = array();
        }
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/crime-report-security-groups.php';
    }

    /**
     * Render the crime report status meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_crime_report_status_meta_box($post) {
        // Retrieve current status
        $status = get_post_meta($post->ID, '_crime_report_status', true);
        if (empty($status)) {
            $status = 'pending';
        }
        
        // Get report submitted by
        $submitted_by = get_post_meta($post->ID, '_submitted_by', true);
        $user_info = '';
        if ($submitted_by) {
            $user = get_user_by('id', $submitted_by);
            if ($user) {
                $user_info = $user->display_name . ' (' . $user->user_email . ')';
            }
        }
        
        // Get submission time
        $submission_time = get_post_meta($post->ID, '_submission_time', true);
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/crime-report-status.php';
    }

    /**
     * Save crime report meta data
     *
     * @since    1.0.0
     * @access   public
     * @param    int    $post_id    The post ID.
     */
    public function save_crime_report_meta($post_id) {
        // Check nonce for security
        if (!isset($_POST['crime_report_meta_nonce']) || !wp_verify_nonce($_POST['crime_report_meta_nonce'], 'save_crime_report_meta')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save crime date
        if (isset($_POST['crime_date'])) {
            update_post_meta($post_id, '_crime_date', sanitize_text_field($_POST['crime_date']));
        }
        
        // Save crime time
        if (isset($_POST['crime_time'])) {
            update_post_meta($post_id, '_crime_time', sanitize_text_field($_POST['crime_time']));
        }
        
        // Save crime result
        if (isset($_POST['crime_result'])) {
            update_post_meta($post_id, '_crime_result', sanitize_text_field($_POST['crime_result']));
        }
        
        // Save crime description
        if (isset($_POST['crime_description'])) {
            update_post_meta($post_id, '_crime_description', wp_kses_post($_POST['crime_description']));
        }
        
        // Save address
        if (isset($_POST['crime_address'])) {
            update_post_meta($post_id, '_crime_address', sanitize_text_field($_POST['crime_address']));
        }
        
        // Save zone
        if (isset($_POST['crime_zone'])) {
            update_post_meta($post_id, '_crime_zone', sanitize_text_field($_POST['crime_zone']));
        }
        
        // Save coordinates
        if (isset($_POST['crime_latitude']) && isset($_POST['crime_longitude'])) {
            update_post_meta($post_id, '_crime_latitude', sanitize_text_field($_POST['crime_latitude']));
            update_post_meta($post_id, '_crime_longitude', sanitize_text_field($_POST['crime_longitude']));
        }
        
        // Save security groups
        if (isset($_POST['crime_security_groups'])) {
            $security_groups = array_map('intval', $_POST['crime_security_groups']);
            update_post_meta($post_id, '_crime_security_groups', $security_groups);
        } else {
            update_post_meta($post_id, '_crime_security_groups', array());
        }
        
        // Save status
        if (isset($_POST['crime_report_status'])) {
            $old_status = get_post_meta($post_id, '_crime_report_status', true);
            $new_status = sanitize_text_field($_POST['crime_report_status']);
            
            update_post_meta($post_id, '_crime_report_status', $new_status);
            
            // Trigger status change notifications
            if ($old_status !== $new_status) {
                $this->process_status_change($post_id, $old_status, $new_status);
            }
        }
    }

    /**
     * Process crime report status change
     *
     * @since    1.0.0
     * @access   private
     * @param    int       $post_id     The post ID.
     * @param    string    $old_status  The old status.
     * @param    string    $new_status  The new status.
     */
    private function process_status_change($post_id, $old_status, $new_status) {
        // Get the crime report
        $crime_report = get_post($post_id);
        
        // If report is approved, send notifications
        if ($new_status === 'approved' && $old_status === 'pending') {
            // Send WhatsApp notification if enabled
            if (get_option('sandbaai_crime_whatsapp_enabled')) {
                // Include WhatsApp notifications functionality
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/whatsapp-notifications.php';
                
                // Send notification
                $whatsapp = new Sandbaai_Crime_WhatsApp_Notifications();
                $whatsapp->send_crime_report_notification($post_id);
            }
            
            // Send email notification if enabled
            if (get_option('sandbaai_crime_email_enabled')) {
                $this->send_crime_report_email_notification($post_id);
            }
        }
    }

    /**
     * Send crime report email notification
     *
     * @since    1.0.0
     * @access   private
     * @param    int    $post_id    The post ID.
     */
    private function send_crime_report_email_notification($post_id) {
        $notification_email = get_option('sandbaai_crime_notification_email', get_option('admin_email'));
        
        // Get crime report details
        $crime_report = get_post($post_id);
        $crime_date = get_post_meta($post_id, '_crime_date', true);
        $crime_time = get_post_meta($post_id, '_crime_time', true);
        $crime_address = get_post_meta($post_id, '_crime_address', true);
        $crime_zone = get_post_meta($post_id, '_crime_zone', true);
        
        // Build email subject and content
        $subject = sprintf(__('New Crime Report: %s', 'sandbaai-crime'), esc_html($crime_report->post_title));
        
        $content = '<p>' . __('A new crime report has been approved:', 'sandbaai-crime') . '</p>';
        $content .= '<p><strong>' . __('Title:', 'sandbaai-crime') . '</strong> ' . esc_html($crime_report->post_title) . '</p>';
        $content .= '<p><strong>' . __('Date/Time:', 'sandbaai-crime') . '</strong> ' . esc_html($crime_date) . ' ' . esc_html($crime_time) . '</p>';
        $content .= '<p><strong>' . __('Location:', 'sandbaai-crime') . '</strong> ' . esc_html($crime_address) . ' (' . esc_html($crime_zone) . ')</p>';
        $content .= '<p><strong>' . __('Description:', 'sandbaai-crime') . '</strong> ' . wp_kses_post($crime_report->post_content) . '</p>';
        $content .= '<p><a href="' . esc_url(get_edit_post_link($post_id)) . '">' . __('View Report', 'sandbaai-crime') . '</a></p>';
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($notification_email, $subject, $content, $headers);
    }

    /**
     * Add meta boxes for security group posts
     *
     * @since    1.0.0
     * @access   public
     */
    public function add_security_group_meta_boxes() {
        add_meta_box(
            'security_group_details',
            __('Security Group Details', 'sandbaai-crime'),
            array($this, 'render_security_group_details_meta_box'),
            'security_group',
            'normal',
            'high'
        );
        
        add_meta_box(
            'security_group_contact',
            __('Contact Information', 'sandbaai-crime'),
            array($this, 'render_security_group_contact_meta_box'),
            'security_group',
            'normal',
            'default'
        );
        
        add_meta_box(
            'security_group_members',
            __('Group Members', 'sandbaai-crime'),
            array($this, 'render_security_group_members_meta_box'),
            'security_group',
            'side',
            'default'
        );
    }

    /**
     * Render the security group details meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_security_group_details_meta_box($post) {
        // Retrieve current values
        $logo_id = get_post_meta($post->ID, '_security_group_logo', true);
        $website = get_post_meta($post->ID, '_security_group_website', true);
        $description = get_post_meta($post->ID, '_security_group_description', true);
        
        // Add nonce for security
        wp_nonce_field('save_security_group_meta', 'security_group_meta_nonce');
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/security-group-details.php';
    }

    /**
     * Render the security group contact meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_security_group_contact_meta_box($post) {
        // Retrieve current values
        $phone = get_post_meta($post->ID, '_security_group_phone', true);
        $emergency_phone = get_post_meta($post->ID, '_security_group_emergency_phone', true);
        $email = get_post_meta($post->ID, '_security_group_email', true);
        $address = get_post_meta($post->ID, '_security_group_address', true);
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/security-group-contact.php';
    }

    /**
     * Render the security group members meta box
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post    $post    The post object.
     */
    public function render_security_group_members_meta_box($post) {
        // Get users with this security group
        $users = get_users(array(
            'meta_key' => '_security_group',
            'meta_value' => $post->ID,
            'number' => 10
        ));
        
        // Include the meta box view
        include plugin_dir_path(__FILE__) . 'views/meta-boxes/security-group-members.php';
    