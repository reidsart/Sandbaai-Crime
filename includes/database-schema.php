<?php
/**
 * Database Schema for Sandbaai Crime plugin
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create and update database tables for the plugin.
 *
 * @since    1.0.0
 */
function sandbaai_crime_create_database_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create crime reports table
    $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description longtext NOT NULL,
        category varchar(100) NOT NULL,
        date_time datetime NOT NULL,
        location varchar(255) NOT NULL,
        zone varchar(100) NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'pending',
        result varchar(100) NULL,
        reporter_id bigint(20) NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Create security groups table
    $table_name = $wpdb->prefix . 'sandbaai_security_groups';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        logo varchar(255) NULL,
        contact_numbers varchar(255) NULL,
        email varchar(100) NULL,
        address text NULL,
        website varchar(255) NULL,
        description longtext NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Create crime report security groups relation table
    $table_name = $wpdb->prefix . 'sandbaai_crime_report_security_groups';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        crime_report_id mediumint(9) NOT NULL,
        security_group_id mediumint(9) NOT NULL,
        PRIMARY KEY  (id),
        KEY crime_report_id (crime_report_id),
        KEY security_group_id (security_group_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Create crime report photos table
    $table_name = $wpdb->prefix . 'sandbaai_crime_report_photos';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        crime_report_id mediumint(9) NOT NULL,
        file_path varchar(255) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY crime_report_id (crime_report_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Create crime categories table
    $table_name = $wpdb->prefix . 'sandbaai_crime_categories';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        description varchar(255) NULL,
        color varchar(7) NULL,
        icon varchar(50) NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY name (name)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Create zones table
    $table_name = $wpdb->prefix . 'sandbaai_zones';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        description varchar(255) NULL,
        coordinates text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY name (name)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Insert default crime categories
    $table_name = $wpdb->prefix . 'sandbaai_crime_categories';
    
    $default_categories = array(
        array(
            'name' => 'Break-in',
            'description' => 'Unauthorized entry into a property',
            'color' => '#ff0000',
            'icon' => 'home',
        ),
        array(
            'name' => 'Theft',
            'description' => 'Stealing of property without forced entry',
            'color' => '#ffa500',
            'icon' => 'shopping-bag',
        ),
        array(
            'name' => 'Assault',
            'description' => 'Physical attack on a person',
            'color' => '#800080',
            'icon' => 'user',
        ),
        array(
            'name' => 'Vandalism',
            'description' => 'Deliberate damage to property',
            'color' => '#008000',
            'icon' => 'tool',
        ),
        array(
            'name' => 'Suspicious Activity',
            'description' => 'Unusual or suspicious behavior',
            'color' => '#0000ff',
            'icon' => 'eye',
        ),
    );
    
    foreach ($default_categories as $category) {
        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE name = %s", $category['name'])
        );
        
        if (!$existing) {
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    // Insert default security group
    $table_name = $wpdb->prefix . 'sandbaai_security_groups';
    
    $existing = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_name WHERE title = %s", 'Resident')
    );
    
    if (!$existing) {
        $wpdb->insert(
            $table_name,
            array(
                'title' => 'Resident',
                'description' => 'Default group for residents',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    // Insert default zones
    $table_name = $wpdb->prefix . 'sandbaai_zones';
    
    $default_zones = array(
        array(
            'name' => 'Zone 1 (Northeast)',
            'description' => 'Northeast quadrant of Sandbaai',
        ),
        array(
            'name' => 'Zone 2 (Northwest)',
            'description' => 'Northwest quadrant of Sandbaai',
        ),
        array(
            'name' => 'Zone 3 (Southeast)',
            'description' => 'Southeast quadrant of Sandbaai',
        ),
        array(
            'name' => 'Zone 4 (Southwest)',
            'description' => 'Southwest quadrant of Sandbaai',
        ),
    );
    
    foreach ($default_zones as $zone) {
        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE name = %s", $zone['name'])
        );
        
        if (!$existing) {
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $zone['name'],
                    'description' => $zone['description'],
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s')
            );
        }
    }
    
    // Set database version
    update_option('sandbaai_crime_db_version', '1.0.0');
}

/**
 * Check if database needs upgrading.
 *
 * @since    1.0.0
 */
function sandbaai_crime_check_database_version() {
    $db_version = get_option('sandbaai_crime_db_version', '0');
    
    if (version_compare($db_version, '1.0.0', '<')) {
        sandbaai_crime_create_database_tables();
    }
}

/**
 * Delete plugin tables on uninstall.
 *
 * @since    1.0.0
 */
function sandbaai_crime_delete_database_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'sandbaai_crime_reports',
        $wpdb->prefix . 'sandbaai_security_groups',
        $wpdb->prefix . 'sandbaai_crime_report_security_groups',
        $wpdb->prefix . 'sandbaai_crime_report_photos',
        $wpdb->prefix . 'sandbaai_crime_categories',
        $wpdb->prefix . 'sandbaai_zones',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    delete_option('sandbaai_crime_db_version');
}
