<?php
/**
 * WhatsApp Notifications
 * 
 * Handles sending crime report notifications via WhatsApp
 * 
 * @package Sandbaai_Crime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Sandbaai_Crime_WhatsApp_Notifications {
    
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     * @var      object
     */
    protected static $instance = null;

    /**
     * WhatsApp API settings
     */
    private $api_key;
    private $sender_number;
    private $notification_template;
    private $enabled;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->load_settings();
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
     * Load WhatsApp API settings from WP options
     */
    private function load_settings() {
        $this->api_key = get_option('sandbaai_crime_whatsapp_api_key', '');
        $this->sender_number = get_option('sandbaai_crime_whatsapp_sender_number', '');
        $this->notification_template = get_option('sandbaai_crime_whatsapp_template', 'New crime report: {title} at {location} - {date}');
        $this->enabled = get_option('sandbaai_crime_whatsapp_enabled', false);
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Send notification when a new crime report is published
        add_action('transition_post_status', array($this, 'maybe_send_notification'), 10, 3);
        
        // Admin settings page
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register WhatsApp settings
     */
    public function register_settings() {
        register_setting('sandbaai_crime_settings', 'sandbaai_crime_whatsapp_api_key');
        register_setting('sandbaai_crime_settings', 'sandbaai_crime_whatsapp_sender_number');
        register_setting('sandbaai_crime_settings', 'sandbaai_crime_whatsapp_template');
        register_setting('sandbaai_crime_settings', 'sandbaai_crime_whatsapp_enabled');
        
        add_settings_section(
            'sandbaai_crime_whatsapp_section',
            __('WhatsApp Notifications', 'sandbaai-crime'),
            array($this, 'whatsapp_section_callback'),
            'sandbaai_crime_settings'
        );
        
        add_settings_field(
            'sandbaai_crime_whatsapp_enabled',
            __('Enable WhatsApp Notifications', 'sandbaai-crime'),
            array($this, 'enabled_field_callback'),
            'sandbaai_crime_settings',
            'sandbaai_crime_whatsapp_section'
        );
        
        add_settings_field(
            'sandbaai_crime_whatsapp_api_key',
            __('WhatsApp API Key', 'sandbaai-crime'),
            array($this, 'api_key_field_callback'),
            'sandbaai_crime_settings',
            'sandbaai_crime_whatsapp_section'
        );
        
        add_settings_field(
            'sandbaai_crime_whatsapp_sender_number',
            __('WhatsApp Sender Number', 'sandbaai-crime'),
            array($this, 'sender_number_field_callback'),
            'sandbaai_crime_settings',
            'sandbaai_crime_whatsapp_section'
        );
        
        add_settings_field(
            'sandbaai_crime_whatsapp_template',
            __('Notification Template', 'sandbaai-crime'),
            array($this, 'template_field_callback'),
            'sandbaai_crime_settings',
            'sandbaai_crime_whatsapp_section'
        );
    }
    
    /**
     * WhatsApp section description
     */
    public function whatsapp_section_callback() {
        echo '<p>' . __('Configure WhatsApp notification settings. You need a WhatsApp Business API account to use this feature.', 'sandbaai-crime') . '</p>';
    }
    
    /**
     * Enable field callback
     */
    public function enabled_field_callback() {
        $enabled = get_option('sandbaai_crime_whatsapp_enabled', false);
        echo '<input type="checkbox" name="sandbaai_crime_whatsapp_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
    }
    
    /**
     * API Key field callback
     */
    public function api_key_field_callback() {
        $api_key = get_option('sandbaai_crime_whatsapp_api_key', '');
        echo '<input type="text" name="sandbaai_crime_whatsapp_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
    }
    
    /**
     * Sender number field callback
     */
    public function sender_number_field_callback() {
        $sender_number = get_option('sandbaai_crime_whatsapp_sender_number', '');
        echo '<input type="text" name="sandbaai_crime_whatsapp_sender_number" value="' . esc_attr($sender_number) . '" class="regular-text" />';
        echo '<p class="description">' . __('Include country code, e.g., 27821234567', 'sandbaai-crime') . '</p>';
    }
    
    /**
     * Template field callback
     */
    public function template_field_callback() {
        $template = get_option('sandbaai_crime_whatsapp_template', 'New crime report: {title} at {location} - {date}');
        echo '<textarea name="sandbaai_crime_whatsapp_template" class="large-text" rows="3">' . esc_textarea($template) . '</textarea>';
        echo '<p class="description">' . __('Available placeholders: {title}, {category}, {location}, {date}, {time}, {description}, {status}', 'sandbaai-crime') . '</p>';
    }

    /**
     * Check if a notification should be sent when a post status changes
     */
    public function maybe_send_notification($new_status, $old_status, $post) {
        // Only proceed if WhatsApp notifications are enabled
        if (!$this->enabled) {
            return;
        }
        
        // Only send when a crime report is published for the first time
        if ('publish' === $new_status && 'publish' !== $old_status && 'crime_report' === $post->post_type) {
            $this->send_crime_report_notification($post->ID);
        }
    }

    /**
     * Send notification for a crime report
     */
    public function send_crime_report_notification($post_id) {
        // Get security groups to notify
        $security_groups = $this->get_security_groups_to_notify($post_id);
        
        if (empty($security_groups)) {
            return;
        }
        
        // Get crime report data
        $crime_data = $this->get_crime_report_data($post_id);
        
        // Format message from template
        $message = $this->format_notification_message($crime_data);
        
        // Send to each security group
        foreach ($security_groups as $group) {
            $phone_numbers = $this->get_security_group_phone_numbers($group->ID);
            
            foreach ($phone_numbers as $phone) {
                $this->send_whatsapp_message($phone, $message);
            }
        }
        
        // Log that notification was sent
        update_post_meta($post_id, '_whatsapp_notification_sent', current_time('mysql'));
    }
    
    /**
     * Get security groups that should be notified about this crime
     */
    private function get_security_groups_to_notify($post_id) {
        // Get assigned security groups
        $assigned_groups = get_post_meta($post_id, '_security_groups', true);
        
        if (!empty($assigned_groups)) {
            return get_posts(array(
                'post_type' => 'security_group',
                'post__in' => (array) $assigned_groups,
                'posts_per_page' => -1,
            ));
        }
        
        // If no specific groups assigned, get groups by zone
        $zone = get_post_meta($post_id, '_crime_zone', true);
        
        if (!empty($zone)) {
            // Query security groups responsible for this zone
            return get_posts(array(
                'post_type' => 'security_group',
                'meta_query' => array(
                    array(
                        'key' => '_responsible_zones',
                        'value' => $zone,
                        'compare' => 'LIKE',
                    ),
                ),
                'posts_per_page' => -1,
            ));
        }
        
        return array();
    }
    
    /**
     * Get phone numbers for a security group
     */
    private function get_security_group_phone_numbers($group_id) {
        $numbers = array();
        
        // Primary contact number
        $primary_number = get_post_meta($group_id, '_primary_contact_number', true);
        if (!empty($primary_number)) {
            $numbers[] = $this->format_phone_number($primary_number);
        }
        
        // Additional contact numbers
        $additional_numbers = get_post_meta($group_id, '_additional_contact_numbers', true);
        if (!empty($additional_numbers) && is_array($additional_numbers)) {
            foreach ($additional_numbers as $number) {
                $numbers[] = $this->format_phone_number($number);
            }
        }
        
        return array_filter($numbers);
    }
    
    /**
     * Format phone number for WhatsApp
     */
    private function format_phone_number($number) {
        // Remove any non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Ensure it starts with country code (assuming South Africa as default)
        if (substr($number, 0, 2) === '27') {
            return $number;
        } elseif (substr($number, 0, 1) === '0') {
            return '27' . substr($number, 1);
        }
        
        return $number;
    }
    
    /**
     * Get crime report data for notification
     */
    private function get_crime_report_data($post_id) {
        $post = get_post($post_id);
        
        return array(
            'title' => $post->post_title,
            'category' => $this->get_crime_category($post_id),
            'location' => get_post_meta($post_id, '_crime_location', true),
            'zone' => get_post_meta($post_id, '_crime_zone', true),
            'date' => get_post_meta($post_id, '_crime_date', true),
            'time' => get_post_meta($post_id, '_crime_time', true),
            'description' => wp_trim_words($post->post_content, 30),
            'status' => get_post_meta($post_id, '_crime_status', true),
            'url' => get_permalink($post_id),
        );
    }
    
    /**
     * Get crime category name
     */
    private function get_crime_category($post_id) {
        $terms = wp_get_post_terms($post_id, 'crime_category');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        
        return '';
    }
    
    /**
     * Format notification message using template and crime data
     */
    private function format_notification_message($crime_data) {
        $message = $this->notification_template;
        
        foreach ($crime_data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Send WhatsApp message
     */
    public function send_whatsapp_message($to_number, $message) {
        // Don't proceed if API key or sender number is missing
        if (empty($this->api_key) || empty($this->sender_number)) {
            return false;
        }
        
        // WhatsApp API endpoint (this would be specific to your WhatsApp Business API provider)
        $api_url = 'https://api.whatsapp.com/v1/messages'; // Replace with actual provider URL
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'from' => $this->sender_number,
                'to' => $to_number,
                'type' => 'text',
                'text' => array(
                    'body' => $message
                )
            )),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            // Log error
            error_log('WhatsApp API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return true;
        } else {
            // Log error
            error_log('WhatsApp API Error: ' . $response_code . ' ' . $response_body);
            return false;
        }
    }
    
    /**
     * Send a test message
     */
    public function send_test_message($phone_number) {
        $test_message = __('This is a test message from the Sandbaai Crime Reporting System.', 'sandbaai-crime');
        return $this->send_whatsapp_message($phone_number, $test_message);
    }
}

// Initialize the notifications class
function sandbaai_crime_whatsapp_notifications() {
    return Sandbaai_Crime_WhatsApp_Notifications::get_instance();
}

// Start the module
sandbaai_crime_whatsapp_notifications();
