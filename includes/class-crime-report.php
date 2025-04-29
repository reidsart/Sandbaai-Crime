<?php
/**
 * Crime Report Class
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crime Report class.
 *
 * Handles the creation, retrieval, updating, and deletion of crime reports.
 *
 * @since      1.0.0
 */
class Sandbaai_Crime_Report {

    /**
     * The ID of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $id    The ID of this crime report.
     */
    private $id;

    /**
     * The title of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $title    The title of this crime report.
     */
    private $title;

    /**
     * The description of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $description    The description of this crime report.
     */
    private $description;

    /**
     * The category of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $category    The category of this crime report.
     */
    private $category;

    /**
     * The date and time of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $date_time    The date and time of this crime report.
     */
    private $date_time;

    /**
     * The location of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $location    The location of this crime report.
     */
    private $location;

    /**
     * The zone of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $zone    The zone of this crime report.
     */
    private $zone;

    /**
     * The status of this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $status    The status of this crime report (pending, approved, rejected).
     */
    private $status;

    /**
     * The result of this crime report (e.g., caught, unresolved).
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $result    The result of this crime report.
     */
    private $result;

    /**
     * The security groups involved in this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $security_groups    The security groups involved in this crime report.
     */
    private $security_groups;

    /**
     * The photos attached to this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $photos    The photos attached to this crime report.
     */
    private $photos;

    /**
     * The user ID of the reporter.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $reporter_id    The user ID of the reporter.
     */
    private $reporter_id;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    int       $id             The ID of this crime report.
     */
    public function __construct($id = 0) {
        if ($id > 0) {
            $this->id = $id;
            $this->populate();
        }
    }

    /**
     * Populate the object with data from the database.
     *
     * @since    1.0.0
     * @access   private
     */
    private function populate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        
        $report = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $this->id
            )
        );

        if ($report) {
            $this->title = $report->title;
            $this->description = $report->description;
            $this->category = $report->category;
            $this->date_time = $report->date_time;
            $this->location = $report->location;
            $this->zone = $report->zone;
            $this->status = $report->status;
            $this->result = $report->result;
            $this->reporter_id = $report->reporter_id;
            
            // Get security groups
            $this->security_groups = $this->get_security_groups();
            
            // Get photos
            $this->photos = $this->get_photos();
        }
    }

    /**
     * Get security groups related to this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @return   array    The security groups involved in this crime report.
     */
    private function get_security_groups() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_report_security_groups';
        
        $security_groups = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT security_group_id FROM $table_name WHERE crime_report_id = %d",
                $this->id
            )
        );
        
        return $security_groups;
    }

    /**
     * Get photos attached to this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @return   array    The photos attached to this crime report.
     */
    private function get_photos() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_report_photos';
        
        $photos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, file_path FROM $table_name WHERE crime_report_id = %d",
                $this->id
            )
        );
        
        return $photos;
    }

    /**
     * Save the crime report to the database.
     *
     * @since    1.0.0
     * @return   int|false    The ID of the saved report, or false on failure.
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        
        $data = array(
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'date_time' => $this->date_time,
            'location' => $this->location,
            'zone' => $this->zone,
            'status' => $this->status ? $this->status : 'pending',
            'result' => $this->result,
            'reporter_id' => $this->reporter_id ? $this->reporter_id : get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        $format = array(
            '%s', // title
            '%s', // description
            '%s', // category
            '%s', // date_time
            '%s', // location
            '%s', // zone
            '%s', // status
            '%s', // result
            '%d', // reporter_id
            '%s', // created_at
            '%s', // updated_at
        );
        
        // Update existing record
        if (!empty($this->id)) {
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $this->id),
                $format,
                array('%d')
            );
            
            // Save security groups
            $this->save_security_groups();
            
            // Save photos
            $this->save_photos();
            
            return $this->id;
        }
        
        // Insert new record
        $wpdb->insert($table_name, $data, $format);
        $this->id = $wpdb->insert_id;
        
        if ($this->id) {
            // Save security groups
            $this->save_security_groups();
            
            // Save photos
            $this->save_photos();
            
            // Send notifications
            $this->send_notifications();
            
            return $this->id;
        }
        
        return false;
    }

    /**
     * Save security groups relation to this crime report.
     *
     * @since    1.0.0
     * @access   private
     */
    private function save_security_groups() {
        global $wpdb;
        
        if (empty($this->security_groups)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_report_security_groups';
        
        // Delete existing relations
        $wpdb->delete(
            $table_name,
            array('crime_report_id' => $this->id),
            array('%d')
        );
        
        // Insert new relations
        foreach ($this->security_groups as $security_group_id) {
            $wpdb->insert(
                $table_name,
                array(
                    'crime_report_id' => $this->id,
                    'security_group_id' => $security_group_id,
                ),
                array('%d', '%d')
            );
        }
    }

    /**
     * Save photos related to this crime report.
     *
     * @since    1.0.0
     * @access   private
     */
    private function save_photos() {
        global $wpdb;
        
        if (empty($this->photos)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_report_photos';
        
        // Process photos
        foreach ($this->photos as $photo) {
            if (is_array($photo) && isset($photo['tmp_name'])) {
                // This is a new upload
                $upload = $this->upload_photo($photo);
                
                if ($upload) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'crime_report_id' => $this->id,
                            'file_path' => $upload['file'],
                        ),
                        array('%d', '%s')
                    );
                }
            }
        }
    }

    /**
     * Upload a photo to the WordPress media library.
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $file    The file data.
     * @return   array|false       The uploaded file data, or false on failure.
     */
    private function upload_photo($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        
        return wp_handle_upload($file, $upload_overrides);
    }

    /**
     * Send notifications about this crime report.
     *
     * @since    1.0.0
     * @access   private
     */
    private function send_notifications() {
        // Call WhatsApp notification class
        if (class_exists('Sandbaai_WhatsApp_Notifications')) {
            $whatsapp = new Sandbaai_WhatsApp_Notifications();
            $whatsapp->send_crime_report_notification($this);
        }
        
        // Email notifications
        $this->send_email_notifications();
    }

    /**
     * Send email notifications about this crime report.
     *
     * @since    1.0.0
     * @access   private
     */
    private function send_email_notifications() {
        $subject = 'New Crime Report: ' . $this->title;
        
        $message = "A new crime has been reported.\n\n";
        $message .= "Title: " . $this->title . "\n";
        $message .= "Category: " . $this->category . "\n";
        $message .= "Date/Time: " . $this->date_time . "\n";
        $message .= "Location: " . $this->location . "\n";
        $message .= "Zone: " . $this->zone . "\n";
        $message .= "Description: " . $this->description . "\n\n";
        $message .= "View this report in the admin panel: " . admin_url('admin.php?page=sandbaai-crime-reports&action=view&id=' . $this->id);
        
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Send email to admin
        wp_mail($admin_email, $subject, $message);
        
        // Send email to security groups
        $this->notify_security_groups($subject, $message);
    }

    /**
     * Notify security groups about this crime report.
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $subject    The email subject.
     * @param    string    $message    The email message.
     */
    private function notify_security_groups($subject, $message) {
        global $wpdb;
        
        if (empty($this->security_groups)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        foreach ($this->security_groups as $security_group_id) {
            $security_group = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT email FROM $table_name WHERE id = %d",
                    $security_group_id
                )
            );
            
            if ($security_group && !empty($security_group->email)) {
                wp_mail($security_group->email, $subject, $message);
            }
        }
    }

    /**
     * Delete this crime report.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function delete() {
        global $wpdb;
        
        if (empty($this->id)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        
        // Delete security groups relations
        $wpdb->delete(
            $wpdb->prefix . 'sandbaai_crime_report_security_groups',
            array('crime_report_id' => $this->id),
            array('%d')
        );
        
        // Delete photos
        $wpdb->delete(
            $wpdb->prefix . 'sandbaai_crime_report_photos',
            array('crime_report_id' => $this->id),
            array('%d')
        );
        
        // Delete crime report
        $result = $wpdb->delete(
            $table_name,
            array('id' => $this->id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Approve this crime report.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function approve() {
        $this->status = 'approved';
        return $this->save();
    }

    /**
     * Reject this crime report.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function reject() {
        $this->status = 'rejected';
        return $this->save();
    }

    /**
     * Get crime reports with optional filtering.
     *
     * @since    1.0.0
     * @param    array    $args    The filter arguments.
     * @return   array             The crime report objects.
     */
    public static function get_crime_reports($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'category' => '',
            'zone' => '',
            'status' => '',
            'result' => '',
            'start_date' => '',
            'end_date' => '',
            'security_group_id' => 0,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'date_time',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $sg_table_name = $wpdb->prefix . 'sandbaai_crime_report_security_groups';
        
        $query = "SELECT r.* FROM $table_name r";
        $where = array();
        $params = array();
        
        // Join security groups if needed
        if (!empty($args['security_group_id'])) {
            $query .= " INNER JOIN $sg_table_name sg ON r.id = sg.crime_report_id";
            $where[] = "sg.security_group_id = %d";
            $params[] = $args['security_group_id'];
        }
        
        // Add filters
        if (!empty($args['category'])) {
            $where[] = "r.category = %s";
            $params[] = $args['category'];
        }
        
        if (!empty($args['zone'])) {
            $where[] = "r.zone = %s";
            $params[] = $args['zone'];
        }
        
        if (!empty($args['status'])) {
            $where[] = "r.status = %s";
            $params[] = $args['status'];
        }
        
        if (!empty($args['result'])) {
            $where[] = "r.result = %s";
            $params[] = $args['result'];
        }
        
        if (!empty($args['start_date'])) {
            $where[] = "r.date_time >= %s";
            $params[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = "r.date_time <= %s";
            $params[] = $args['end_date'];
        }
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // Add ORDER BY clause
        $query .= " ORDER BY r." . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        
        // Add LIMIT clause
        $query .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        // Prepare the query
        $query = $wpdb->prepare($query, $params);
        
        // Get results
        $results = $wpdb->get_results($query);
        
        // Convert to objects
        $crime_reports = array();
        foreach ($results as $result) {
            $crime_report = new self($result->id);
            $crime_reports[] = $crime_report;
        }
        
        return $crime_reports;
    }

    /**
     * Count crime reports with optional filtering.
     *
     * @since    1.0.0
     * @param    array    $args    The filter arguments.
     * @return   int               The number of crime reports.
     */
    public static function count_crime_reports($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'category' => '',
            'zone' => '',
            'status' => '',
            'result' => '',
            'start_date' => '',
            'end_date' => '',
            'security_group_id' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $sg_table_name = $wpdb->prefix . 'sandbaai_crime_report_security_groups';
        
        $query = "SELECT COUNT(DISTINCT r.id) FROM $table_name r";
        $where = array();
        $params = array();
        
        // Join security groups if needed
        if (!empty($args['security_group_id'])) {
            $query .= " INNER JOIN $sg_table_name sg ON r.id = sg.crime_report_id";
            $where[] = "sg.security_group_id = %d";
            $params[] = $args['security_group_id'];
        }
        
        // Add filters
        if (!empty($args['category'])) {
            $where[] = "r.category = %s";
            $params[] = $args['category'];
        }
        
        if (!empty($args['zone'])) {
            $where[] = "r.zone = %s";
            $params[] = $args['zone'];
        }
        
        if (!empty($args['status'])) {
            $where[] = "r.status = %s";
            $params[] = $args['status'];
        }
        
        if (!empty($args['result'])) {
            $where[] = "r.result = %s";
            $params[] = $args['result'];
        }
        
        if (!empty($args['start_date'])) {
            $where[] = "r.date_time >= %s";
            $params[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = "r.date_time <= %s";
            $params[] = $args['end_date'];
        }
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // Prepare the query
        $query = $wpdb->prepare($query, $params);
        
        // Get result
        return $wpdb->get_var($query);
    }

    /**
     * Get crime statistics grouped by a field.
     *
     * @since    1.0.0
     * @param    string    $group_by    The field to group by.
     * @param    array     $args        The filter arguments.
     * @return   array                  The crime statistics.
     */
    public static function get_crime_statistics($group_by, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'category' => '',
            'zone' => '',
            'status' => '',
            'result' => '',
            'start_date' => '',
            'end_date' => '',
            'security_group_id' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $sg_table_name = $wpdb->prefix . 'sandbaai_crime_report_security_groups';
        
        // Validate group by field
        $allowed_group_by = array('category', 'zone', 'status', 'result', 'DATE(date_time)');
        if (!in_array($group_by, $allowed_group_by)) {
            return array();
        }
        
        $query = "SELECT $group_by as label, COUNT(*) as count FROM $table_name r";
        $where = array();
        $params = array();
        
        // Join security groups if needed
        if (!empty($args['security_group_id'])) {
            $query .= " INNER JOIN $sg_table_name sg ON r.id = sg.crime_report_id";
            $where[] = "sg.security_group_id = %d";
            $params[] = $args['security_group_id'];
        }
        
        // Add filters
        if (!empty($args['category'])) {
            $where[] = "r.category = %s";
            $params[] = $args['category'];
        }
        
        if (!empty($args['zone'])) {
            $where[] = "r.zone = %s";
            $params[] = $args['zone'];
        }
        
        if (!empty($args['status'])) {
            $where[] = "r.status = %s";
            $params[] = $args['status'];
        }
        
        if (!empty($args['result'])) {
            $where[] = "r.result = %s";
            $params[] = $args['result'];
        }
        
        if (!empty($args['start_date'])) {
            $where[] = "r.date_time >= %s";
            $params[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = "r.date_time <= %s";
            $params[] = $args['end_date'];
        }
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // Add GROUP BY clause
        $query .= " GROUP BY $group_by";
        
        // Prepare the query
        $query = $wpdb->prepare($query, $params);
        
        // Get results
        return $wpdb->get_results($query);
    }

    /**
     * Get and set methods for properties
     */
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_title() {
        return $this->title;
    }
    
    public function set_title($title) {
        $this->title = sanitize_text_field($title);
    }
    
    public function get_description() {
        return $this->description;
    }
    
    public function set_description($description) {
        $this->description = sanitize_textarea_field($description);
    }
    
    public function get_category() {
        return $this->category;
    }
    
    public function set_category($category) {
        $this->category = sanitize_text_field($category);
    }
    
    public function get_date_time() {
        return $this->date_time;
    }
    
    public function set_date_time($date_time) {
        $this->date_time = sanitize_text_field($date_time);
    }
    
    public function get_location() {
        return $this->location;
    }
    
    public function set_location($location) {
        $this->location = sanitize_text_field($location);
    }
    
    public function get_zone() {
        return $this->zone;
    }
    
    public function set_zone($zone) {
        $this->zone = sanitize_text_field($zone);
    }
    
    public function get_status() {
        return $this->status;
    }
    
    public function set_status($status) {
        $this->status = sanitize_text_field($status);
    }
    
    public function get_result() {
        return $this->result;
    }
    
    public function set_result($result) {
        $this->result = sanitize_text_field($result);
    }
    
    public function get_security_groups() {
        return $this->security_groups;
    }
    
    public function set_security_groups($security_groups) {
        $this->security_groups = array_map('intval', (array) $security_groups);
    }
    
    public function get_photos() {
        return $this->photos;
    }
    
    public function set_photos($photos) {
        $this->photos = (array) $photos;
    }
    
    public function get_reporter_id() {
        return $this->reporter_id;
    }
    
    public function set_reporter_id($reporter_id) {
        $this->reporter_id = intval($reporter_id);
    }
}
