<?php
/**
 * Security Group Management
 * 
 * Handles creation and management of security groups
 * 
 * @package Sandbaai_Crime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Sandbaai_Crime_Security_Groups {
    
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register custom post type
        add_action('init', array($this, 'register_post_type'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post meta
        add_action('save_post_security_group', array($this, 'save_meta_box_data'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // User profile fields
        add_action('show_user_profile', array($this, 'add_security_group_field'));
        add_action('edit_user_profile', array($this, 'add_security_group_field'));
        add_action('personal_options_update', array($this, 'save_security_group_field'));
        add_action('edit_user_profile_update', array($this, 'save_security_group_field'));
    }

    /**
     * Register security group post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Security Groups', 'Post Type General Name', 'sandbaai-crime'),
            'singular_name'         => _x('Security Group', 'Post Type Singular Name', 'sandbaai-crime'),
            'menu_name'             => __('Security Groups', 'sandbaai-crime'),
            'name_admin_bar'        => __('Security Group', 'sandbaai-crime'),
            'archives'              => __('Security Group Archives', 'sandbaai-crime'),
            'attributes'            => __('Security Group Attributes', 'sandbaai-crime'),
            'all_items'             => __('All Security Groups', 'sandbaai-crime'),
            'add_new_item'          => __('Add New Security Group', 'sandbaai-crime'),
            'add_new'               => __('Add New', 'sandbaai-crime'),
            'new_item'              => __('New Security Group', 'sandbaai-crime'),
            'edit_item'             => __('Edit Security Group', 'sandbaai-crime'),
            'update_item'           => __('Update Security Group', 'sandbaai-crime'),
            'view_item'             => __('View Security Group', 'sandbaai-crime'),
            'view_items'            => __('View Security Groups', 'sandbaai-crime'),
            'search_items'          => __('Search Security Group', 'sandbaai-crime'),
            'not_found'             => __('Not found', 'sandbaai-crime'),
            'not_found_in_trash'    => __('Not found in Trash', 'sandbaai-crime'),
        );
        
        $args = array(
            'label'                 => __('Security Group', 'sandbaai-crime'),
            'description'           => __('Security groups in Sandbaai', 'sandbaai-crime'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // Will be added under custom menu
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-shield',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
        );
        
        register_post_type('security_group', $args);
    }

    /**
     * Add meta boxes to security group post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'security_group_details',
            __('Security Group Details', 'sandbaai-crime'),
            array($this, 'render_details_meta_box'),
            'security_group',
            'normal',
            'high'
        );
        
        add_meta_box(
            'security_group_zones',
            __('Responsible Zones', 'sandbaai-crime'),
            array($this, 'render_zones_meta_box'),
            'security_group',
            'normal',
            'high'
        );
    }

    /**
     * Render details meta box
     */
    public function render_details_meta_box($post) {
        wp_nonce_field('security_group_meta_box', 'security_group_meta_box_nonce');
        
        $primary_contact_number = get_post_meta($post->ID, '_primary_contact_number', true);
        $additional_contact_numbers = get_post_meta($post->ID, '_additional_contact_numbers', true);
        $email = get_post_meta($post->ID, '_email', true);
        $address = get_post_meta($post->ID, '_address', true);
        $website = get_post_meta($post->ID, '_website', true);
        
        if (!is_array($additional_contact_numbers)) {
            $additional_contact_numbers = array('');
        }
        ?>
        <p>
            <label for="primary_contact_number"><?php _e('Primary Contact Number:', 'sandbaai-crime'); ?></label><br>
            <input type="text" id="primary_contact_number" name="primary_contact_number" value="<?php echo esc_attr($primary_contact_number); ?>" class="regular-text" />
        </p>
        
        <div id="additional_numbers_container">
            <p><strong><?php _e('Additional Contact Numbers:', 'sandbaai-crime'); ?></strong></p>
            <?php foreach ($additional_contact_numbers as $index => $number) : ?>
                <p class="additional-number">
                    <input type="text" name="additional_contact_numbers[]" value="<?php echo esc_attr($number); ?>" class="regular-text" />
                    <?php if ($index === 0) : ?>
                        <button type="button" class="add-number button"><?php _e('Add Another', 'sandbaai-crime'); ?></button>
                    <?php else : ?>
                        <button type="button" class="remove-number button"><?php _e('Remove', 'sandbaai-crime'); ?></button>
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        </div>
        
        <p>
            <label for="email"><?php _e('Email Address:', 'sandbaai-crime'); ?></label><br>
            <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" />
        </p>
        
        <p>
            <label for="address"><?php _e('Physical Address:', 'sandbaai-crime'); ?></label><br>
            <textarea id="address" name="address" rows="3" class="large-text"><?php echo esc_textarea($address); ?></textarea>
        </p>
        
        <p>
            <label for="website"><?php _e('Website:', 'sandbaai-crime'); ?></label><br>
            <input type="url" id="website" name="website" value="<?php echo esc_url($website); ?>" class="regular-text" />
        </p>
        
        <script>
        jQuery(document).ready(function($) {
            $('#additional_numbers_container').on('click', '.add-number', function() {
                var newField = $('<p class="additional-number">' +
                    '<input type="text" name="additional_contact_numbers[]" value="" class="regular-text" />' +
                    '<button type="button" class="remove-number button"><?php _e('Remove', 'sandbaai-crime'); ?></button>' +
                    '</p>');
                $('#additional_numbers_container').append(newField);
            });
            
            $('#additional_numbers_container').on('click', '.remove-number', function() {
                $(this).parent('.additional-number').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Render zones meta box
     */
    public function render_zones_meta_box($post) {
        $responsible_zones = get_post_meta($post->ID, '_responsible_zones', true);
        
        if (!is_array($responsible_zones)) {
            $responsible_zones = array();
        }
        
        // Get all available zones
        $zones = $this->get_all_zones();
        ?>
        <p><?php _e('Select the zones this security group is responsible for:', 'sandbaai-crime'); ?></p>
        
        <div class="zone-selections">
            <?php foreach ($zones as $zone_id => $zone_name) : ?>
                <p>
                    <label>
                        <input type="checkbox" name="responsible_zones[]" value="<?php echo esc_attr($zone_id); ?>" 
                            <?php checked(in_array($zone_id, $responsible_zones)); ?> />
                        <?php echo esc_html($zone_name); ?>
                    </label>
                </p>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Get all available zones in Sandbaai
     */
    private function get_all_zones() {
        // This would ideally come from a zone management system
        // For now, we'll hardcode the 4 quadrants with subzones
        return array(
            'north-east' => __('North East', 'sandbaai-crime'),
            'north-west' => __('North West', 'sandbaai-crime'),
            'south-east' => __('South East', 'sandbaai-crime'),
            'south-west' => __('South West', 'sandbaai-crime'),
            'central' => __('Central', 'sandbaai-crime'),
            'beach-front' => __('Beach Front', 'sandbaai-crime'),
            'mountain-side' => __('Mountain Side', 'sandbaai-crime'),
            'industrial' => __('Industrial Area', 'sandbaai-crime'),
        );
    }

    /**
     * Save meta box data
     */
    public function save_meta_box_data($post_id) {
        // Check if nonce is set
        if (!isset($_POST['security_group_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['security_group_meta_box_nonce'], 'security_group_meta_box')) {
            return;
        }
        
        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save details
        if (isset($_POST['primary_contact_number'])) {
            update_post_meta($post_id, '_primary_contact_number', sanitize_text_field($_POST['primary_contact_number']));
        }
        
        if (isset($_POST['additional_contact_numbers']) && is_array($_POST['additional_contact_numbers'])) {
            $numbers = array_map('sanitize_text_field', array_filter($_POST['additional_contact_numbers']));
            update_post_meta($post_id, '_additional_contact_numbers', $numbers);
        }
        
        if (isset($_POST['email'])) {
            update_post_meta($post_id, '_email', sanitize_email($_POST['email']));
        }
        
        if (isset($_POST['address'])) {
            update_post_meta($post_id, '_address', sanitize_textarea_field($_POST['address']));
        }
        
        if (isset($_POST['website'])) {
            update_post_meta($post_id, '_website', esc_url_raw($_POST['website']));
        }
        
        // Save zones
        if (isset($_POST['responsible_zones']) && is_array($_POST['responsible_zones'])) {
            update_post_meta($post_id, '_responsible_zones', array_map('sanitize_key', $_POST['responsible_zones']));
        } else {
            update_post_meta($post_id, '_responsible_zones', array());
        }
    }
    
    /**
     * Add security groups admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sandbaai-crime', // Parent slug
            __('Security Groups', 'sandbaai-crime'),
            __('Security Groups', 'sandbaai-crime'),
            'manage_options',
            'edit.php?post_type=security_group'
        );
    }
    
    /**
     * Add security group field to user profile
     */
    public function add_security_group_field($user) {
        $security_group_id = get_user_meta($user->ID, '_security_group_id', true);
        $security_groups = get_posts(array(
            'post_type' => 'security_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        <h3><?php _e('Security Group', 'sandbaai-crime'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="security_group_id"><?php _e('Assigned Security Group', 'sandbaai-crime'); ?></label></th>
                <td>
                    <select name="security_group_id" id="security_group_id">
                        <option value=""><?php _e('None', 'sandbaai-crime'); ?></option>
                        <?php foreach ($security_groups as $group) : ?>
                            <option value="<?php echo esc_attr($group->ID); ?>" <?php selected($security_group_id, $group->ID); ?>>
                                <?php echo esc_html($group->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select the security group this user belongs to.', 'sandbaai-crime'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save security group field on user profile
     */
    public function save_security_group_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST['security_group_id'])) {
            update_user_meta($user_id, '_security_group_id', sanitize_text_field($_POST['security_group_id']));
        }
    }
    
    /**
     * Get users assigned to a security group
     */
    public function get_security_group_users($group_id) {
        $users = get_users(array(
            'meta_key' => '_security_group_id',
            'meta_value' => $group_id,
        ));
        
        return $users;
    }
    
    /**
     * Check if user belongs to a security group
     */
    public function user_belongs_to_group($user_id, $group_id) {
        $user_group_id = get_user_meta($user_id, '_security_group_id', true);
        return ($user_group_id == $group_id);
    }
    
    /**
     * Get security group for a user
     */
    public function get_user_security_group($user_id) {
        $group_id = get_user_meta($user_id, '_security_group_id', true);
        
        if (!empty($group_id)) {
            return get_post($group_id);
        }
        
        return false;
    }
    
    /**
     * Get all security groups
     */
    public function get_all_security_groups() {
        return get_posts(array(
            'post_type' => 'security_group',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
    }
}

// Initialize the security groups class
function sandbaai_crime_security_groups() {
    return Sandbaai_Crime_Security_Groups::get_instance();
}

// Start the module
sandbaai_crime_security_groups();