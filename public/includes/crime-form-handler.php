<?php
/**
 * Crime Form Handler
 * 
 * Handles the processing of crime report form submissions
 * 
 * @package SandbaaiCrime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crime_Form_Handler Class
 * 
 * Handles the processing of crime report submissions from the front-end form
 */
class Crime_Form_Handler {
    
    /**
     * Initialize the class and set hooks
     */
    public function __construct() {
        // Register AJAX action for form processing
        add_action('wp_ajax_submit_crime_report', array($this, 'process_crime_report'));
        add_action('wp_ajax_nopriv_submit_crime_report', array($this, 'process_crime_report'));
        
        // Register shortcode for displaying the form
        add_shortcode('sandbaai_crime_form', array($this, 'display_crime_form'));
    }
    
    /**
     * Process the submitted crime report form
     */
    public function process_crime_report() {
        // Check nonce for security
        if (!isset($_POST['sandbaai_crime_nonce']) || !wp_verify_nonce($_POST['sandbaai_crime_nonce'], 'sandbaai_crime_form')) {
            wp_send_json_error(array('message' => 'Security verification failed. Please refresh the page and try again.'));
        }
        
        // Check required fields
        $required_fields = array('crime_title', 'crime_category', 'crime_date', 'crime_time', 'crime_location');
        $errors = array();
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', substr($field, 6))) . ' is required.';
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('message' => 'Please complete all required fields.', 'errors' => $errors));
        }
        
        // Sanitize form data
        $title = sanitize_text_field($_POST['crime_title']);
        $category = sanitize_text_field($_POST['crime_category']);
        $date = sanitize_text_field($_POST['crime_date']);
        $time = sanitize_text_field($_POST['crime_time']);
        $location = sanitize_text_field($_POST['crime_location']);
        $zone = isset($_POST['crime_zone']) ? sanitize_text_field($_POST['crime_zone']) : '';
        $description = wp_kses_post($_POST['crime_description']);
        $security_groups = isset($_POST['security_groups']) ? array_map('intval', $_POST['security_groups']) : array();
        $result = isset($_POST['crime_result']) ? sanitize_text_field($_POST['crime_result']) : '';
        
        // Convert date and time to timestamp
        $datetime = $date . ' ' . $time;
        $timestamp = strtotime($datetime);
        
        // Current user information
        $user_id = get_current_user_id();
        
        // Create post data array
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $description,
            'post_status'   => $user_id ? 'publish' : 'pending', // Auto-publish for logged-in users
            'post_type'     => 'crime_report',
            'post_author'   => $user_id ? $user_id : 1, // Default to admin if not logged in
        );
        
        // Insert the post into the database
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Error creating crime report: ' . $post_id->get_error_message()));
        }
        
        // Add post meta for crime details
        update_post_meta($post_id, '_crime_category', $category);
        update_post_meta($post_id, '_crime_date', $date);
        update_post_meta($post_id, '_crime_time', $time);
        update_post_meta($post_id, '_crime_timestamp', $timestamp);
        update_post_meta($post_id, '_crime_location', $location);
        update_post_meta($post_id, '_crime_zone', $zone);
        update_post_meta($post_id, '_crime_result', $result);
        
        // Process security groups
        if (!empty($security_groups)) {
            foreach ($security_groups as $group_id) {
                add_post_meta($post_id, '_crime_security_group', $group_id);
            }
        }
        
        // Process image uploads if present
        if (!empty($_FILES['crime_images'])) {
            $this->process_image_uploads($post_id, $_FILES['crime_images']);
        }
        
        // Trigger notification actions
        do_action('sandbaai_crime_report_submitted', $post_id);
        
        // Store in database for statistics
        $this->store_crime_for_statistics($post_id, $title, $category, $timestamp, $location, $zone, $security_groups, $result);
        
        // Return success message
        wp_send_json_success(array(
            'message' => 'Crime report submitted successfully!',
            'report_id' => $post_id
        ));
    }
    
    /**
     * Process image uploads for the crime report
     * 
     * @param int $post_id The post ID
     * @param array $files Files from $_FILES
     */
    private function process_image_uploads($post_id, $files) {
        // Check if multiple files or single file
        $file_count = is_array($files['name']) ? count($files['name']) : 1;
        
        // Handle multiple file uploads
        if ($file_count > 1) {
            for ($i = 0; $i < $file_count; $i++) {
                // Skip if file is not uploaded
                if ($files['error'][$i] !== 0) {
                    continue;
                }
                
                $file = array(
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                );
                
                $this->upload_single_image($post_id, $file);
            }
        } else {
            // Handle single file upload
            if ($files['error'] === 0) {
                $this->upload_single_image($post_id, $files);
            }
        }
    }
    
    /**
     * Upload a single image and attach to post
     * 
     * @param int $post_id The post ID
     * @param array $file File data from $_FILES
     */
    private function upload_single_image($post_id, $file) {
        // Include WordPress file upload handling
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Set up options for media_handle_sideload
        $file_attrs = array(
            'test_form' => false,
            'post_id' => $post_id,
        );
        
        // Upload and attach image to post
        $attachment_id = media_handle_sideload($file, $post_id, '', $file_attrs);
        
        if (!is_wp_error($attachment_id)) {
            // Add as post thumbnail if it's the first image
            if (!has_post_thumbnail($post_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
            
            // Add attachment ID to post meta for reference
            add_post_meta($post_id, '_crime_image', $attachment_id);
        }
    }
    
    /**
     * Store crime report in statistics database table
     * 
     * @param int $post_id The post ID
     * @param string $title Crime report title
     * @param string $category Crime category
     * @param int $timestamp Crime timestamp
     * @param string $location Crime location
     * @param string $zone Crime zone
     * @param array $security_groups Security groups involved
     * @param string $result Crime result
     */
    private function store_crime_for_statistics($post_id, $title, $category, $timestamp, $location, $zone, $security_groups, $result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_statistics';
        
        // Insert into database
        $wpdb->insert(
            $table_name,
            array(
                'post_id'       => $post_id,
                'title'         => $title,
                'category'      => $category,
                'crime_date'    => date('Y-m-d', $timestamp),
                'crime_time'    => date('H:i:s', $timestamp),
                'location'      => $location,
                'zone'          => $zone,
                'result'        => $result,
                'security_groups' => maybe_serialize($security_groups),
                'created_at'    => current_time('mysql'),
                'status'        => 'active'
            ),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
    }
    
    /**
     * Display the crime report form via shortcode
     * 
     * @return string HTML for the form
     */
    public function display_crime_form() {
        // Enqueue form scripts and styles
        wp_enqueue_script('sandbaai-crime-form-js');
        wp_enqueue_style('sandbaai-crime-form-css');
        
        // Start output buffer
        ob_start();
        
        // Check if template exists in theme
        $template_path = locate_template('sandbaai-crime/crime-form.php');
        
        if (!empty($template_path)) {
            include $template_path;
        } else {
            // Use default template from plugin
            include plugin_dir_path(dirname(__FILE__)) . 'public/templates/crime-form.php';
        }
        
        // Return the form HTML
        return ob_get_clean();
    }
}

// Initialize the class
$crime_form_handler = new Crime_Form_Handler();
