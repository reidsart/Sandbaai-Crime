<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/reidsart/Sandbaai-Crime
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and admin-specific hooks
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin
 * @author     Reid Sart
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
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sandbaai-crime-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sandbaai-crime-admin.js', array( 'jquery' ), $this->version, false );
        
        // Add localized script data for admin-specific JavaScript
        wp_localize_script(
            $this->plugin_name,
            'sandbaai_crime_admin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'sandbaai_crime_admin_nonce' ),
                'confirm_delete' => __( 'Are you sure you want to delete this item?', 'sandbaai-crime' ),
            )
        );
    }

    /**
     * Register the admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Add main menu item
        add_menu_page(
            __( 'Sandbaai Crime', 'sandbaai-crime' ),
            __( 'Sandbaai Crime', 'sandbaai-crime' ),
            'manage_options',
            'sandbaai-crime',
            array( $this, 'display_dashboard_page' ),
            'dashicons-shield',
            30
        );

        // Add dashboard submenu
        add_submenu_page(
            'sandbaai-crime',
            __( 'Dashboard', 'sandbaai-crime' ),
            __( 'Dashboard', 'sandbaai-crime' ),
            'manage_options',
            'sandbaai-crime',
            array( $this, 'display_dashboard_page' )
        );

        // Add crime reports submenu
        add_submenu_page(
            'sandbaai-crime',
            __( 'Crime Reports', 'sandbaai-crime' ),
            __( 'Crime Reports', 'sandbaai-crime' ),
            'manage_options',
            'edit.php?post_type=crime_report'
        );

        // Add security groups submenu
        add_submenu_page(
            'sandbaai-crime',
            __( 'Security Groups', 'sandbaai-crime' ),
            __( 'Security Groups', 'sandbaai-crime' ),
            'manage_options',
            'edit.php?post_type=security_group'
        );

        // Add submit crime report submenu
        add_submenu_page(
            'sandbaai-crime',
            __( 'Add Crime Report', 'sandbaai-crime' ),
            __( 'Add Crime Report', 'sandbaai-crime' ),
            'edit_posts',
            'sandbaai-crime-add-report',
            array( $this, 'display_crime_report_form' )
        );

        // Add Statistics submenu
        add_submenu_page(
            'sandbaai-crime',
            __( 'Crime Statistics', 'sandbaai-crime' ),
            __( 'Crime Statistics', 'sandbaai-crime' ),
            'read',
            'sandbaai-crime-statistics',
            array( $this, 'display_crime_statistics' )
        );

        // Add Settings submenu
        add_submenu_page(
            'sandbaai-crime',
            __( 'Settings', 'sandbaai-crime' ),
            __( 'Settings', 'sandbaai-crime' ),
            'manage_options',
            'sandbaai-crime-settings',
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        // Get statistics for the dashboard
        $stats = $this->get_crime_statistics();
        
        // Include the dashboard view
        include plugin_dir_path( __FILE__ ) . 'views/dashboard.php';
    }

    /**
     * Display the crime report submission form.
     *
     * @since    1.0.0
     */
    public function display_crime_report_form() {
        // Get security groups for dropdown
        $security_groups = $this->get_security_groups();
        
        // Get crime categories for dropdown
        $crime_categories = $this->get_crime_categories();
        
        // Include the crime report form view
        include plugin_dir_path( __FILE__ ) . 'views/crime-report-form.php';
    }

    /**
     * Display the crime statistics page.
     *
     * @since    1.0.0
     */
    public function display_crime_statistics() {
        // Get statistics data
        $stats = $this->get_crime_statistics();
        
        // Include the statistics view
        include plugin_dir_path( __FILE__ ) . 'views/crime-statistics.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Process form submission if needed
        if ( isset( $_POST['sandbaai_crime_settings_nonce'] ) && 
             wp_verify_nonce( $_POST['sandbaai_crime_settings_nonce'], 'sandbaai_crime_settings' ) ) {
            $this->save_settings();
        }
        
        // Get current settings
        $settings = get_option( 'sandbaai_crime_settings', array() );
        
        // Include the settings view
        include plugin_dir_path( __FILE__ ) . 'views/settings.php';
    }

    /**
     * Save plugin settings.
     *
     * @since    1.0.0
     */
    private function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = array();

        // WhatsApp Notification Settings
        $settings['whatsapp_enabled'] = isset( $_POST['whatsapp_enabled'] ) ? 1 : 0;
        $settings['whatsapp_api_key'] = sanitize_text_field( $_POST['whatsapp_api_key'] );
        $settings['whatsapp_recipients'] = sanitize_textarea_field( $_POST['whatsapp_recipients'] );
        
        // Map Settings
        $settings['map_api_key'] = sanitize_text_field( $_POST['map_api_key'] );
        $settings['default_lat'] = floatval( $_POST['default_lat'] );
        $settings['default_lng'] = floatval( $_POST['default_lng'] );
        $settings['default_zoom'] = intval( $_POST['default_zoom'] );
        
        // Email Notification Settings
        $settings['email_notifications'] = isset( $_POST['email_notifications'] ) ? 1 : 0;
        $settings['notification_email'] = sanitize_email( $_POST['notification_email'] );
        
        // Form Settings
        $settings['require_approval'] = isset( $_POST['require_approval'] ) ? 1 : 0;
        $settings['allow_anonymous'] = isset( $_POST['allow_anonymous'] ) ? 1 : 0;
        
        // Update settings
        update_option( 'sandbaai_crime_settings', $settings );
        
        // Add admin notice
        add_settings_error(
            'sandbaai_crime_settings',
            'settings_updated',
            __( 'Settings saved successfully.', 'sandbaai-crime' ),
            'updated'
        );
    }

    /**
     * Register meta boxes for crime reports.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crime_report_details',
            __( 'Crime Report Details', 'sandbaai-crime' ),
            array( $this, 'render_crime_report_metabox' ),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_location',
            __( 'Crime Location', 'sandbaai-crime' ),
            array( $this, 'render_location_metabox' ),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'security_group_details',
            __( 'Security Group Details', 'sandbaai-crime' ),
            array( $this, 'render_security_group_metabox' ),
            'security_group',
            'normal',
            'high'
        );
    }

    /**
     * Render the crime report meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_crime_report_metabox( $post ) {
        // Add nonce for security
        wp_nonce_field( 'sandbaai_crime_report_meta', 'sandbaai_crime_report_nonce' );
        
        // Get the saved meta values
        $crime_type = get_post_meta( $post->ID, '_crime_type', true );
        $crime_date = get_post_meta( $post->ID, '_crime_date', true );
        $crime_time = get_post_meta( $post->ID, '_crime_time', true );
        $crime_status = get_post_meta( $post->ID, '_crime_status', true );
        $security_groups = get_post_meta( $post->ID, '_security_groups', true );
        
        // Get crime categories for dropdown
        $crime_categories = $this->get_crime_categories();
        
        // Get security groups for multiselect
        $all_security_groups = $this->get_security_groups();
        
        // Include the metabox view
        include plugin_dir_path( __FILE__ ) . 'views/metaboxes/crime-report-details.php';
    }

    /**
     * Render the location meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_location_metabox( $post ) {
        // Get the saved meta values
        $address = get_post_meta( $post->ID, '_crime_address', true );
        $zone = get_post_meta( $post->ID, '_crime_zone', true );
        $latitude = get_post_meta( $post->ID, '_crime_latitude', true );
        $longitude = get_post_meta( $post->ID, '_crime_longitude', true );
        
        // Get settings for map defaults
        $settings = get_option( 'sandbaai_crime_settings', array() );
        $default_lat = isset( $settings['default_lat'] ) ? $settings['default_lat'] : -34.397;
        $default_lng = isset( $settings['default_lng'] ) ? $settings['default_lng'] : 19.153;
        $default_zoom = isset( $settings['default_zoom'] ) ? $settings['default_zoom'] : 14;
        
        // Include the metabox view
        include plugin_dir_path( __FILE__ ) . 'views/metaboxes/crime-report-location.php';
    }

    /**
     * Render the security group meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_security_group_metabox( $post ) {
        // Add nonce for security
        wp_nonce_field( 'sandbaai_security_group_meta', 'sandbaai_security_group_nonce' );
        
        // Get the saved meta values
        $contact_name = get_post_meta( $post->ID, '_contact_name', true );
        $contact_phone = get_post_meta( $post->ID, '_contact_phone', true );
        $contact_email = get_post_meta( $post->ID, '_contact_email', true );
        $website = get_post_meta( $post->ID, '_website', true );
        $address = get_post_meta( $post->ID, '_address', true );
        
        // Include the metabox view
        include plugin_dir_path( __FILE__ ) . 'views/metaboxes/security-group-details.php';
    }

    /**
     * Save crime report meta data.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     */
    public function save_crime_report_meta( $post_id, $post ) {
        // Check if our nonce is set
        if ( ! isset( $_POST['sandbaai_crime_report_nonce'] ) ) {
            return;
        }

        // Verify the nonce
        if ( ! wp_verify_nonce( $_POST['sandbaai_crime_report_nonce'], 'sandbaai_crime_report_meta' ) ) {
            return;
        }

        // If this is an autosave, we don't want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions
        if ( 'crime_report' !== $post->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save crime report meta data
        if ( isset( $_POST['crime_type'] ) ) {
            update_post_meta( $post_id, '_crime_type', sanitize_text_field( $_POST['crime_type'] ) );
        }
        
        if ( isset( $_POST['crime_date'] ) ) {
            update_post_meta( $post_id, '_crime_date', sanitize_text_field( $_POST['crime_date'] ) );
        }
        
        if ( isset( $_POST['crime_time'] ) ) {
            update_post_meta( $post_id, '_crime_time', sanitize_text_field( $_POST['crime_time'] ) );
        }
        
        if ( isset( $_POST['crime_status'] ) ) {
            update_post_meta( $post_id, '_crime_status', sanitize_text_field( $_POST['crime_status'] ) );
        }
        
        if ( isset( $_POST['security_groups'] ) ) {
            update_post_meta( $post_id, '_security_groups', array_map( 'absint', $_POST['security_groups'] ) );
        } else {
            update_post_meta( $post_id, '_security_groups', array() );
        }
        
        // Save location data
        if ( isset( $_POST['crime_address'] ) ) {
            update_post_meta( $post_id, '_crime_address', sanitize_text_field( $_POST['crime_address'] ) );
        }
        
        if ( isset( $_POST['crime_zone'] ) ) {
            update_post_meta( $post_id, '_crime_zone', sanitize_text_field( $_POST['crime_zone'] ) );
        }
        
        if ( isset( $_POST['crime_latitude'] ) ) {
            update_post_meta( $post_id, '_crime_latitude', floatval( $_POST['crime_latitude'] ) );
        }
        
        if ( isset( $_POST['crime_longitude'] ) ) {
            update_post_meta( $post_id, '_crime_longitude', floatval( $_POST['crime_longitude'] ) );
        }
        
        // Trigger notifications if it's a new report or status changed
        $old_status = get_post_meta( $post_id, '_crime_status', true );
        $new_status = isset( $_POST['crime_status'] ) ? sanitize_text_field( $_POST['crime_status'] ) : '';
        
        if ( $post->post_status === 'publish' && ( $old_status !== $new_status || get_post_meta( $post_id, '_notified', true ) !== 'yes' ) ) {
            $this->send_notifications( $post_id );
            update_post_meta( $post_id, '_notified', 'yes' );
        }
    }

    /**
     * Save security group meta data.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     */
    public function save_security_group_meta( $post_id, $post ) {
        // Check if our nonce is set
        if ( ! isset( $_POST['sandbaai_security_group_nonce'] ) ) {
            return;
        }

        // Verify the nonce
        if ( ! wp_verify_nonce( $_POST['sandbaai_security_group_nonce'], 'sandbaai_security_group_meta' ) ) {
            return;
        }

        // If this is an autosave, we don't want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions
        if ( 'security_group' !== $post->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save security group meta data
        if ( isset( $_POST['contact_name'] ) ) {
            update_post_meta( $post_id, '_contact_name', sanitize_text_field( $_POST['contact_name'] ) );
        }
        
        if ( isset( $_POST['contact_phone'] ) ) {
            update_post_meta( $post_id, '_contact_phone', sanitize_text_field( $_POST['contact_phone'] ) );
        }
        
        if ( isset( $_POST['contact_email'] ) ) {
            update_post_meta( $post_id, '_contact_email', sanitize_email( $_POST['contact_email'] ) );
        }
        
        if ( isset( $_POST['website'] ) ) {
            update_post_meta( $post_id, '_website', esc_url_raw( $_POST['website'] ) );
        }
        
        if ( isset( $_POST['address'] ) ) {
            update_post_meta( $post_id, '_address', sanitize_textarea_field( $_POST['address'] ) );
        }
    }

    /**
     * Send notifications for new crime reports.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    private function send_notifications( $post_id ) {
        $settings = get_option( 'sandbaai_crime_settings', array() );
        
        // Send email notification
        if ( isset( $settings['email_notifications'] ) && $settings['email_notifications'] ) {
            $this->send_email_notification( $post_id );
        }
        
        // Send WhatsApp notification
        if ( isset( $settings['whatsapp_enabled'] ) && $settings['whatsapp_enabled'] ) {
            $this->send_whatsapp_notification( $post_id );
        }
    }

    /**
     * Send email notification for crime report.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    private function send_email_notification( $post_id ) {
        $settings = get_option( 'sandbaai_crime_settings', array() );
        $email = isset( $settings['notification_email'] ) ? $settings['notification_email'] : get_option( 'admin_email' );
        
        $post = get_post( $post_id );
        $crime_type = get_post_meta( $post_id, '_crime_type', true );
        $crime_date = get_post_meta( $post_id, '_crime_date', true );
        $crime_address = get_post_meta( $post_id, '_crime_address', true );
        
        $subject = sprintf( __( 'New Crime Report: %s', 'sandbaai-crime' ), $post->post_title );
        
        $message = sprintf(
            __( "A new crime report has been submitted:\n\nTitle: %s\nType: %s\nDate: %s\nLocation: %s\n\nView full report: %s", 'sandbaai-crime' ),
            $post->post_title,
            $crime_type,
            $crime_date,
            $crime_address,
            admin_url( 'post.php?post=' . $post_id . '&action=edit' )
        );
        
        wp_mail( $email, $subject, $message );
    }

    /**
     * Send WhatsApp notification for crime report.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    private function send_whatsapp_notification( $post_id ) {
        // Use the WhatsApp notification class
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/whatsapp-notifications.php';
        
        $whatsapp = new Sandbaai_Crime_WhatsApp_Notifications();
        $whatsapp->send_crime_report_notification( $post_id );
    }

    /**
     * Get crime categories.
     *
     * @since    1.0.0
     * @return   array    Crime categories.
     */
    private function get_crime_categories() {
        // Get custom crime categories if available
        $custom_categories = get_option( 'sandbaai_crime_categories', array() );
        
        if ( ! empty( $custom_categories ) ) {
            return $custom_categories;
        }
        
        // Return default categories
        return array(
            'burglary' => __( 'Burglary', 'sandbaai-crime' ),
            'theft' => __( 'Theft', 'sandbaai-crime' ),
            'robbery' => __( 'Robbery', 'sandbaai-crime' ),
            'assault' => __( 'Assault', 'sandbaai-crime' ),
            'vandalism' => __( 'Vandalism', 'sandbaai-crime' ),
            'suspicious' => __( 'Suspicious Activity', 'sandbaai-crime' ),
            'other' => __( 'Other', 'sandbaai-crime' ),
        );
    }

    /**
     * Get security groups.
     *
     * @since    1.0.0
     * @return   array    Security groups.
     */
    private function get_security_groups() {
        $security_groups = array();
        
        $args = array(
            'post_type' => 'security_group',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $security_groups[ get_the_ID() ] = get_the_title();
            }
            wp_reset_postdata();
        }
        
        return $security_groups;
    }

    /**
     * Get crime statistics.
     *
     * @since    1.0.0
     * @param    array    $filters    Optional. Filters for statistics.
     * @return   array    Crime statistics.
     */
    private function get_crime_statistics( $filters = array() ) {
        $stats = array();
        
        // Parse filters
        $defaults = array(
            'start_date' => date( 'Y-m-d', strtotime( '-1 year' ) ),
            'end_date' => date( 'Y-m-d' ),
            'crime_type' => '',
            'zone' => '',
        );
        
        $filters = wp_parse_args( $filters, $defaults );
        
        // Build meta query based on filters
        $meta_query = array( 'relation' => 'AND' );
        
        // Date filter
        $meta_query[] = array(
            'key' => '_crime_date',
            'value' => array( $filters['start_date'], $filters['end_date'] ),
            'compare' => 'BETWEEN',
            'type' => 'DATE',
        );
        
        // Crime type filter
        if ( ! empty( $filters['crime_type'] ) ) {
            $meta_query[] = array(
                'key' => '_crime_type',
                'value' => $filters['crime_type'],
            );
        }
        
        // Zone filter
        if ( ! empty( $filters['zone'] ) ) {
            $meta_query[] = array(
                'key' => '_crime_zone',
                'value' => $filters['zone'],
            );
        }
        
        // Get total crime reports
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
        );
        
        $query = new WP_Query( $args );
        $stats['total'] = $query->found_posts;
        
        // Get crime reports by category
        $categories = $this->get_crime_categories();
        $stats['by_category'] = array();
        
        foreach ( $categories as $slug => $name ) {
            $category_meta_query = $meta_query;
            $category_meta_query[] = array(
                'key' => '_crime_type',
                'value' => $slug,
            );
            
            $args = array(
                'post_type' => 'crime_report',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => $category_meta_query,
            );
            
            $query = new WP_Query( $args );
            $stats['by_category'][ $slug ] = array(
                'name' => $name,
                'count' => $query->found_posts,
            );
        }
        
        // Get crime reports by month
        $stats['by_month'] = array();
        
        $start = new DateTime( $filters['start_date'] );
        $end = new DateTime( $filters['end_date'] );
        $interval = new DateInterval( 'P1M' );
        $period = new DatePeriod( $start, $interval, $end );
        
        foreach ( $period as $date ) {
            $month = $date->format( 'Y-m' );
            $month_meta_query = $meta_query;
            
            // Override date filter for this specific month
            foreach ( $month_meta_query as $key => $query_item ) {
                if ( isset( $query_item['key'] ) && $query_item['key'] === '_crime_date' ) {
                    $month_meta_query[$key] = array(
                        'key' => '_crime_date',
                        'value' => array( $month . '-01', $month . '-31' ),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE',
                    );
                    break;
                }
            }
            
            $args = array(
                'post_type' => 'crime_report',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => $month_meta_query,
            );
            
            $query = new WP_Query( $args );
            $stats['by_month'][ $month ] = $query->found_posts;
        }
        
        // Get crime reports by day of week
        $stats['by_day'] = array(
            'Sunday' => 0,
            'Monday' => 0,
            'Tuesday' => 0,
            'Wednesday' => 0,
            'Thursday' => 0,
            'Friday' => 0,
            'Saturday' => 0,
        );
        
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();
                $date_str = get_post_meta( $id, '_crime_date', true );
                
                if ( $date_str ) {
                    $date = new DateTime( $date_str );
                    $day_of_week = $date->format( 'l' );
                    $stats['by_day'][ $day_of_week ]++;
                }
            }
            wp_reset_postdata();
        }
        
        // Get crime reports for map
        $map_meta_query = $meta_query;
        $map_meta_query[] = array(
            'relation' => 'AND',
            array(
                'key' => '_crime_latitude',
                'compare' => 'EXISTS',
            ),
            array(
                'key' => '_crime_longitude',
                'compare' => 'EXISTS',
            ),
        );
        
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $map_meta_query,
        );
        
        $query = new WP_Query( $args );
        $stats['map_data'] = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();
                $lat = get_post_meta( $id, '_crime_latitude', true );
                $lng = get_post_meta( $id, '_crime_longitude', true );
                $type = get_post_meta( $id, '_crime_type', true );
                $date = get_post_meta( $id, '_crime_date', true );
                $time = get_post_meta( $id, '_crime_time', true );
                $status = get_post_meta( $id, '_crime_status', true );
                
                if ( $lat && $lng ) {
                    $stats['map_data'][] = array(
                        'id' => $id,
                        'title' => get_the_title(),
                        'type' => $type,
                        'date' => $date,
                        'time' => $time,
                        'status' => $status,
                        'lat' => $lat,
                        'lng' => $lng,
                        'url' => get_permalink(),
                    );
                }
            }
            wp_reset_postdata();
        }
        
        // Get crime zones with count
        $stats['zones'] = array();
        
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
            'meta_key' => '_crime_zone',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $zone = get_post_meta( get_the_ID(), '_crime_zone', true );
                
                if ( $zone ) {
                    if ( ! isset( $stats['zones'][ $zone ] ) ) {
                        $stats['zones'][ $zone ] = 0;
                    }
                    
                    $stats['zones'][ $zone ]++;
                }
            }
            wp_reset_postdata();
        }
        
        return $stats;
    }

    /**
     * Register AJAX handlers.
     *
     * @since    1.0.0
     */
    public function register_ajax_handlers() {
        add_action( 'wp_ajax_sandbaai_crime_submit_report', array( $this, 'ajax_submit_report' ) );
        add_action( 'wp_ajax_nopriv_sandbaai_crime_submit_report', array( $this, 'ajax_submit_report' ) );
        
        add_action( 'wp_ajax_sandbaai_crime_get_statistics', array( $this, 'ajax_get_statistics' ) );
        add_action( 'wp_ajax_nopriv_sandbaai_crime_get_statistics', array( $this, 'ajax_get_statistics' ) );
        
        add_action( 'wp_ajax_sandbaai_crime_export_data', array( $this, 'ajax_export_data' ) );
        
        add_action( 'wp_ajax_sandbaai_crime_manage_category', array( $this, 'ajax_manage_category' ) );
        
        add_action( 'wp_ajax_sandbaai_crime_get_zones', array( $this, 'ajax_get_zones' ) );
        add_action( 'wp_ajax_nopriv_sandbaai_crime_get_zones', array( $this, 'ajax_get_zones' ) );
    }
    
    /**
     * AJAX handler for getting crime statistics.
     *
     * @since    1.0.0
     */
    public function ajax_get_statistics() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sandbaai_crime_stats_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'sandbaai-crime' ) ) );
        }
        
        // Get filters
        $filters = array();
        
        if ( isset( $_POST['start_date'] ) ) {
            $filters['start_date'] = sanitize_text_field( $_POST['start_date'] );
        }
        
        if ( isset( $_POST['end_date'] ) ) {
            $filters['end_date'] = sanitize_text_field( $_POST['end_date'] );
        }
        
        if ( isset( $_POST['crime_type'] ) ) {
            $filters['crime_type'] = sanitize_text_field( $_POST['crime_type'] );
        }
        
        if ( isset( $_POST['zone'] ) ) {
            $filters['zone'] = sanitize_text_field( $_POST['zone'] );
        }
        
        // Get statistics
        $stats = $this->get_crime_statistics( $filters );
        
        // Return the statistics
        wp_send_json_success( $stats );
    }
    
    /**
     * AJAX handler for exporting crime data.
     *
     * @since    1.0.0
     */
    public function ajax_export_data() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sandbaai_crime_export_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'sandbaai-crime' ) ) );
        }
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'sandbaai-crime' ) ) );
        }
        
        // Get export type
        $export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( $_POST['export_type'] ) : 'csv';
        
        // Get filters
        $filters = array();
        
        if ( isset( $_POST['start_date'] ) ) {
            $filters['start_date'] = sanitize_text_field( $_POST['start_date'] );
        }
        
        if ( isset( $_POST['end_date'] ) ) {
            $filters['end_date'] = sanitize_text_field( $_POST['end_date'] );
        }
        
        if ( isset( $_POST['crime_type'] ) ) {
            $filters['crime_type'] = sanitize_text_field( $_POST['crime_type'] );
        }
        
        if ( isset( $_POST['zone'] ) ) {
            $filters['zone'] = sanitize_text_field( $_POST['zone'] );
        }
        
        // Build meta query based on filters
        $meta_query = array( 'relation' => 'AND' );
        
        // Date filter
        if ( isset( $filters['start_date'] ) && isset( $filters['end_date'] ) ) {
            $meta_query[] = array(
                'key' => '_crime_date',
                'value' => array( $filters['start_date'], $filters['end_date'] ),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            );
        }
        
        // Crime type filter
        if ( ! empty( $filters['crime_type'] ) ) {
            $meta_query[] = array(
                'key' => '_crime_type',
                'value' => $filters['crime_type'],
            );
        }
        
        // Zone filter
        if ( ! empty( $filters['zone'] ) ) {
            $meta_query[] = array(
                'key' => '_crime_zone',
                'value' => $filters['zone'],
            );
        }
        
        // Get crime reports
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
        );
        
        $query = new WP_Query( $args );
        
        // Prepare data
        $data = array();
        
        // Add header row
        $data[] = array(
            'ID',
            __( 'Title', 'sandbaai-crime' ),
            __( 'Type', 'sandbaai-crime' ),
            __( 'Date', 'sandbaai-crime' ),
            __( 'Time', 'sandbaai-crime' ),
            __( 'Status', 'sandbaai-crime' ),
            __( 'Address', 'sandbaai-crime' ),
            __( 'Zone', 'sandbaai-crime' ),
            __( 'Latitude', 'sandbaai-crime' ),
            __( 'Longitude', 'sandbaai-crime' ),
            __( 'Description', 'sandbaai-crime' ),
            __( 'Security Groups', 'sandbaai-crime' ),
            __( 'Author', 'sandbaai-crime' ),
            __( 'Created', 'sandbaai-crime' ),
        );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get security groups
                $security_group_ids = get_post_meta( $post_id, '_security_groups', true );
                $security_groups = '';
                
                if ( is_array( $security_group_ids ) && ! empty( $security_group_ids ) ) {
                    $security_group_names = array();
                    
                    foreach ( $security_group_ids as $group_id ) {
                        $security_group_names[] = get_the_title( $group_id );
                    }
                    
                    $security_groups = implode( ', ', $security_group_names );
                }
                
                // Add data row
                $data[] = array(
                    $post_id,
                    get_the_title(),
                    get_post_meta( $post_id, '_crime_type', true ),
                    get_post_meta( $post_id, '_crime_date', true ),
                    get_post_meta( $post_id, '_crime_time', true ),
                    get_post_meta( $post_id, '_crime_status', true ),
                    get_post_meta( $post_id, '_crime_address', true ),
                    get_post_meta( $post_id, '_crime_zone', true ),
                    get_post_meta( $post_id, '_crime_latitude', true ),
                    get_post_meta( $post_id, '_crime_longitude', true ),
                    wp_strip_all_tags( get_the_content() ),
                    $security_groups,
                    get_the_author(),
                    get_the_date(),
                );
            }
            wp_reset_postdata();
        }
        
        // Return data based on export type
        if ( $export_type === 'csv' ) {
            $csv_data = '';
            
            foreach ( $data as $row ) {
                $csv_data .= '"' . implode( '","', $row ) . '"' . "\n";
            }
            
            wp_send_json_success( array(
                'data' => $csv_data,
                'filename' => 'crime-reports-' . date( 'Y-m-d' ) . '.csv',
            ) );
        } else {
            wp_send_json_success( array(
                'data

    /**
     * AJAX handler for submitting crime reports.
     *
     * @since    1.0.0
     */
    public function ajax_submit_report() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sandbaai_crime_report_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'sandbaai-crime' ) ) );
        }
        
        // Check if user is logged in or anonymous reports are allowed
        $settings = get_option( 'sandbaai_crime_settings', array() );
        $allow_anonymous = isset( $settings['allow_anonymous'] ) && $settings['allow_anonymous'];
        
        if ( ! is_user_logged_in() && ! $allow_anonymous ) {
            wp_send_json_error( array( 'message' => __( 'Login required to submit reports', 'sandbaai-crime' ) ) );
        }
        
        // Validate required fields
        $required_fields = array( 'title', 'crime_type', 'crime_date', 'crime_time', 'crime_address' );
        foreach ( $required_fields as $field ) {
            if ( ! isset( $_POST[$field] ) || empty( $_POST[$field] ) ) {
                wp_send_json_error( array( 
                    'message' => __( 'Please fill in all required fields', 'sandbaai-crime' ),
                    'field' => $field
                ) );
            }
        }
        
        // Create post data
        $post_status = 'publish';
        
        // If require approval setting is enabled and user is not admin
        if ( isset( $settings['require_approval'] ) && $settings['require_approval'] && ! current_user_can( 'manage_options' ) ) {
            $post_status = 'pending';
        }
        
        $post_data = array(
            'post_title'    => sanitize_text_field( $_POST['title'] ),
            'post_content'  => isset( $_POST['description'] ) ? wp_kses_post( $_POST['description'] ) : '',
            'post_status'   => $post_status,
            'post_type'     => 'crime_report',
            'post_author'   => is_user_logged_in() ? get_current_user_id() : 1, // Use admin if anonymous
        );
        
        // Insert the post
        $post_id = wp_insert_post( $post_data );
        
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
        }
        
        // Save meta data
        update_post_meta( $post_id, '_crime_type', sanitize_text_field( $_POST['crime_type'] ) );
        update_post_meta( $post_id, '_crime_date', sanitize_text_field( $_POST['crime_date'] ) );
        update_post_meta( $post_id, '_crime_time', sanitize_text_field( $_POST['crime_time'] ) );
        update_post_meta( $post_id, '_crime_address', sanitize_text_field( $_POST['crime_address'] ) );
        
        // Save optional fields
        if ( isset( $_POST['crime_zone'] ) ) {
            update_post_meta( $post_id, '_crime_zone', sanitize_text_field( $_POST['crime_zone'] ) );
        }
        
        if ( isset( $_POST['crime_latitude'] ) && isset( $_POST['crime_longitude'] ) ) {
            update_post_meta( $post_id, '_crime_latitude', floatval( $_POST['crime_latitude'] ) );
            update_post_meta( $post_id, '_crime_longitude', floatval( $_POST['crime_longitude'] ) );
        }
        
        if ( isset( $_POST['security_groups'] ) && is_array( $_POST['security_groups'] ) ) {
            update_post_meta( $post_id, '_security_groups', array_map( 'absint', $_POST['security_groups'] ) );
        }
        
        if ( isset( $_POST['crime_status'] ) ) {
            update_post_meta( $post_id, '_crime_status', sanitize_text_field( $_POST['crime_status'] ) );
        } else {
            update_post_meta( $post_id, '_crime_status', 'reported' );
        }
        
        // Handle image upload
        if ( isset( $_FILES['crime_photo'] ) && ! empty( $_FILES['crime_photo']['name'] ) ) {
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
            
            $upload_overrides = array( 'test_form' => false );
            $uploaded_file = wp_handle_upload( $_FILES['crime_photo'], $upload_overrides );
            
            if ( ! isset( $uploaded_file['error'] ) && isset( $uploaded_file['file'] ) ) {
                // Create attachment
                $attachment = array(
                    'post_mime_type' => $uploaded_file['type'],
                    'post_title'     => sanitize_file_name( basename( $_FILES['crime_photo']['name'] ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                
                $attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'], $post_id );
                
                if ( ! is_wp_error( $attach_id ) ) {
                    // Generate attachment metadata
                    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
                        require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    }
                    
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    
                    // Set as featured image
                    set_post_thumbnail( $post_id, $attach_id );
                }
            }
        }
        
        // Send notifications if post is published
        if ( $post_status === 'publish' ) {
            $this->send_notifications( $post_id );
            update_post_meta( $post_id, '_notified', 'yes' );
        }
        
        // Return success
        wp_send_json_success( array(
            'message' => __( 'Crime report submitted successfully', 'sandbaai-crime' ),
            'post_id' => $post_id,
            'status'  => $post_status === 'publish' ? 'published' : 'pending',
            'redirect_url' => get_permalink( $post_id )
        ) );
    }