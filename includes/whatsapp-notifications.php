<?php
/**
 * WhatsApp Notifications for Sandbaai Crime Plugin
 * 
 * This file handles the integration with WhatsApp API for automatic notifications
 * when new crime reports are submitted or status changes.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Sandbaai_WhatsApp_Notifications {
    /**
     * Initialize the class and set up hooks
     */
    public function __construct() {
        // Add settings fields
        add_action('sandbaai_crime_settings_notifications', array($this, 'add_settings_fields'));
        
        // Save settings
        add_action('sandbaai_crime_save_settings', array($this, 'save_settings'));
        
        // New crime report hook
        add_action('sandbaai_crime_report_submitted', array($this, 'notify_new_crime_report'), 10, 2);
