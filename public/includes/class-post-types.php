<?php
/**
 * Class for registering custom post types
 *
 * @since      1.0.0
 * @package    Sandbaai_Crime
 */

class Sandbaai_Crime_Post_Types {

    /**
     * Register Crime Report post type
     */
    public function register_crime_report_post_type() {
        $labels = array(
            'name'                  => _x('Crime Reports', 'Post type general name', 'sandbaai-crime'),
            'singular_name'         => _x('Crime Report', 'Post type singular name', 'sandbaai-crime'),
            'menu_name'             => _x('Crime Reports', 'Admin Menu text', 'sandbaai-crime'),
            'name_admin_bar'        => _x('Crime Report', 'Add New on Toolbar', 'sandbaai-crime'),
            'add_new'               => __('Add New', 'sandbaai-crime'),
            'add_new_item'          => __('Add New Crime Report', 'sandbaai-crime'),
            'new_item'              => __('New Crime Report', 'sandbaai-crime'),
            'edit_item'             => __('Edit Crime Report', 'sandbaai-crime'),
            'view_item'             => __('View Crime Report', 'sandbaai-crime'),
            'all_items'             => __('All Crime Reports', 'sandbaai-crime'),
            'search_items'          => __('Search Crime Reports', 'sandbaai-crime'),
            'not_found'             => __('No crime reports found.', 'sandbaai-crime'),
            'not_found_in_trash'    => __('No crime reports found in Trash.', 'sandbaai-crime'),
            'featured_image'        => _x('Crime Report Image', 'Overrides the "Featured Image" phrase', 'sandbaai-crime'),
            'set_featured_image'    => _x('Set crime report image', 'Overrides the "Set featured image" phrase', 'sandbaai-crime'),
            'remove_featured_image' => _x('Remove crime report image', 'Overrides the "Remove featured image" phrase', 'sandbaai-crime'),
            'use_featured_image'    => _x('Use as crime report image', 'Overrides the "Use as featured image" phrase', 'sandbaai-crime'),
            'archives'              => _x('Crime Report archives', 'The post type archive label used in nav menus', 'sandbaai-crime'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'sandbaai-crime',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'crime-report'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'custom-fields'),
            'show_in_rest'       => true,
        );
        
        register_post_type('crime_report', $args);
        
        // Register taxonomies
        $this->register_crime_category_taxonomy();
    }
    
    /**
     * Register Security Group post type
     */
    public function register_security_group_post_type() {
        $labels = array(
            'name'                  => _x('Security Groups', 'Post type general name', 'sandbaai-crime'),
            'singular_name'         => _x('Security Group', 'Post type singular name', 'sandbaai-crime'),
            'menu_name'             => _x('Security Groups', 'Admin Menu text', 'sandbaai-crime'),
            'name_admin_bar'        => _x('Security Group', 'Add New on Toolbar', 'sandbaai-crime'),
            'add_new'               => __('Add New', 'sandbaai-crime'),
            'add_new_item'          => __('Add New Security Group', 'sandbaai-crime'),
            'new_item'              => __('New Security Group', 'sandbaai-crime'),
            'edit_item'             => __('Edit Security Group', 'sandbaai-crime'),
            'view_item'             => __('View Security Group', 'sandbaai-crime'),
            'all_items'             => __('All Security Groups', 'sandbaai-crime'),
            'search_items'          => __('Search Security Groups', 'sandbaai-crime'),
            'not_found'             => __('No security groups found.', 'sandbaai-crime'),
            'not_found_in_trash'    => __('No security groups found in Trash.', 'sandbaai-crime'),
            'featured_image'        => _x('Security Group Logo', 'Overrides the "Featured Image" phrase', 'sandbaai-crime'),
            'set_featured_image'    => _x('Set security group logo', 'Overrides the "Set featured image" phrase', 'sandbaai-crime'),
            'remove_featured_image' => _x('Remove security group logo', 'Overrides the "Remove featured image" phrase', 'sandbaai-crime'),
            'use_featured_image'    => _x('Use as security group logo', 'Overrides the "Use as featured image" phrase', 'sandbaai-crime'),
            'archives'              => _x('Security Group archives', 'The post type archive label used in nav menus', 'sandbaai-crime'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'sandbaai-crime',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'security-group'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'show_in_rest'       => true,
        );
        
        register_post_type('security_group', $args);
    }
    
    /**
     * Register Crime Category taxonomy
     */
    private function register_crime_category_taxonomy() {
        $labels = array(
            'name'              => _x('Crime Categories', 'taxonomy general name', 'sandbaai-crime'),
            'singular_name'     => _x('Crime Category', 'taxonomy singular name', 'sandbaai-crime'),
            'search_items'      => __('Search Crime Categories', 'sandbaai-crime'),
            'all_items'         => __('All Crime Categories', 'sandbaai-crime'),
            'parent_item'       => __('Parent Crime Category', 'sandbaai-crime'),
            'parent_item_colon' => __('Parent Crime Category:', 'sandbaai-crime'),
            'edit_item'         => __('Edit Crime Category', 'sandbaai-crime'),
            'update_item'       => __('Update Crime Category', 'sandbaai-crime'),
            'add_new_item'      => __('Add New Crime Category', 'sandbaai-crime'),
            'new_item_name'     => __('New Crime Category Name', 'sandbaai-crime'),
            'menu_name'         => __('Crime Categories', 'sandbaai-crime'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'crime-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('crime_category', array('crime_report'), $args);
        
        // Add default categories
        $this->add_default_crime_categories();
    }
    
    /**
     * Add default crime categories
     */
    private function add_default_crime_categories() {
        $default_categories = array(
            'Theft' => 'Theft of property',
            'Burglary' => 'Breaking and entering',
            'Assault' => 'Physical attack',
            'Robbery' => 'Theft with force or threat',
            'Vandalism' => 'Property damage',
            'Suspicious Activity' => 'Suspicious behavior or persons',
            'Other' => 'Other crime types'
        );
        
        foreach ($default_categories as $name => $description) {
            if (!term_exists($name, 'crime_category')) {
                wp_insert_term(
                    $name,
                    'crime_category',
                    array(
                        'description' => $description
                    )
                );
            }
        }
    }
}
