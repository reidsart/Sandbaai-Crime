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
 * Defines the plugin name, version, and two hooks for
 * enqueuing the admin-specific stylesheet and JavaScript.
 * Also registers admin menus and handles admin pages.
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
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/sandbaai-crime-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/sandbaai-crime-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize the script with data for the map
        wp_localize_script(
            $this->plugin_name,
            'sandbaai_crime_admin_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sandbaai_crime_admin_nonce'),
            )
        );
    }

    /**
     * Register the admin menu and submenus.
     *
     * @since    1.0.0
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
            25
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

        // Crime Reports submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Crime Reports', 'sandbaai-crime'),
            __('Crime Reports', 'sandbaai-crime'),
            'manage_options',
            'edit.php?post_type=crime_report',
            null
        );

        // Add Crime Report submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Add Crime Report', 'sandbaai-crime'),
            __('Add Crime Report', 'sandbaai-crime'),
            'manage_options',
            'post-new.php?post_type=crime_report',
            null
        );

        // Security Groups submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Security Groups', 'sandbaai-crime'),
            __('Security Groups', 'sandbaai-crime'),
            'manage_options',
            'edit.php?post_type=security_group',
            null
        );

        // Add Security Group submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Add Security Group', 'sandbaai-crime'),
            __('Add Security Group', 'sandbaai-crime'),
            'manage_options',
            'post-new.php?post_type=security_group',
            null
        );

        // Crime Categories submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Crime Categories', 'sandbaai-crime'),
            __('Crime Categories', 'sandbaai-crime'),
            'manage_options',
            'edit-tags.php?taxonomy=crime_category&post_type=crime_report',
            null
        );

        // Statistics Page
        add_submenu_page(
            'sandbaai-crime',
            __('Crime Statistics', 'sandbaai-crime'),
            __('Crime Statistics', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime-statistics',
            array($this, 'display_statistics_page')
        );

        // WhatsApp Notifications
        add_submenu_page(
            'sandbaai-crime',
            __('WhatsApp Notifications', 'sandbaai-crime'),
            __('WhatsApp Notifications', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime-whatsapp',
            array($this, 'display_whatsapp_page')
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
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        require_once plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Display the statistics page.
     *
     * @since    1.0.0
     */
    public function display_statistics_page() {
        require_once plugin_dir_path(__FILE__) . 'views/statistics.php';
    }

    /**
     * Display the WhatsApp notifications page.
     *
     * @since    1.0.0
     */
    public function display_whatsapp_page() {
        require_once plugin_dir_path(__FILE__) . 'views/whatsapp.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'views/settings.php';
    }

    /**
     * Register settings fields for the plugin.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register a settings section
        add_settings_section(
            'sandbaai_crime_general_settings',
            __('General Settings', 'sandbaai-crime'),
            array($this, 'settings_section_callback'),
            'sandbaai-crime-settings'
        );

        // Register Map API Key Field
        register_setting(
            'sandbaai_crime_settings',
            'sandbaai_crime_map_api_key'
        );
        add_settings_field(
            'sandbaai_crime_map_api_key',
            __('Map API Key', 'sandbaai-crime'),
            array($this, 'map_api_key_callback'),
            'sandbaai-crime-settings',
            'sandbaai_crime_general_settings'
        );

        // Register WhatsApp Integration Settings
        add_settings_section(
            'sandbaai_crime_whatsapp_settings',
            __('WhatsApp Notification Settings', 'sandbaai-crime'),
            array($this, 'whatsapp_settings_section_callback'),
            'sandbaai-crime-settings'
        );

        // WhatsApp API Key
        register_setting(
            'sandbaai_crime_settings',
            'sandbaai_crime_whatsapp_api_key'
        );
        add_settings_field(
            'sandbaai_crime_whatsapp_api_key',
            __('WhatsApp API Key', 'sandbaai-crime'),
            array($this, 'whatsapp_api_key_callback'),
            'sandbaai-crime-settings',
            'sandbaai_crime_whatsapp_settings'
        );

        // WhatsApp Phone Number
        register_setting(
            'sandbaai_crime_settings',
            'sandbaai_crime_whatsapp_phone'
        );
        add_settings_field(
            'sandbaai_crime_whatsapp_phone',
            __('WhatsApp Phone Number', 'sandbaai-crime'),
            array($this, 'whatsapp_phone_callback'),
            'sandbaai-crime-settings',
            'sandbaai_crime_whatsapp_settings'
        );

        // Enable WhatsApp Notifications
        register_setting(
            'sandbaai_crime_settings',
            'sandbaai_crime_enable_whatsapp'
        );
        add_settings_field(
            'sandbaai_crime_enable_whatsapp',
            __('Enable WhatsApp Notifications', 'sandbaai-crime'),
            array($this, 'enable_whatsapp_callback'),
            'sandbaai-crime-settings',
            'sandbaai_crime_whatsapp_settings'
        );
    }

    /**
     * Callback for the settings section.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure general settings for the Sandbaai Crime plugin.', 'sandbaai-crime') . '</p>';
    }

    /**
     * Callback for WhatsApp settings section.
     *
     * @since    1.0.0
     */
    public function whatsapp_settings_section_callback() {
        echo '<p>' . __('Configure WhatsApp notification settings for crime reports.', 'sandbaai-crime') . '</p>';
    }

    /**
     * Callback for the map API key field.
     *
     * @since    1.0.0
     */
    public function map_api_key_callback() {
        $api_key = get_option('sandbaai_crime_map_api_key');
        echo '<input type="text" name="sandbaai_crime_map_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your Google Maps API key for map functionality.', 'sandbaai-crime') . '</p>';
    }

    /**
     * Callback for the WhatsApp API key field.
     *
     * @since    1.0.0
     */
    public function whatsapp_api_key_callback() {
        $api_key = get_option('sandbaai_crime_whatsapp_api_key');
        echo '<input type="text" name="sandbaai_crime_whatsapp_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your WhatsApp Business API key.', 'sandbaai-crime') . '</p>';
    }

    /**
     * Callback for the WhatsApp phone number field.
     *
     * @since    1.0.0
     */
    public function whatsapp_phone_callback() {
        $phone = get_option('sandbaai_crime_whatsapp_phone');
        echo '<input type="text" name="sandbaai_crime_whatsapp_phone" value="' . esc_attr($phone) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter the WhatsApp phone number to send notifications from.', 'sandbaai-crime') . '</p>';
    }

    /**
     * Callback for the enable WhatsApp notifications field.
     *
     * @since    1.0.0
     */
    public function enable_whatsapp_callback() {
        $enabled = get_option('sandbaai_crime_enable_whatsapp');
        echo '<input type="checkbox" name="sandbaai_crime_enable_whatsapp" value="1" ' . checked(1, $enabled, false) . '>';
        echo '<p class="description">' . __('Enable automatic WhatsApp notifications for new crime reports.', 'sandbaai-crime') . '</p>';
    }

    /**
     * Register custom meta boxes for crime reports.
     *
     * @since    1.0.0
     */
    public function register_meta_boxes() {
        // Location meta box
        add_meta_box(
            'sandbaai_crime_location',
            __('Crime Location', 'sandbaai-crime'),
            array($this, 'location_meta_box_callback'),
            'crime_report',
            'normal',
            'high'
        );

        // Date and Time meta box
        add_meta_box(
            'sandbaai_crime_datetime',
            __('Date and Time', 'sandbaai-crime'),
            array($this, 'datetime_meta_box_callback'),
            'crime_report',
            'normal',
            'high'
        );

        // Security Groups Involved meta box
        add_meta_box(
            'sandbaai_crime_security_groups',
            __('Security Groups Involved', 'sandbaai-crime'),
            array($this, 'security_groups_meta_box_callback'),
            'crime_report',
            'normal',
            'high'
        );

        // Result Status meta box
        add_meta_box(
            'sandbaai_crime_result',
            __('Result Status', 'sandbaai-crime'),
            array($this, 'result_meta_box_callback'),
            'crime_report',
            'normal',
            'high'
        );

        // Photos meta box
        add_meta_box(
            'sandbaai_crime_photos',
            __('Photos', 'sandbaai-crime'),
            array($this, 'photos_meta_box_callback'),
            'crime_report',
            'normal',
            'high'
        );
    }

    /**
     * Callback for the location meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function location_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('sandbaai_crime_location_nonce', 'sandbaai_crime_location_nonce');

        // Retrieve existing values
        $address = get_post_meta($post->ID, '_sandbaai_crime_address', true);
        $zone = get_post_meta($post->ID, '_sandbaai_crime_zone', true);
        $latitude = get_post_meta($post->ID, '_sandbaai_crime_latitude', true);
        $longitude = get_post_meta($post->ID, '_sandbaai_crime_longitude', true);

        // Output fields
        ?>
        <div class="sandbaai-crime-meta-box">
            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_address"><?php _e('Address', 'sandbaai-crime'); ?></label>
                <input type="text" id="sandbaai_crime_address" name="sandbaai_crime_address" value="<?php echo esc_attr($address); ?>" class="widefat">
            </div>

            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_zone"><?php _e('Zone', 'sandbaai-crime'); ?></label>
                <select id="sandbaai_crime_zone" name="sandbaai_crime_zone" class="widefat">
                    <option value=""><?php _e('Select Zone', 'sandbaai-crime'); ?></option>
                    <option value="north_east" <?php selected($zone, 'north_east'); ?>><?php _e('North East', 'sandbaai-crime'); ?></option>
                    <option value="north_west" <?php selected($zone, 'north_west'); ?>><?php _e('North West', 'sandbaai-crime'); ?></option>
                    <option value="south_east" <?php selected($zone, 'south_east'); ?>><?php _e('South East', 'sandbaai-crime'); ?></option>
                    <option value="south_west" <?php selected($zone, 'south_west'); ?>><?php _e('South West', 'sandbaai-crime'); ?></option>
                </select>
            </div>

            <div class="sandbaai-crime-field sandbaai-crime-map-container">
                <div id="sandbaai_crime_map" style="width: 100%; height: 300px;"></div>
            </div>

            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_latitude"><?php _e('Latitude', 'sandbaai-crime'); ?></label>
                <input type="text" id="sandbaai_crime_latitude" name="sandbaai_crime_latitude" value="<?php echo esc_attr($latitude); ?>" class="widefat">
            </div>

            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_longitude"><?php _e('Longitude', 'sandbaai-crime'); ?></label>
                <input type="text" id="sandbaai_crime_longitude" name="sandbaai_crime_longitude" value="<?php echo esc_attr($longitude); ?>" class="widefat">
            </div>
        </div>
        <?php
    }

    /**
     * Callback for the date and time meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function datetime_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('sandbaai_crime_datetime_nonce', 'sandbaai_crime_datetime_nonce');

        // Retrieve existing values
        $date = get_post_meta($post->ID, '_sandbaai_crime_date', true);
        $time = get_post_meta($post->ID, '_sandbaai_crime_time', true);

        // Set defaults if empty
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        if (empty($time)) {
            $time = date('H:i');
        }

        // Output fields
        ?>
        <div class="sandbaai-crime-meta-box">
            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_date"><?php _e('Date', 'sandbaai-crime'); ?></label>
                <input type="date" id="sandbaai_crime_date" name="sandbaai_crime_date" value="<?php echo esc_attr($date); ?>" class="widefat">
            </div>

            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_time"><?php _e('Time', 'sandbaai-crime'); ?></label>
                <input type="time" id="sandbaai_crime_time" name="sandbaai_crime_time" value="<?php echo esc_attr($time); ?>" class="widefat">
            </div>
        </div>
        <?php
    }

    /**
     * Callback for the security groups meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function security_groups_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('sandbaai_crime_security_groups_nonce', 'sandbaai_crime_security_groups_nonce');

        // Retrieve existing values
        $selected_groups = get_post_meta($post->ID, '_sandbaai_crime_security_groups', true);
        if (!is_array($selected_groups)) {
            $selected_groups = array();
        }

        // Get all security groups
        $security_groups = get_posts(array(
            'post_type' => 'security_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        // Output fields
        ?>
        <div class="sandbaai-crime-meta-box">
            <div class="sandbaai-crime-field">
                <?php if (empty($security_groups)) : ?>
                    <p><?php _e('No security groups found. Please create security groups first.', 'sandbaai-crime'); ?></p>
                <?php else : ?>
                    <ul class="sandbaai-crime-checklist">
                        <?php foreach ($security_groups as $group) : ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="sandbaai_crime_security_groups[]" value="<?php echo esc_attr($group->ID); ?>" <?php checked(in_array($group->ID, $selected_groups)); ?>>
                                    <?php echo esc_html($group->post_title); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Callback for the result meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function result_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('sandbaai_crime_result_nonce', 'sandbaai_crime_result_nonce');

        // Retrieve existing value
        $result = get_post_meta($post->ID, '_sandbaai_crime_result', true);

        // Output fields
        ?>
        <div class="sandbaai-crime-meta-box">
            <div class="sandbaai-crime-field">
                <label for="sandbaai_crime_result"><?php _e('Result Status', 'sandbaai-crime'); ?></label>
                <select id="sandbaai_crime_result" name="sandbaai_crime_result" class="widefat">
                    <option value=""><?php _e('Select Result', 'sandbaai-crime'); ?></option>
                    <option value="pending" <?php selected($result, 'pending'); ?>><?php _e('Pending', 'sandbaai-crime'); ?></option>
                    <option value="resolved" <?php selected($result, 'resolved'); ?>><?php _e('Resolved', 'sandbaai-crime'); ?></option>
                    <option value="unresolved" <?php selected($result, 'unresolved'); ?>><?php _e('Unresolved', 'sandbaai-crime'); ?></option>
                    <option value="false_alarm" <?php selected($result, 'false_alarm'); ?>><?php _e('False Alarm', 'sandbaai-crime'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * Callback for the photos meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function photos_meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('sandbaai_crime_photos_nonce', 'sandbaai_crime_photos_nonce');

        // Retrieve existing values
        $attachment_ids = get_post_meta($post->ID, '_sandbaai_crime_photos', true);
        if (!is_array($attachment_ids)) {
            $attachment_ids = array();
        }

        // Output fields
        ?>
        <div class="sandbaai-crime-meta-box">
            <div class="sandbaai-crime-field">
                <div class="sandbaai-crime-photos-container">
                    <?php if (!empty($attachment_ids)) : ?>
                        <ul class="sandbaai-crime-photos-list">
                            <?php foreach ($attachment_ids as $attachment_id) : ?>
                                <?php $image = wp_get_attachment_image($attachment_id, 'thumbnail'); ?>
                                <?php if ($image) : ?>
                                    <li class="sandbaai-crime-photo-item">
                                        <?php echo $image; ?>
                                        <a href="#" class="sandbaai-crime-remove-photo" data-attachment-id="<?php echo esc_attr($attachment_id); ?>"><?php _e('Remove', 'sandbaai-crime'); ?></a>
                                        <input type="hidden" name="sandbaai_crime_photos[]" value="<?php echo esc_attr($attachment_id); ?>">
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <button type="button" class="button sandbaai-crime-upload-photos"><?php _e('Upload Photos', 'sandbaai-crime'); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set for each meta box
        $nonces = array(
            'sandbaai_crime_location_nonce',
            'sandbaai_crime_datetime_nonce',
            'sandbaai_crime_security_groups_nonce',
            'sandbaai_crime_result_nonce',
            'sandbaai_crime_photos_nonce',
        );

        // Check if we're doing an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (isset($_POST['post_type']) && 'crime_report' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Check at least one nonce
        $nonce_verified = false;
        foreach ($nonces as $nonce) {
            if (isset($_POST[$nonce]) && wp_verify_nonce($_POST[$nonce], $nonce)) {
                $nonce_verified = true;
                break;
            }
        }

        if (!$nonce_verified) {
            return;
        }

        // Sanitize and save location data
        if (isset($_POST['sandbaai_crime_address'])) {
            update_post_meta($post_id, '_sandbaai_crime_address', sanitize_text_field($_POST['sandbaai_crime_address']));
        }
        if (isset($_POST['sandbaai_crime_zone'])) {
            update_post_meta($post_id, '_sandbaai_crime_zone', sanitize_text_field($_POST['sandbaai_crime_zone']));
        }
        if (isset($_POST['sandbaai_crime_latitude'])) {
            update_post_meta($post_id, '_sandbaai_crime_latitude', sanitize_text_field($_POST['sandbaai_crime_latitude']));
        }
        if (isset($_POST['sandbaai_crime_longitude'])) {
            update_post_meta($post_id, '_sandbaai_crime_longitude', sanitize_text_field($_POST['sandbaai_crime_longitude']));
        }

        // Sanitize and save date and time
        if (isset($_POST['sandbaai_crime_date'])) {
            update_post_meta($post_id, '_sandbaai_crime_date', sanitize_text_field($_POST['sandbaai_crime_date']));
        }
        if (isset($_POST['sandbaai_crime_time'])) {
            update_post_meta($post_id, '_sandbaai_crime_time', sanitize_text_field($_POST['sandbaai_crime_time']));
        }

        // Sanitize and save security groups
        if (isset($_POST['sandbaai_crime_security_groups'])) {
            $security_groups = array_map('intval', $_POST['sandbaai_crime_security_groups']);
            update_post_meta($post_id, '_sandbaai_crime_security_groups', $security_groups);
        } else {
            update_post_meta($post_id, '_sandbaai_crime_security_groups', array());
        }

        // Sanitize and save result status
        if (isset($_POST['sandbaai_crime_result'])) {
            update_post_meta($post_id, '_sandbaai_crime_result', sanitize_text_field($_POST['sandbaai_crime_result']));
        }

        // Sanitize and save photos
        if (isset($_POST['sandbaai_crime_photos'])) {
            $photos = array_map('intval', $_POST['sandbaai_crime_photos']);
            update_post_meta($post_id, '_sandbaai_crime_photos', $photos);
        } else {
            update_post_meta($post_id, '_sandbaai_crime_photos', array());
        }

        // Trigger WhatsApp notification for new crime reports
        if (get_post_status($post_id) === 'publish' && get_option('sandbaai_crime_enable_whatsapp') == 1) {
            $this->send_whatsapp_notification($post_id);
        }
    }

    /**
     * Send WhatsApp notification for a new crime report.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the crime report.
     */
    public function send_whatsapp_notification($post_id) {
        // Get WhatsApp notification class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/whatsapp-notifications.php';
        
        // Initialize WhatsApp notification class
        $whatsapp = new Sandbaai_Crime_WhatsApp_Notifications();
        
        // Send notification
        $whatsapp->send_crime_report_notification($post_id);
    }
    
    /**
     * Add custom columns to crime report list table.
     *
     * @since    1.0.0
     * @param    array    $columns    An array of column names.
     * @return   array    Modified array of column names.
     */
    public function add_crime_report_columns($columns) {
        $new_columns = array();
        
        // Insert title and checkbox first
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add custom columns
        $new_columns['crime_category'] = __('Crime Category', 'sandbaai-crime');
        $new_columns['location'] = __('Location', 'sandbaai-crime');
        $new_columns['datetime'] = __('Date & Time', 'sandbaai-crime');
        $new_columns['result_status'] = __('Result Status', 'sandbaai-crime');
        $new_columns['security_groups'] = __('Security Groups', 'sandbaai-crime');
        
        // Add remaining columns
        foreach ($columns as $key => $value) {
            if (!isset($new_columns[$key])) {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content for crime reports.
     *
     * @since    1.0.0
     * @param    string    $column    The name of the column to display.
     * @param    int       $post_id   The current post ID.
     */
    public function display_crime_report_columns($column, $post_id) {
        switch ($column) {
            case 'crime_category':
                $terms = get_the_terms($post_id, 'crime_category');
                if (!empty($terms)) {
                    $categories = array();
                    foreach ($terms as $term) {
                        $categories[] = $term->name;
                    }
                    echo implode(', ', $categories);
                } else {
                    echo '—';
                }
                break;
                
            case 'location':
                $zone = get_post_meta($post_id, '_sandbaai_crime_zone', true);
                $address = get_post_meta($post_id, '_sandbaai_crime_address', true);
                
                if (!empty($zone)) {
                    $zone_display = str_replace('_', ' ', ucwords($zone, '_'));
                    echo '<strong>' . esc_html($zone_display) . '</strong>';
                }
                
                if (!empty($address)) {
                    echo (!empty($zone) ? '<br>' : '') . esc_html($address);
                }
                
                if (empty($zone) && empty($address)) {
                    echo '—';
                }
                break;
                
            case 'datetime':
                $date = get_post_meta($post_id, '_sandbaai_crime_date', true);
                $time = get_post_meta($post_id, '_sandbaai_crime_time', true);
                
                if (!empty($date)) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($date)));
                }
                
                if (!empty($time)) {
                    echo (!empty($date) ? ' ' : '') . esc_html(date_i18n(get_option('time_format'), strtotime($time)));
                }
                
                if (empty($date) && empty($time)) {
                    echo '—';
                }
                break;
                
            case 'result_status':
                $result = get_post_meta($post_id, '_sandbaai_crime_result', true);
                
                if (!empty($result)) {
                    $status_class = 'sandbaai-crime-status-' . $result;
                    $status_display = ucwords(str_replace('_', ' ', $result));
                    echo '<span class="' . esc_attr($status_class) . '">' . esc_html($status_display) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'security_groups':
                $security_groups = get_post_meta($post_id, '_sandbaai_crime_security_groups', true);
                
                if (!empty($security_groups) && is_array($security_groups)) {
                    $group_names = array();
                    foreach ($security_groups as $group_id) {
                        $group = get_post($group_id);
                        if ($group) {
                            $group_names[] = $group->post_title;
                        }
                    }
                    echo implode(', ', $group_names);
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Add filters to crime report list table.
     *
     * @since    1.0.0
     */
    public function add_crime_report_filters() {
        global $typenow;
        
        if ($typenow == 'crime_report') {
            // Zone filter
            $selected_zone = isset($_GET['crime_zone']) ? sanitize_text_field($_GET['crime_zone']) : '';
            $zones = array(
                'north_east' => __('North East', 'sandbaai-crime'),
                'north_west' => __('North West', 'sandbaai-crime'),
                'south_east' => __('South East', 'sandbaai-crime'),
                'south_west' => __('South West', 'sandbaai-crime'),
            );
            
            echo '<select name="crime_zone">';
            echo '<option value="">' . __('All Zones', 'sandbaai-crime') . '</option>';
            
            foreach ($zones as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($selected_zone, $value, false) . '>' . esc_html($label) . '</option>';
            }
            
            echo '</select>';
            
            // Result status filter
            $selected_result = isset($_GET['crime_result']) ? sanitize_text_field($_GET['crime_result']) : '';
            $results = array(
                'pending' => __('Pending', 'sandbaai-crime'),
                'resolved' => __('Resolved', 'sandbaai-crime'),
                'unresolved' => __('Unresolved', 'sandbaai-crime'),
                'false_alarm' => __('False Alarm', 'sandbaai-crime'),
            );
            
            echo '<select name="crime_result">';
            echo '<option value="">' . __('All Results', 'sandbaai-crime') . '</option>';
            
            foreach ($results as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($selected_result, $value, false) . '>' . esc_html($label) . '</option>';
            }
            
            echo '</select>';
            
            // Date range filter
            $start_date = isset($_GET['crime_start_date']) ? sanitize_text_field($_GET['crime_start_date']) : '';
            $end_date = isset($_GET['crime_end_date']) ? sanitize_text_field($_GET['crime_end_date']) : '';
            
            echo '<input type="date" name="crime_start_date" placeholder="' . __('Start Date', 'sandbaai-crime') . '" value="' . esc_attr($start_date) . '">';
            echo '<input type="date" name="crime_end_date" placeholder="' . __('End Date', 'sandbaai-crime') . '" value="' . esc_attr($end_date) . '">';
        }
    }
    
    /**
     * Filter crime reports by custom fields.
     *
     * @since    1.0.0
     * @param    WP_Query    $query    The WP_Query instance.
     */
    public function filter_crime_reports_by_fields($query) {
        global $pagenow, $typenow;
        
        if ($pagenow == 'edit.php' && $typenow == 'crime_report' && is_admin()) {
            $meta_query = array();
            
            // Filter by zone
            if (isset($_GET['crime_zone']) && !empty($_GET['crime_zone'])) {
                $meta_query[] = array(
                    'key' => '_sandbaai_crime_zone',
                    'value' => sanitize_text_field($_GET['crime_zone']),
                    'compare' => '=',
                );
            }
            
            // Filter by result status
            if (isset($_GET['crime_result']) && !empty($_GET['crime_result'])) {
                $meta_query[] = array(
                    'key' => '_sandbaai_crime_result',
                    'value' => sanitize_text_field($_GET['crime_result']),
                    'compare' => '=',
                );
            }
            
            // Filter by date range
            if (isset($_GET['crime_start_date']) && !empty($_GET['crime_start_date'])) {
                $meta_query[] = array(
                    'key' => '_sandbaai_crime_date',
                    'value' => sanitize_text_field($_GET['crime_start_date']),
                    'compare' => '>=',
                    'type' => 'DATE',
                );
            }
            
            if (isset($_GET['crime_end_date']) && !empty($_GET['crime_end_date'])) {
                $meta_query[] = array(
                    'key' => '_sandbaai_crime_date',
                    'value' => sanitize_text_field($_GET['crime_end_date']),
                    'compare' => '<=',
                    'type' => 'DATE',
                );
            }
            
            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
        }
    }
    
    /**
     * Add custom columns to security group list table.
     *
     * @since    1.0.0
     * @param    array    $columns    An array of column names.
     * @return   array    Modified array of column names.
     */
    public function add_security_group_columns($columns) {
        $new_columns = array();
        
        // Insert title and checkbox first
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add custom columns
        $new_columns['logo'] = __('Logo', 'sandbaai-crime');
        $new_columns['contact'] = __('Contact', 'sandbaai-crime');
        $new_columns['crime_count'] = __('Crime Reports', 'sandbaai-crime');
        
        // Add remaining columns
        foreach ($columns as $key => $value) {
            if (!isset($new_columns[$key])) {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content for security groups.
     *
     * @since    1.0.0
     * @param    string    $column    The name of the column to display.
     * @param    int       $post_id   The current post ID.
     */
    public function display_security_group_columns($column, $post_id) {
        switch ($column) {
            case 'logo':
                $logo_id = get_post_meta($post_id, '_sandbaai_security_group_logo', true);
                if (!empty($logo_id)) {
                    echo wp_get_attachment_image($logo_id, array(50, 50));
                } else {
                    echo '—';
                }
                break;
                
            case 'contact':
                $phone = get_post_meta($post_id, '_sandbaai_security_group_phone', true);
                $email = get_post_meta($post_id, '_sandbaai_security_group_email', true);
                
                if (!empty($phone)) {
                    echo '<strong>' . esc_html($phone) . '</strong>';
                }
                
                if (!empty($email)) {
                    echo (!empty($phone) ? '<br>' : '') . '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                }
                
                if (empty($phone) && empty($email)) {
                    echo '—';
                }
                break;
                
            case 'crime_count':
                global $wpdb;
                
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $wpdb->postmeta 
                    WHERE meta_key = '_sandbaai_crime_security_groups' 
                    AND meta_value LIKE %s",
                    '%"' . $post_id . '"%'
                ));
                
                if ($count > 0) {
                    $url = admin_url('edit.php?post_type=crime_report&security_group=' . $post_id);
                    echo '<a href="' . esc_url($url) . '">' . intval($count) . '</a>';
                } else {
                    echo '0';
                }
                break;
        }
    }
    
    /**
     * Register AJAX handler for crime statistics.
     *
     * @since    1.0.0
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_get_crime_statistics', array($this, 'get_crime_statistics'));
        add_action('wp_ajax_nopriv_get_crime_statistics', array($this, 'get_crime_statistics'));
    }
    
    /**
     * AJAX handler for getting crime statistics.
     *
     * @since    1.0.0
     */
    public function get_crime_statistics() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sandbaai_crime_admin_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'sandbaai-crime')));
        }
        
        // Get filter parameters
        $filters = array();
        
        if (isset($_POST['month']) && !empty($_POST['month'])) {
            $filters['month'] = intval($_POST['month']);
        }
        
        if (isset($_POST['year']) && !empty($_POST['year'])) {
            $filters['year'] = intval($_POST['year']);
        }
        
        if (isset($_POST['crime_type']) && !empty($_POST['crime_type'])) {
            $filters['crime_type'] = sanitize_text_field($_POST['crime_type']);
        }
        
        if (isset($_POST['result']) && !empty($_POST['result'])) {
            $filters['result'] = sanitize_text_field($_POST['result']);
        }
        
        if (isset($_POST['zone']) && !empty($_POST['zone'])) {
            $filters['zone'] = sanitize_text_field($_POST['zone']);
        }
        
        // Get crime statistics data
        $statistics = $this->get_crime_data_for_statistics($filters);
        
        wp_send_json_success($statistics);
    }
    
    /**
     * Get crime data for statistics.
     *
     * @since    1.0.0
     * @param    array    $filters    Array of filter parameters.
     * @return   array    Crime statistics data.
     */
    private function get_crime_data_for_statistics($filters = array()) {
        // Set up query args
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(),
            'tax_query' => array(),
        );
        
        // Apply filters
        if (isset($filters['month']) && isset($filters['year'])) {
            $start_date = date('Y-m-d', strtotime($filters['year'] . '-' . $filters['month'] . '-01'));
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $args['meta_query'][] = array(
                'key' => '_sandbaai_crime_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            );
        } elseif (isset($filters['year'])) {
            $start_date = date('Y-m-d', strtotime($filters['year'] . '-01-01'));
            $end_date = date('Y-m-d', strtotime($filters['year'] . '-12-31'));
            
            $args['meta_query'][] = array(
                'key' => '_sandbaai_crime_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            );
        }
        
        if (isset($filters['crime_type'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'crime_category',
                'field' => 'slug',
                'terms' => $filters['crime_type'],
            );
        }
        
        if (isset($filters['result'])) {
            $args['meta_query'][] = array(
                'key' => '_sandbaai_crime_result',
                'value' => $filters['result'],
                'compare' => '=',
            );
        }
        
        if (isset($filters['zone'])) {
            $args['meta_query'][] = array(
                'key' => '_sandbaai_crime_zone',
                'value' => $filters['zone'],
                'compare' => '=',
            );
        }
        
        // Get crime reports
        $query = new WP_Query($args);
        $crime_reports = $query->posts;
        
        // Initialize statistics arrays
        $crime_by_day = array();
        $crime_by_category = array();
        $crime_by_zone = array();
        $crime_by_result = array();
        $crime_locations = array();
        
        // Process crime reports
        foreach ($crime_reports as $crime) {
            // Get crime data
            $date = get_post_meta($crime->ID, '_sandbaai_crime_date', true);
            $category_terms = get_the_terms($crime->ID, 'crime_category');
            $zone = get_post_meta($crime->ID, '_sandbaai_crime_zone', true);
            $result = get_post_meta($crime->ID, '_sandbaai_crime_result', true);
            $latitude = get_post_meta($crime->ID, '_sandbaai_crime_latitude', true);
            $longitude = get_post_meta($crime->ID, '_sandbaai_crime_longitude', true);
            
            // Process crime by day
            $day = date('Y-m-d', strtotime($date));
            if (!isset($crime_by_day[$day])) {
                $crime_by_day[$day] = 0;
            }
            $crime_by_day[$day]++;
            
            // Process crime by category
            if (!empty($category_terms) && is_array($category_terms)) {
                foreach ($category_terms as $term) {
                    if (!isset($crime_by_category[$term->name])) {
                        $crime_by_category[$term->name] = 0;
                    }
                    $crime_by_category[$term->name]++;
                }
            }
            
            // Process crime by zone
            if (!empty($zone)) {
                $zone_display = str_replace('_', ' ', ucwords($zone, '_'));
                if (!isset($crime_by_zone[$zone_display])) {
                    $crime_by_zone[$zone_display] = 0;
                }
                $crime_by_zone[$zone_display]++;
            }
            
            // Process crime by result
            if (!empty($result)) {
                $result_display = ucwords(str_replace('_', ' ', $result));
                if (!isset($crime_by_result[$result_display])) {
                    $crime_by_result[$result_display] = 0;
                }
                $crime_by_result[$result_display]++;
            }
            
            // Process crime locations for map
            if (!empty($latitude) && !empty($longitude)) {
                $crime_locations[] = array(
                    'id' => $crime->ID,
                    'title' => $crime->post_title,
                    'lat' => (float) $latitude,
                    'lng' => (float) $longitude,
                    'category' => (!empty($category_terms) && is_array($category_terms)) ? $category_terms[0]->name : '',
                    'date' => date_i18n(get_option('date_format'), strtotime($date)),
                    'url' => get_permalink($crime->ID),
                );
            }
        }
        
        // Sort crime by day
        ksort($crime_by_day);
        
        // Format crime by day for chart
        $chart_data = array();
        foreach ($crime_by_day as $day => $count) {
            $chart_data[] = array(
                'date' => date_i18n(get_option('date_format'), strtotime($day)),
                'count' => $count,
            );
        }
        
        // Return statistics data
        return array(
            'total' => count($crime_reports),
            'by_day' => $chart_data,
            'by_category' => $crime_by_category,
            'by_zone' => $crime_by_zone,
            'by_result' => $crime_by_result,
            'locations' => $crime_locations,
        );
    }
    
    /**
     * Register crime report import functionality.
     *
     * @since    1.0.0
     */
    public function register_import_page() {
        add_submenu_page(
            'sandbaai-crime',
            __('Import Crime Reports', 'sandbaai-crime'),
            __('Import Reports', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime-import',
            array($this, 'display_import_page')
        );
    }
    
    /**
     * Display the import page.
     *
     * @since    1.0.0
     */
    public function display_import_page() {
        require_once plugin_dir_path(__FILE__) . 'views/import.php';
    }
    
    /**
     * Handle CSV import of crime reports.
     *
     * @since    1.0.0
     */
    public function handle_csv_import() {
        if (isset($_POST['sandbaai_crime_import_submit']) && isset($_FILES['sandbaai_crime_import_file'])) {
            // Check nonce
            check_admin_referer('sandbaai_crime_import', 'sandbaai_crime_import_nonce');
            
            // Check file
            $file = $_FILES['sandbaai_crime_import_file'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                add_settings_error(
                    'sandbaai_crime_import',
                    'sandbaai_crime_import_error',
                    __('Error uploading file. Please try again.', 'sandbaai-crime'),
                    'error'
                );
                return;
            }
            
            // Check file extension
            $file_info = pathinfo($file['name']);
            if ($file_info['extension'] !== 'csv') {
                add_settings_error(
                    'sandbaai_crime_import',
                    'sandbaai_crime_import_error',
                    __('Invalid file format. Please upload a CSV file.', 'sandbaai-crime'),
                    'error'
                );
                return;
            }
            
            // Process CSV file
            $this->process_csv_import($file['tmp_name']);
        }
    }
    
    /**
     * Process CSV import of crime reports.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the CSV file.
     */
    private function process_csv_import($file_path) {
        // Required columns
        $required_columns = array(
            'title',
            'description',
            'date',
            'time',
            'zone',
            'address',
            'latitude',
            'longitude',
            'result',
            'category',
        );
        
        // Open the file
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            add_settings_error(
                'sandbaai_crime_import',
                'sandbaai_crime_import_error',
                __('Could not open the CSV file.', 'sandbaai-crime'),
                'error'
            );
            return;
        }
        
        // Get headers
        $headers = fgetcsv($handle);
        if ($headers === false) {
            add_settings_error(
                'sandbaai_crime_import',
                'sandbaai_crime_import_error',
                __('Could not read CSV headers.', 'sandbaai-crime'),
                'error'
            );
            fclose($handle);
            return;
        }
        
        // Check required columns
        $missing_columns = array();
        foreach ($required_columns as $required) {
            if (!in_array($required, $headers)) {
                $missing_columns[] = $required;
            }
        }
        
        if (!empty($missing_columns)) {
            add_settings_error(
                'sandbaai_crime_import',
                'sandbaai_crime_import_error',
                sprintf(
                    __('Missing required columns: %s', 'sandbaai-crime'),
                    implode(', ', $missing_columns)