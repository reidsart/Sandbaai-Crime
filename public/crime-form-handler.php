<?php
/**
 * Class responsible for handling crime report form submissions
 *
 * @package Sandbaai_Crime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sandbaai Crime Form Handler
 * 
 * Handles form validation, submission, and saving crime reports
 */
class Sandbaai_Crime_Form_Handler {

    /**
     * Initialize the form handler hooks
     */
    public static function init() {
        add_action('wp_ajax_submit_crime_report', array(__CLASS__, 'process_crime_report'));
        add_action('wp_ajax_nopriv_submit_crime_report', array(__CLASS__, 'process_crime_report'));
    }

    /**
     * Process crime report submission
     * 
     * Validates and saves the crime report form data
     */
    public static function process_crime_report() {
        // Verify nonce
        if (!isset($_POST['sandbaai_crime_report_nonce']) || 
            !wp_verify_nonce($_POST['sandbaai_crime_report_nonce'], 'sandbaai_crime_report_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed. Please refresh the page and try again.', 'sandbaai-crime')));
        }

        // Initialize response
        $response = array(
            'success' => false,
            'message' => '',
            'errors' => array()
        );

        // Validate required fields
        $required_fields = self::get_required_fields();
        $errors = self::validate_required_fields($required_fields);

        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['message'] = __('Please complete all required fields.', 'sandbaai-crime');
            wp_send_json_error($response);
        }

        // Process location data
        $location_data = self::process_location_data();
        if (!$location_data) {
            $response['message'] = __('Invalid location data.', 'sandbaai-crime');
            wp_send_json_error($response);
        }

        // Process files/photos if any
        $attachment_ids = array();
        if (!empty($_FILES['crime_photos']) && !empty($_FILES['crime_photos']['name'][0])) {
            $attachment_ids = self::process_uploaded_files();
            
            if (is_wp_error($attachment_ids)) {
                $response['message'] = $attachment_ids->get_error_message();
                wp_send_json_error($response);
            }
        }

        // Prepare crime report data
        $report_data = array(
            'post_title'    => sanitize_text_field($_POST['crime_title']),
            'post_content'  => wp_kses_post($_POST['crime_description']),
            'post_status'   => current_user_can('publish_posts') ? 'publish' : 'pending',
            'post_type'     => 'sandbaai_crime',
            'post_author'   => get_current_user_id() ? get_current_user_id() : 1, // Default to admin if not logged in
        );

        // Insert the crime report post
        $post_id = wp_insert_post($report_data);

        if (is_wp_error($post_id)) {
            $response['message'] = __('Error saving report. Please try again.', 'sandbaai-crime');
            wp_send_json_error($response);
        }

        // Save crime details meta
        self::save_crime_meta($post_id, $location_data, $attachment_ids);

        // Update post terms (crime category)