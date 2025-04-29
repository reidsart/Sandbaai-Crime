<?php
/**
 * Plugin Name: Sandbaai Crime Prevention
 * Plugin URI: https://yourwebsite.com/sandbaai-crime-prevention
 * Description: A comprehensive crime reporting and statistics tracking plugin for Sandbaai
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: sandbaai-crime
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SANDBAAI_CRIME_VERSION', '1.0.0');
define('SANDBAAI_CRIME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SANDBAAI_CRIME_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_sandbaai_crime() {
    // Create custom post types
    require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-sandbaai-crime-post-types.php';
    $post_types = new Sandbaai_Crime_Post_Types();
    $post_types->register();
    
    // Create database tables
    require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-sandbaai-crime-activator.php';
    Sandbaai_Crime_Activator::activate();
    
    // Set default options
    update_option('sandbaai_crime_version', SANDBAAI_CRIME_VERSION);
    
    // Add default security group "resident"
    if (!term_exists('resident', 'security_group')) {
        wp_insert_term('Resident', 'security_group', array(
            'description' => 'Default group for residents of Sandbaai',
            'slug' => 'resident'
        ));
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_sandbaai_crime() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_sandbaai_crime');
register_deactivation_hook(__FILE__, 'deactivate_sandbaai_crime');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-sandbaai-crime.php';

/**
 * Begins execution of the plugin.
 */
function run_sandbaai_crime() {
    $plugin = new Sandbaai_Crime();
    $plugin->run();
}
run_sandbaai_crime();
