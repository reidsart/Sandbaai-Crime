<?php
/**
 * Security Group Class
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Group class.
 *
 * Handles the creation, retrieval, updating, and deletion of security groups.
 *
 * @since      1.0.0
 */
class Sandbaai_Security_Group {

    /**
     * The ID of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $id    The ID of this security group.
     */
    private $id;

    /**
     * The title of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $title    The title of this security group.
     */
    private $title;

    /**
     * The logo of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $logo    The logo URL of this security group.
     */
    private $logo;

    /**
     * The contact number(s) of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $contact_numbers    The contact number(s) of this security group.
     */
    private $contact_numbers;

    /**
     * The email of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $email    The email of this security group.
     */
    private $email;

    /**
     * The address of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $address    The address of this security group.
     */
    private $address;

    /**
     * The website of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $website    The website of this security group.
     */
    private $website;

    /**
     * The description of this security group.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $description    The description of this security group.
     */
    private $description;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    int       $id             The ID of this security group.
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

        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        $group = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $this->id
            )
        );

        if ($group) {
            $this->title = $group->title;
            $this->logo = $group->logo;
            $this->contact_numbers = $group->contact_numbers;
            $this->email = $group->email;
            $this->address = $group->address;
            $this->website = $group->website;
            $this->description = $group->description;
        }
    }

    /**
     * Save the security group to the database.
     *
     * @since    1.0.0
     * @return   int|false    The ID of the saved security group, or false on failure.
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        $data = array(
            'title' => $this->title,
            'logo' => $this->logo,
            'contact_numbers' => $this->contact_numbers,
            'email' => $this->email,
            'address' => $this->address,
            'website' => $this->website,
            'description' => $this->description,
            'updated_at' => current_time('mysql'),
        );
        
        $format = array(
            '%s', // title
            '%s', // logo
            '%s', // contact_numbers
            '%s', // email
            '%s', // address
            '%s', // website
            '%s', // description
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
            
            return $this->id;
        }
        
        // Insert new record
        $data['created_at'] = current_time('mysql');
        $format[] = '%s'; // created_at
        
        $wpdb->insert($table_name, $data, $format);
        $this->id = $wpdb->insert_id;
        
        return $this->id;
    }

    /**
     * Delete this security group.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function delete() {
        global $wpdb;
        
        if (empty($this->id)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        // Delete security group
        $result = $wpdb->delete(
            $table_name,
            array('id' => $this->id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Get security groups with optional filtering.
     *
     * @since    1.0.0
     * @param    array    $args    The filter arguments.
     * @return   array             The security group objects.
     */
    public static function get_security_groups($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        $query = "SELECT * FROM $table_name";
        
        // Add ORDER BY clause
        $query .= " ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        
        // Add LIMIT clause
        $query .= " LIMIT %d OFFSET %d";
        
        // Prepare the query
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        // Get results
        $results = $wpdb->get_results($query);
        
        // Convert to objects
        $security_groups = array();
        foreach ($results as $result) {
            $security_group = new self($result->id);
            $security_groups[] = $security_group;
        }
        
        return $security_groups;
    }

    /**
     * Count security groups.
     *
     * @since    1.0.0
     * @return   int     The number of security groups.
     */
    public static function count_security_groups() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    /**
     * Get a list of security groups for select fields.
     *
     * @since    1.0.0
     * @return   array    Array of security group IDs and titles.
     */
    public static function get_security_groups_for_select() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_security_groups';
        
        $query = "SELECT id, title FROM $table_name ORDER BY title ASC";
        
        $results = $wpdb->get_results($query);
        
        $options = array();
        foreach ($results as $result) {
            $options[$result->id] = $result->title;
        }
        
        return $options;
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
    
    public function get_logo() {
        return $this->logo;
    }
    
    public function set_logo($logo) {
        $this->logo = esc_url_raw($logo);
    }
    
    public function get_contact_numbers() {
        return $this->contact_numbers;
    }
    
    public function set_contact_numbers($contact_numbers) {
        $this->contact_numbers = sanitize_text_field($contact_numbers);
    }
    
    public function get_email() {
        return $this->email;
    }
    
    public function set_email($email) {
        $this->email = sanitize_email($email);
    }
    
    public function get_address() {
        return $this->address;
    }
    
    public function set_address($address) {
        $this->address = sanitize_textarea_field($address);
    }
    
    public function get_website() {
        return $this->website;
    }
    
    public function set_website($website) {
        $this->website = esc_url_raw($website);
    }
    
    public function get_description() {
        return $this->description;
    }
    
    public function set_description($description) {
        $this->description = sanitize_textarea_field($description);
    }
    
    /**
     * Upload and set a logo image.
     *
     * @since    1.0.0
     * @param    array    $file    The file data.
     * @return   bool              True on success, false on failure.
     */
    public function upload_logo($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if ($uploaded_file && !isset($uploaded_file['error'])) {
            $this->logo = $uploaded_file['url'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Assign users to this security group.
     *
     * @since    1.0.0
     * @param    array    $user_ids    Array of user IDs.
     * @return   bool                  True on success, false on failure.
     */
    public function assign_users($user_ids) {
        if (empty($this->id)) {
            return false;
        }
        
        foreach ($user_ids as $user_id) {
            update_user_meta($user_id, 'sandbaai_security_group', $this->id);
        }
        
        return true;
    }
    
    /**
     * Get users assigned to this security group.
     *
     * @since    1.0.0
     * @return   array    Array of user objects.
     */
    public function get_users() {
        if (empty($this->id)) {
            return array();
        }
        
        $args = array(
            'meta_key' => 'sandbaai_security_group',
            'meta_value' => $this->id,
            'fields' => array('ID', 'display_name', 'user_email'),
        );
        
        return get_users($args);
    }
}