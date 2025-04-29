<?php
/**
 * Plugin Name: Sandbaai Crime Reporting
 * Plugin URI: https://github.com/reidsart/Sandbaai-Crime
 * Description: A comprehensive WordPress plugin for crime reporting and statistics tracking in Sandbaai.
 * Version: 1.0.0
 * Author: Reidsart
 * Author URI: https://github.com/reidsart
 * Text Domain: sandbaai-crime
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SANDBAAI_CRIME_VERSION', '1.0.0');
define('SANDBAAI_CRIME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SANDBAAI_CRIME_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SANDBAAI_CRIME_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_sandbaai_crime() {
    require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/database-schema.php';
    $database_schema = new Sandbaai_Crime_Database_Schema();
    $database_schema->create_tables();
    
    // Register custom post types
    require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-post-types.php';
    $post_types = new Sandbaai_Crime_Post_Types();
    $post_types->register_crime_report_post_type();
    $post_types->register_security_group_post_type();
    
    // Clear the permalinks
    flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_sandbaai_crime() {
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_sandbaai_crime');
register_deactivation_hook(__FILE__, 'deactivate_sandbaai_crime');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/plugin-base-structure.php';

/**
 * Begins execution of the plugin.
 */
function run_sandbaai_crime() {
    $plugin = new Sandbaai_Crime();
    $plugin->run();
}
run_sandbaai_crime();
