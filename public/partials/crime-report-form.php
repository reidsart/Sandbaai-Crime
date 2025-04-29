<?php
/**
 * Crime Report Form
 *
 * This file contains the mobile-optimized form for reporting crimes
 *
 * @link       https://sandbaai.com
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/public/partials
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcode handler for the crime report form
 * 
 * @return string Form HTML
 */
function sandbaai_crime_report_form_shortcode() {
    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        return '<div class="sandbaai-message sandbaai-error">
            <p>' . __( 'You must be logged in to report a crime.', 'sandbaai-crime' ) . '</p>
            <p><a href="' . wp_login_url( get_permalink() ) . '" class="button">' . __( 'Log In', 'sandbaai-crime' ) . '</a></p>
        </div>';
    }
    
    // Enqueue required scripts and styles
    wp_enqueue_style( 'sandbaai-crime-report-form' );
    wp_enqueue_script( 'sandbaai-crime-report-form' );
    
    // Get crime categories
    $crime_categories = get_terms( array(
        'taxonomy' => 'crime_category',
        'hide_empty' => false,
    ) );
    
    // Get security groups
    $security_groups = get_posts( array(
        'post_type' => 'security_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    
    // Current date and time for default values
    $current_date = date( 'Y-m-d' );
    $current_time = date( 'H:i' );
    
    // Start output buffer
    ob_start();
    
    // Check for form submission
    $form_submitted = false;
    $success_message = '';
    $error_message = '';
    
    if ( isset( $_POST['sandbaai_crime_report_submit'] ) ) {
        // Verify nonce
        if ( ! isset( $_POST['sandbaai_crime_report_nonce'] ) || ! wp_verify_nonce( $_POST['sandbaai_crime_report_nonce'], 'sandbaai_crime_report' ) ) {
            $error_message = __( 'Security check failed. Please try again.', 'sandbaai-crime' );
        } else {
            // Process form submission
            $report_data = array(
                'title' => isset( $_POST['crime_title'] ) ? sanitize_text_field( $_POST['crime_title'] ) : '',
                'category' => isset( $_POST['crime_category'] ) ? intval( $_POST['crime_category'] ) : 0,
                'date' => isset( $_POST['crime_date'] ) ? sanitize_text_field( $_POST['crime_date'] ) : $current_date,
                'time' => isset( $_POST['crime_time'] ) ? sanitize_text_field( $_POST['crime_time'] ) : $current_time,
                'location' => isset( $_POST['crime_location'] ) ? sanitize_text_field( $_POST['crime_location'] ) : '',
                'zone' => isset( $_POST['crime_zone'] ) ? sanitize_text_field( $_POST['crime_zone'] ) : '',
                'result' => isset( $_POST['crime_result'] ) ? sanitize_text_field( $_POST['crime_result'] ) : '',
                'description' => isset( $_POST['crime_description'] ) ? sanitize_textarea_field( $_POST['crime_description'] ) : '',
                'security_groups' => isset( $_POST['crime_security_groups'] ) ? array_map( 'intval', (array) $_POST['crime_security_groups'] ) : array(),
            );
            
            // Basic validation
            $validation_errors = array();
            
            if ( empty( $report_data['title'] ) ) {
                $validation_errors[] = __( 'Please enter a title for the crime report.', 'sandbaai-crime' );
            }
            
            if ( empty( $report_data['category'] ) ) {
                $validation_errors[] = __( 'Please select a crime category.', 'sandbaai-crime' );
            }
            
            if ( empty( $report_data['location'] ) && empty( $report_data['zone'] ) ) {
                $validation_errors[] = __( 'Please enter a location or select a zone.', 'sandbaai-crime' );
            }
            
            if ( empty( $validation_errors ) ) {
                // Create the crime report post
                $post_data = array(
                    'post_title'    => $report_data['title'],
                    'post_