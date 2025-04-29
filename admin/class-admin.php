<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Sandbaai_Crime
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
        wp_enqueue_style($this->plugin_name, SANDBAAI_CRIME_PLUGIN_URL . 'admin/css/sandbaai-crime-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, SANDBAAI_CRIME_PLUGIN_URL . 'admin/js/sandbaai-crime-admin.js', array('jquery'), $this->version, false);
        
        // Localize the script with data
        wp_localize_script($this->plugin_name, 'sandbaai_crime_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sandbaai_crime_admin_nonce')
        ));
    }

    /**
     * Add plugin admin menu items.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Sandbaai Crime', 'sandbaai-crime'),
            __('Sandbaai Crime', 'sandbaai-crime'),
            'manage_options',
            'sandbaai-crime',
            array($this, 'display_dashboard_page'),
            'dashicons-shield',
            26
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
            'edit.php?post_type=crime_report'
        );
        
        // Security Groups submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Security Groups', 'sandbaai-crime'),
            __('Security Groups', 'sandbaai-crime'),
            'manage_options',
            'edit.php?post_type=security_group'
        );
        
        // Crime Categories submenu
        add_submenu_page(
            'sandbaai-crime',
            __('Crime Categories', 'sandbaai-crime'),
            __('Crime Categories', 'sandbaai-crime'),
            'manage_options',
            'edit-tags.php?taxonomy=crime_category&post_type=crime_report'
        );
        
        // Settings submenu
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
        include_once SANDBAAI_CRIME_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once SANDBAAI_CRIME_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings
        register_setting(
            'sandbaai_crime_settings',
            'sandbaai_crime_options',
            array($this, 'validate_settings')
        );
        
        // Add settings sections
        add_settings_section(
            'sandbaai_crime_general_settings',
            __('General Settings', 'sandbaai-crime'),
            array($this, 'display_general_settings_section'),
            'sandbaai-crime-settings'
        );
        
        add_settings_section(
            'sandbaai_crime_whatsapp_settings',
            __('WhatsApp Notification Settings', 'sandbaai-crime'),
            array($this, 'display_whatsapp_settings_section'),
            'sandbaai-crime-settings'
        );
        
        add_settings_section(
            'sandbaai_crime_map_settings',
            __('Map Settings', 'sandbaai-crime'),
            array($this, 'display_map_settings_section'),
            'sandbaai-crime-settings'
        );
        
        // Add settings fields
        // General settings
        add_settings_field(
            'enable_public_reporting',
            __('Enable Public Reporting', 'sandbaai-crime'),
            array($this, 'display_enable_public_reporting_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_general_settings'
        );
        
        add_settings_field(
            'report_approval_required',
            __('Require Approval for Public Reports', 'sandbaai-crime'),
            array($this, 'display_report_approval_required_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_general_settings'
        );
        
        // WhatsApp settings
        add_settings_field(
            'enable_whatsapp_notifications',
            __('Enable WhatsApp Notifications', 'sandbaai-crime'),
            array($this, 'display_enable_whatsapp_notifications_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_whatsapp_settings'
        );
        
        add_settings_field(
            'whatsapp_api_key',
            __('WhatsApp API Key', 'sandbaai-crime'),
            array($this, 'display_whatsapp_api_key_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_whatsapp_settings'
        );
        
        add_settings_field(
            'notification_recipients',
            __('Notification Recipients', 'sandbaai-crime'),
            array($this, 'display_notification_recipients_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_whatsapp_settings'
        );
        
        // Map settings
        add_settings_field(
            'map_center_latitude',
            __('Map Center Latitude', 'sandbaai-crime'),
            array($this, 'display_map_center_latitude_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_map_settings'
        );
        
        add_settings_field(
            'map_center_longitude',
            __('Map Center Longitude', 'sandbaai-crime'),
            array($this, 'display_map_center_longitude_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_map_settings'
        );
        
        add_settings_field(
            'map_zoom_level',
            __('Map Default Zoom Level', 'sandbaai-crime'),
            array($this, 'display_map_zoom_level_field'),
            'sandbaai-crime-settings',
            'sandbaai_crime_map_settings'
        );
    }
    
    /**
     * Display the general settings section.
     *
     * @since    1.0.0
     */
    public function display_general_settings_section() {
        echo '<p>' . __('Configure general settings for the Sandbaai Crime Reporting plugin.', 'sandbaai-crime') . '</p>';
    }
    
    /**
     * Display the WhatsApp settings section.
     *
     * @since    1.0.0
     */
    public function display_whatsapp_settings_section() {
        echo '<p>' . __('Configure WhatsApp notification settings for crime reports.', 'sandbaai-crime') . '</p>';
    }
    
    /**
     * Display the map settings section.
     *
     * @since    1.0.0
     */
    public function display_map_settings_section() {
        echo '<p>' . __('Configure map settings for crime reporting and display.', 'sandbaai-crime') . '</p>';
    }
    
    /**
     * Display the enable public reporting field.
     *
     * @since    1.0.0
     */
    public function display_enable_public_reporting_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['enable_public_reporting']) ? $options['enable_public_reporting'] : 1;
        ?>
        <input type="checkbox" name="sandbaai_crime_options[enable_public_reporting]" value="1" <?php checked(1, $value); ?>>
        <span class="description"><?php _e('Allow public users to submit crime reports', 'sandbaai-crime'); ?></span>
        <?php
    }
    
    /**
     * Display the report approval required field.
     *
     * @since    1.0.0
     */
    public function display_report_approval_required_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['report_approval_required']) ? $options['report_approval_required'] : 1;
        ?>
        <input type="checkbox" name="sandbaai_crime_options[report_approval_required]" value="1" <?php checked(1, $value); ?>>
        <span class="description"><?php _e('Require admin approval for public crime reports before publishing', 'sandbaai-crime'); ?></span>
        <?php
    }
    
    /**
     * Display the enable WhatsApp notifications field.
     *
     * @since    1.0.0
     */
    public function display_enable_whatsapp_notifications_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['enable_whatsapp_notifications']) ? $options['enable_whatsapp_notifications'] : 0;
        ?>
        <input type="checkbox" name="sandbaai_crime_options[enable_whatsapp_notifications]" value="1" <?php checked(1, $value); ?>>
        <span class="description"><?php _e('Send WhatsApp notifications for new crime reports', 'sandbaai-crime'); ?></span>
        <?php
    }
    
    /**
     * Display the WhatsApp API key field.
     *
     * @since    1.0.0
     */
    public function display_whatsapp_api_key_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['whatsapp_api_key']) ? $options['whatsapp_api_key'] : '';
        ?>
        <input type="text" name="sandbaai_crime_options[whatsapp_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Enter your WhatsApp Business API key', 'sandbaai-crime'); ?></p>
        <?php
    }
    
    /**
     * Display the notification recipients field.
     *
     * @since    1.0.0
     */
    public function display_notification_recipients_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['notification_recipients']) ? $options['notification_recipients'] : '';
        ?>
        <textarea name="sandbaai_crime_options[notification_recipients]" rows="5" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('Enter WhatsApp numbers to receive notifications (one per line)', 'sandbaai-crime'); ?></p>
        <?php
    }
    
    /**
     * Display the map center latitude field.
     *
     * @since    1.0.0
     */
    public function display_map_center_latitude_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['map_center_latitude']) ? $options['map_center_latitude'] : '-34.3965';
        ?>
        <input type="text" name="sandbaai_crime_options[map_center_latitude]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    /**
     * Display the map center longitude field.
     *
     * @since    1.0.0
     */
    public function display_map_center_longitude_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['map_center_longitude']) ? $options['map_center_longitude'] : '19.1473';
        ?>
        <input type="text" name="sandbaai_crime_options[map_center_longitude]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
    
    /**
     * Display the map zoom level field.
     *
     * @since    1.0.0
     */
    public function display_map_zoom_level_field() {
        $options = get_option('sandbaai_crime_options', array());
        $value = isset($options['map_zoom_level']) ? $options['map_zoom_level'] : '14';
        ?>
        <input type="number" name="sandbaai_crime_options[map_zoom_level]" value="<?php echo intval($value); ?>" min="1" max="20" class="small-text">
        <?php
    }
    
    /**
     * Validate settings.
     *
     * @since    1.0.0
     * @param    array    $input    The input to validate.
     * @return   array              The validated input.
     */
    public function validate_settings($input) {
        $output = array();
        
        // Sanitize and validate each setting
        if (isset($input['enable_public_reporting'])) {
            $output['enable_public_reporting'] = 1;
        } else {
            $output['enable_public_reporting'] = 0;
        }
        
        if (isset($input['report_approval_required'])) {
            $output['report_approval_required'] = 1;
        } else {
            $output['report_approval_required'] = 0;
        }
        
        if (isset($input['enable_whatsapp_notifications'])) {
            $output['enable_whatsapp_notifications'] = 1;
        } else {
            $output['enable_whatsapp_notifications'] = 0;
        }
        
        if (isset($input['whatsapp_api_key'])) {
            $output['whatsapp_api_key'] = sanitize_text_field($input['whatsapp_api_key']);
        }
        
        if (isset($input['notification_recipients'])) {
            $output['notification_recipients'] = sanitize_textarea_field($input['notification_recipients']);
        }
        
        if (isset($input['map_center_latitude'])) {
            $output['map_center_latitude'] = sanitize_text_field($input['map_center_latitude']);
        }
        
        if (isset($input['map_center_longitude'])) {
            $output['map_center_longitude'] = sanitize_text_field($input['map_center_longitude']);
        }
        
        if (isset($input['map_zoom_level'])) {
            $output['map_zoom_level'] = intval($input['map_zoom_level']);
            if ($output['map_zoom_level'] < 1 || $output['map_zoom_level'] > 20) {
                $output['map_zoom_level'] = 14;
            }
        }
        
        return $output;
    }
    
    /**
     * Add meta boxes to the custom post types.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        // Crime Report meta boxes
        add_meta_box(
            'crime_report_details',
            __('Crime Report Details', 'sandbaai-crime'),
            array($this, 'display_crime_report_details_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_location',
            __('Crime Report Location', 'sandbaai-crime'),
            array($this, 'display_crime_report_location_meta_box'),
            'crime_report',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crime_report_security_groups',
            __('Security Groups Involved', 'sandbaai-crime'),
            array($this, 'display_crime_report_security_groups_meta_box'),
            'crime_report',
            'side',
            'default'
        );
        
        // Security Group meta boxes
        add_meta_box(
            'security_group_details',
            __('Security Group Details', 'sandbaai-crime'),
            array($this, 'display_security_group_details_meta_box'),
            'security_group',
            'normal',
            'high'
        );
    }
    
    /**
     * Display the crime report details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function display_crime_report_details_meta_box($post) {
        // Add a nonce field
        wp_nonce_field('sandbaai_crime_meta_box', 'sandbaai_crime_meta_box_nonce');
        
        // Get the current values
        $date = get_post_meta($post->ID, '_crime_date', true);
        $time = get_post_meta($post->ID, '_crime_time', true);
        $result = get_post_meta($post->ID, '_crime_result', true);
        $reporter_name = get_post_meta($post->ID, '_reporter_name', true);
        $reporter_contact = get_post_meta($post->ID, '_reporter_contact', true);
        
        // If date is empty, use current date
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        
        // If time is empty, use current time
        if (empty($time)) {
            $time = date('H:i');
        }
        
        ?>
        <div class="crime-report-meta-box">
            <div class="form-field">
                <label for="crime_date"><?php _e('Date', 'sandbaai-crime'); ?>:</label>
                <input type="date" id="crime_date" name="crime_date" value="<?php echo esc_attr($date); ?>">
            </div>
            
            <div class="form-field">
                <label for="crime_time"><?php _e('Time', 'sandbaai-crime'); ?>:</label>
                <input type="time" id="crime_time" name="crime_time" value="<?php echo esc_attr($time); ?>">
            </div>
            
            <div class="form-field">
                <label for="crime_result"><?php _e('Result', 'sandbaai-crime'); ?>:</label>
                <select id="crime_result" name="crime_result">
                    <option value=""><?php _e('Select Result', 'sandbaai-crime'); ?></option>
                    <option value="resolved" <?php selected($result, 'resolved'); ?>><?php _e('Resolved', 'sandbaai-crime'); ?></option>
                    <option value="pending" <?php selected($result, 'pending'); ?>><?php _e('Pending', 'sandbaai-crime'); ?></option>
                    <option value="unresolved" <?php selected($result, 'unresolved'); ?>><?php _e('Unresolved', 'sandbaai-crime'); ?></option>
                    <option value="false_alarm" <?php selected($result, 'false_alarm'); ?>><?php _e('False Alarm', 'sandbaai-crime'); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label for="reporter_name"><?php _e('Reporter Name', 'sandbaai-crime'); ?>:</label>
                <input type="text" id="reporter_name" name="reporter_name" value="<?php echo esc_attr($reporter_name); ?>">
            </div>
            
            <div class="form-field">
                <label for="reporter_contact"><?php _e('Reporter Contact', 'sandbaai-crime'); ?>:</label>
                <input type="text" id="reporter_contact" name="reporter_contact" value="<?php echo esc_attr($reporter_contact); ?>">
            </div>
        </div>
        <?php
    }
    
    /**
     * Display the crime report location meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function display_crime_report_location_meta_box($post) {
        // Get the current values
        $address = get_post_meta($post->ID, '_crime_address', true);
        $latitude = get_post_meta($post->ID, '_crime_latitude', true);
        $longitude = get_post_meta($post->ID, '_crime_longitude', true);
        $zone = get_post_meta($post->ID, '_crime_zone', true);
        
        // Get the options for map default values
        $options = get_option('sandbaai_crime_options', array());
        $default_latitude = isset($options['map_center_latitude']) ? $options['map_center_latitude'] : '-34.3965';
        $default_longitude = isset($options['map_center_longitude']) ? $options['map_center_longitude'] : '19.1473';
        $default_zoom = isset($options['map_zoom_level']) ? $options['map_zoom_level'] : '14';
        
        // If lat/lng are empty, use defaults
        if (empty($latitude)) {
            $latitude = $default_latitude;
        }
        
        if (empty($longitude)) {
            $longitude = $default_longitude;
        }
        
        ?>
        <div class="crime-report-location-meta-box">
            <div class="form-field">
                <label for="crime_address"><?php _e('Address', 'sandbaai-crime'); ?>:</label>
                <input type="text" id="crime_address" name="crime_address" value="<?php echo esc_attr($address); ?>" class="large-text">
            </div>
            
            <div class="form-field">
                <label for="crime_zone"><?php _e('Zone', 'sandbaai-crime'); ?>:</label>
                <select id="crime_zone" name="crime_zone">
                    <option value=""><?php _e('Select Zone', 'sandbaai-crime'); ?></option>
                    <option value="north" <?php selected($zone, 'north'); ?>><?php _e('North', 'sandbaai-crime'); ?></option>
                    <option value="south" <?php selected($zone, 'south'); ?>><?php _e('South', 'sandbaai-crime'); ?></option>
                    <option value="east" <?php selected($zone, 'east'); ?>><?php _e('East', 'sandbaai-crime'); ?></option>
                    <option value="west" <?php selected($zone, 'west'); ?>><?php _e('West', 'sandbaai-crime'); ?></option>
                    <option value="central" <?php selected($zone, 'central'); ?>><?php _e('Central', 'sandbaai-crime'); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label><?php _e('Select Location on Map', 'sandbaai-crime'); ?>:</label>
                <div id="crime-location-map" style="height: 300px; margin-top: 10px;"></div>
                <input type="hidden" id="crime_latitude" name="crime_latitude" value="<?php echo esc_attr($latitude); ?>">
                <input type="hidden" id="crime_longitude" name="crime_longitude" value="<?php echo esc_attr($longitude); ?>">
            </div>
            
            <div class="form-field location-coordinates">
                <label><?php _e('Coordinates', 'sandbaai-crime'); ?>:</label>
                <span id="location-coordinates-display"><?php echo esc_html($latitude . ', ' . $longitude); ?></span>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Initialize the map when the page is fully loaded
                var map;
                var marker;
                
                function initMap() {
                    var mapOptions = {
                        center: {
                            lat: parseFloat('<?php echo esc_js($latitude); ?>'),
                            lng: parseFloat('<?php echo esc_js($longitude); ?>')
                        },
                        zoom: parseInt('<?php echo esc_js($default_zoom); ?>'),
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    };
                    
                    map = new google.maps.Map(document.getElementById('crime-location-map'), mapOptions);
                    
                    marker = new google.maps.Marker({
                        position: mapOptions.center,
                        map: map,
                        draggable: true
                    });
                    
                    // Update coordinates when marker is dragged
                    google.maps.event.addListener(marker, 'dragend', function() {
                        var position = marker.getPosition();
                        $('#crime_latitude').val(position.lat());
                        $('#crime_longitude').val(position.lng());
                        $('#location-coordinates-display').text(position.lat() + ', ' + position.lng());
                        
                        // Reverse geocode to get address
                        var geocoder = new google.maps.Geocoder();
                        geocoder.geocode({'location': position}, function(results, status) {
                            if (status === 'OK' && results[0]) {
                                $('#crime_address').val(results[0].formatted_address);
                            }
                        });
                    });
                    
                    // Add click event to map
                    google.maps.event.addListener(map, 'click', function(event) {
                        marker.setPosition(event.latLng);
                        $('#crime_latitude').val(event.latLng.lat());
                        $('#crime_longitude').val(event.latLng.lng());
                        $('#location-coordinates-display').text(event.latLng.lat() + ', ' + event.latLng.lng());
                        
                        // Reverse geocode to get address
                        var geocoder = new google.maps.Geocoder();
                        geocoder.geocode({'location': event.latLng}, function(results, status) {
                            if (status === 'OK' && results[0]) {
                                $('#crime_address').val(results[0].formatted_address);
                            }
                        });
                    });
                    
                    // Address lookup
                    $('#crime_address').on('change', function() {
                        