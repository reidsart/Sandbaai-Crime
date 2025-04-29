<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/includes
 */
class Sandbaai_Crime_Activator {

    /**
     * Create the necessary database tables for the plugin.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table for crime reports
        $table_crime_reports = $wpdb->prefix . 'sandbaai_crime_reports';
        
        // Table for security groups
        $table_security_groups = $wpdb->prefix . 'sandbaai_security_groups';
        
        // Table for crime categories
        $table_crime_categories = $wpdb->prefix . 'sandbaai_crime_categories';
        
        // Table for zones
        $table_zones = $wpdb->prefix . 'sandbaai_zones';
        
        // Table for crime reports to security groups relationship
        $table_report_groups = $wpdb->prefix . 'sandbaai_report_groups';

        // SQL for crime reports table
        $sql_crime_reports = "CREATE TABLE $table_crime_reports (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            crime_category_id mediumint(9) NOT NULL,
            crime_date date NOT NULL,
            crime_time time NOT NULL,
            address varchar(255),
            zone_id mediumint(9),
            result varchar(50) NOT NULL DEFAULT 'unsolved',
            photo_url varchar(255),
            security_group_id mediumint(9) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // SQL for security groups table
        $sql_security_groups = "CREATE TABLE $table_security_groups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            logo_url varchar(255),
            phone_numbers text,
            email varchar(255),
            address text,
            website varchar(255),
            description text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // SQL for crime categories table
        $sql_crime_categories = "CREATE TABLE $table_crime_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            color varchar(7) NOT NULL DEFAULT '#000000',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // SQL for zones table
        $sql_zones = "CREATE TABLE $table_zones (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            parent_id mediumint(9),
            coordinates text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // SQL for report to security groups relationship table
        $sql_report_groups = "CREATE TABLE $table_report_groups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            report_id mediumint(9) NOT NULL,
            security_group_id mediumint(9) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY report_group (report_id, security_group_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_crime_reports);
        dbDelta($sql_security_groups);
        dbDelta($sql_crime_categories);
        dbDelta($sql_zones);
        dbDelta($sql_report_groups);

        // Insert default crime categories
        self::insert_default_categories($table_crime_categories);
        
        // Insert default zones
        self::insert_default_zones($table_zones);
        
        // Insert resident security group
        self::insert_default_security_group($table_security_groups);
    }

    /**
     * Insert default crime categories
     *
     * @param string $table Table name
     */
    private static function insert_default_categories($table) {
        global $wpdb;
        
        $categories = array(
            array('name' => 'Burglary', 'color' => '#FF0000'),
            array('name' => 'Theft', 'color' => '#FFA500'),
            array('name' => 'Assault', 'color' => '#800080'),
            array('name' => 'Vandalism', 'color' => '#008000'),
            array('name' => 'Suspicious Activity', 'color' => '#FFFF00')
        );
        
        foreach ($categories as $category) {
            $wpdb->insert(
                $table,
                array(
                    'name' => $category['name'],
                    'color' => $category['color']
                )
            );
        }
    }
    
    /**
     * Insert default zones for Sandbaai
     *
     * @param string $table Table name
     */
    private static function insert_default_zones($table) {
        global $wpdb;
        
        // Main quadrants
        $quadrants = array(
            array('name' => 'North Sandbaai', 'coordinates' => '{"type":"Polygon","coordinates":[[[0,0],[0.5,0],[0.5,0.5],[0,0.5],[0,0]]]}'),
            array('name' => 'East Sandbaai', 'coordinates' => '{"type":"Polygon","coordinates":[[[0.5,0],[1,0],[1,0.5],[0.5,0.5],[0.5,0]]]}'),
            array('name' => 'South Sandbaai', 'coordinates' => '{"type":"Polygon","coordinates":[[[0,0.5],[0.5,0.5],[0.5,1],[0,1],[0,0.5]]]}'),
            array('name' => 'West Sandbaai', 'coordinates' => '{"type":"Polygon","coordinates":[[[0.5,0.5],[1,0.5],[1,1],[0.5,1],[0.5,0.5]]]},'),
        );
        
        foreach ($quadrants as $quadrant) {
            $wpdb->insert(
                $table,
                array(
                    'name' => $quadrant['name'],
                    'coordinates' => $quadrant['coordinates']
                )
            );
        }
    }
    
    /**
     * Insert default resident security group
     *
     * @param string $table Table name
     */
    private static function insert_default_security_group($table) {
        global $wpdb;
        
        $wpdb->insert(
            $table,
            array(
                'title' => 'Resident',
                'description' => 'Default group for residents of Sandbaai'
            )
        );
    }
}
