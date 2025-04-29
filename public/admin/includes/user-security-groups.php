<?php
/**
 * User Security Group Assignment Functionality
 *
 * This file contains functions for managing user assignments to security groups
 *
 * @link       https://sandbaai.com
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin/includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add user profile fields for security group assignment
 *
 * @param WP_User $user User object
 */
function sandbaai_add_security_group_field( $user ) {
    // Only show this field if the current user can edit users
    if ( ! current_user_can( 'edit_users' ) ) {
        return;
    }
    
    // Get all security groups
    $security_groups = get_posts( array(
        'post_type' => 'security_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    
    // Get user's current security group
    $current_group_id = get_user_meta( $user->ID, '_security_group_id', true );
    
    // Add a default "Resident" option if it doesn't exist
    $has_resident = false;
    foreach ( $security_groups as $group ) {
        if ( strtolower( $group->post_title ) === 'resident' ) {
            $has_resident = true;
            break;
        }
    }
    
    // If no security groups exist, show a message
    if ( empty( $security_groups ) && ! $has_resident ) {
        // Create a default "Resident" security group
        $resident_group = array(
            'post_title' => 'Resident',
            'post_content' => 'Default group for residents',
            'post_status' => 'publish',
            'post_type' => 'security_group'
        );
        
        $resident_id = wp_insert_post( $resident_group );
        
        if ( $resident_id ) {
            $security_groups = array( get_post( $resident_id ) );
            
            // Set as default for this user if they don't have a group
            if ( empty( $current_group_id ) ) {
                update_user_meta( $user->ID, '_security_group_id', $resident_id );
                $current_group_id = $resident_id;
            }
        }
    }
    
    // Display the security group field
    ?>
    <h3><?php _e( 'Security Group Assignment', 'sandbaai-crime' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="security_group_id"><?php _e( 'Security Group', 'sandbaai-crime' ); ?></label></th>
            <td>
                <?php wp_nonce_field( 'sandbaai_user_group_assignment', 'sandbaai_user_group_nonce' ); ?>
                <select name="security_group_id" id="security_group_id" class="security-group-select" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
                    <option value=""><?php _e( '— Select —', 'sandbaai-crime' ); ?></option>
                    <?php foreach ( $security_groups as $group ) : ?>
                        <option value="<?php echo esc_attr( $group->ID ); ?>" <?php selected( $current_group_id, $group->ID ); ?>>
                            <?php echo esc_html( $group->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e( 'Select the security group this user belongs to.', 'sandbaai-crime' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'sandbaai_add_security_group_field' );
add_action( 'edit_user_profile', 'sandbaai_add_security_group_field' );

/**
 * Save user profile field for security group
 *
 * @param int $user_id User ID
 * @return bool Whether the update was successful
 */
function sandbaai_save_security_group_field( $user_id ) {
    // Check if current user can edit users
    if ( ! current_user_can( 'edit_users' ) ) {
        return false;
    }
    
    // Verify nonce
    if ( ! isset( $_POST['sandbaai_user_group_nonce'] ) || ! wp_verify_nonce( $_POST['sandbaai_user_group_nonce'], 'sandbaai_user_group_assignment' ) ) {
        return false;
    }
    
    // Save security group
    if ( isset( $_POST['security_group_id'] ) ) {
        $group_id = sanitize_text_field( $_POST['security_group_id'] );
        update_user_meta( $user_id, '_security_group_id', $group_id );
    }
    
    return true;
}
add_action( 'personal_options_update', 'sandbaai_save_security_group_field' );
add_action( 'edit_user_profile_update', 'sandbaai_save_security_group_field' );

/**
 * AJAX handler for assigning a user to a security group
 */
function sandbaai_ajax_assign_security_group() {
    // Check nonce
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sandbaai_user_group_assignment' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }
    
    // Get parameters
    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    $group_id = isset( $_POST['group_id'] ) ? sanitize_text_field( $_POST['group_id'] ) : '';
    
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Invalid user ID.' ) );
    }
    
    // Update user meta
    update_user_meta( $user_id, '_security_group_id', $group_id );
    
    // Get group name for the response message
    $group_name = empty( $group_id ) ? 'None' : get_the_title( $group_id );
    
    wp_send_json_success( array( 
        'message' => sprintf( 'User successfully assigned to group: %s', $group_name ),
        'group_id' => $group_id,
        'group_name' => $group_name
    ) );
}
add_action( 'wp_ajax_sandbaai_assign_security_group', 'sandbaai_ajax_assign_security_group' );

/**
 * AJAX handler for bulk assigning users to a security group
 */
function sandbaai_ajax_bulk_assign_users() {
    // Check nonce
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sandbaai_group_users_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }
    
    // Get parameters
    $group_id = isset( $_POST['group_id'] ) ? sanitize_text_field( $_POST['group_id'] ) : '';
    $user_ids = isset( $_POST['user_ids'] ) ? (array) $_POST['user_ids'] : array();
    
    if ( empty( $group_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security group ID.' ) );
    }
    
    if ( empty( $user_ids ) ) {
        wp_send_json_error( array( 'message' => 'No users selected.' ) );
    }
    
    // Assign users to the group
    $success_count = 0;
    foreach ( $user_ids as $user_id ) {
        $user_id = intval( $user_id );
        if ( $user_id > 0 ) {
            update_user_meta( $user_id, '_security_group_id', $group_id );
            $success_count++;
        }
    }
    
    wp_send_json_success( array( 
        'message' => sprintf( '%d users successfully assigned to the group.', $success_count ),
        'count' => $success_count
    ) );
}
add_action( 'wp_ajax_sandbaai_bulk_assign_users', 'sandbaai_ajax_bulk_assign_users' );

/**
 * AJAX handler for removing a user from a security group
 */
function sandbaai_ajax_remove_user_from_group() {
    // Check nonce
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sandbaai_group_users_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }
    
    // Get parameters
    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    $group_id = isset( $_POST['group_id'] ) ? sanitize_text_field( $_POST['group_id'] ) : '';
    
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Invalid user ID.' ) );
    }
    
    if ( empty( $group_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security group ID.' ) );
    }
    
    // Check if the user is actually in this group
    $current_group_id = get_user_meta( $user_id, '_security_group_id', true );
    if ( $current_group_id !== $group_id ) {
        wp_send_json_error( array( 'message' => 'User is not assigned to this security group.' ) );
    }
    
    // Remove user from group
    delete_user_meta( $user_id, '_security_group_id' );
    
    wp_send_json_success( array( 
        'message' => 'User successfully removed from the group.'
    ) );
}
add_action( 'wp_ajax_sandbaai_remove_user_from_group', 'sandbaai_ajax_remove_user_from_group' );

/**
 * AJAX handler for getting assigned users for a security group
 */
function sandbaai_ajax_get_assigned_users() {
    // Check nonce
    if ( ! isset( $_GET['security'] ) || ! wp_verify_nonce( $_GET['security'], 'sandbaai_group_users_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }
    
    // Get parameters
    $group_id = isset( $_GET['group_id'] ) ? sanitize_text_field( $_GET['group_id'] ) : '';
    
    if ( empty( $group_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security group ID.' ) );
    }
    
    // Get users assigned to this group
    $assigned_users = get_users( array(
        'meta_key' => '_security_group_id',
        'meta_value' => $group_id,
        'orderby' => 'display_name',
        'order' => 'ASC'
    ) );
    
    $count = count( $assigned_users );
    $html = '';
    
    if ( $count > 0 ) {
        foreach ( $assigned_users as $user ) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html( $user->display_name ) . '</td>';
            $html .= '<td>' . esc_html( $user->user_email ) . '</td>';
            $html .= '<td>' . esc_html( $user->user_login ) . '</td>';
            $html .= '<td><a href="#" class="button button-small remove-user-from-group" data-user-id="' . esc_attr( $user->ID ) . '">Remove</a></td>';
            $html .= '</tr>';
        }
    } else {
        $html = '<tr><td colspan="4">No users assigned to this security group.</td></tr>';
    }
    
    wp_send_json_success( array( 
        'html' => $html,
        'count' => $count
    ) );
}
add_action( 'wp_ajax_sandbaai_get_assigned_users', 'sandbaai_ajax_get_assigned_users' );

/**
 * AJAX handler for searching users
 */
function sandbaai_ajax_search_users() {
    // Check nonce
    if ( ! isset( $_GET['security'] ) || ! wp_verify_nonce( $_GET['security'], 'sandbaai_search_users_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }
    
    // Get search parameters
    $search = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
    $page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
    $per_page = 20;
    
    if ( empty( $search ) ) {
        wp_send_json_success( array( 'users' => array(), 'more' => false ) );
    }
    
    // Prepare query args
    $args = array(
        'search' => '*' . $search . '*',
        'search_columns' => array(
            'user_login',
            'user_email',
            'user_nicename',
            'display_name'
        ),
        'orderby' => 'display_name',
        'order' => 'ASC',
        'number' => $per_page,
        'offset' => ( $page - 1 ) * $per_page,
        'fields' => array( 'ID', 'user_login', 'user_email', 'display_name' )
    );
    
    // Get total matching users for pagination
    $total_query = new WP_User_Query( array_merge( $args, array( 'count_total' => true, 'fields' => 'ID' ) ) );
    $total = $total_query->get_total();
    
    // Get current page of results
    $user_query = new WP_User_Query( $args );
    $users = array();
    
    if ( ! empty( $user_query->results ) ) {
        foreach ( $user_query->results as $user ) {
            $users[] = array(
                'id' => $user->ID,
                'text' => sprintf(
                    '%s (%s - %s)',
                    $user->display_name,
                    $user->user_login,
                    $user->user_email
                )
            );
        }
    }
    
    wp_send_json_success( array( 
        'users' => $users,
        'more' => ( $page * $per_page < $total )
    ) );
}
add_action( 'wp_ajax_sandbaai_search_users', 'sandbaai_ajax_search_users' );

/**
 * Add security group management page
 */
function sandbaai_add_security_group_users_page() {
    add_submenu_page(
        'sandbaai-security-groups',
        __( 'Manage Users', 'sandbaai-crime' ),
        __( 'Manage Users', 'sandbaai-crime' ),
        'edit_users',
        'sandbaai-security-group-users',
        'sandbaai_security_group_users_page'
    );
}
add_action( 'admin_menu', 'sandbaai_add_security_group_users_page', 20 );

/**
 * Display security group users management page
 */
function sandbaai_security_group_users_page() {
    // Check permissions
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'sandbaai-crime' ) );
    }
    
    // Get all security groups
    $security_groups = get_posts( array(
        'post_type' => 'security_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    
    // Get current group (if any)
    $current_group_id = isset( $_GET['group_id'] ) ? sanitize_text_field( $_GET['group_id'] ) : '';
    $current_group = ! empty( $current_group_id ) ? get_post( $current_group_id ) : null;
    
    // Get users assigned to current group
    $assigned_users = array();
    $assigned_count = 0;
    
    if ( $current_group ) {
        $assigned_users = get_users( array(
            'meta_key' => '_security_group_id',
            'meta_value' => $current_group_id,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ) );
        $assigned_count = count( $assigned_users );
    }
    
    // Display the page
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e( 'Security Group User Management', 'sandbaai-crime' ); ?></h1>
        <hr class="wp-header-end">
        
        <?php if ( empty( $security_groups ) ) : ?>
            <div class="notice notice-warning">
                <p><?php _e( 'No security groups found. Please create a security group first.', 'sandbaai-crime' ); ?></p>
                <p><a href="<?php echo admin_url( 'admin.php?page=sandbaai-security-groups' ); ?>" class="button"><?php _e( 'Manage Security Groups', 'sandbaai-crime' ); ?></a></p>
            </div>
        <?php else : ?>
            <div class="security-group-select-container">
                <form method="get">
                    <input type="hidden" name="page" value="sandbaai-security-group-users">
                    <select name="group_id" id="security-group-selector">
                        <option value=""><?php _e( '— Select Security Group —', 'sandbaai-crime' ); ?></option>
                        <?php foreach ( $security_groups as $group ) : ?>
                            <option value="<?php echo esc_attr( $group->ID ); ?>" <?php selected( $current_group_id, $group->ID ); ?>>
                                <?php echo esc_html( $group->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="<?php _e( 'Select', 'sandbaai-crime' ); ?>">
                </form>
            </div>
            
            <?php if ( $current_group ) : ?>
                <div class="security-group-users">
                    <h2><?php printf( __( 'Users in %s', 'sandbaai-crime' ), esc_html( $current_group->post_title ) ); ?> (<span id="assigned-users-count"><?php echo $assigned_count; ?></span>)</h2>
                    
                    <div class="user-assignment-form">
                        <h3><?php _e( 'Add Users to Group', 'sandbaai-crime' ); ?></h3>
                        <input type="hidden" id="security-group-id" value="<?php echo esc_attr( $current_group_id ); ?>">
                        <?php wp_nonce_field( 'sandbaai_group_users_nonce', 'sandbaai_group_users_nonce' ); ?>
                        <?php wp_nonce_field( 'sandbaai_search_users_nonce', 'sandbaai_search_users_nonce' ); ?>
                        
                        <select id="selected-users" class="user-select2" multiple style="width: 100%; max-width: 500px;"></select>
                        <p>
                            <button id="assign-users-to-group" class="button button-primary"><?php _e( 'Assign Selected Users', 'sandbaai-crime' ); ?></button>
                        </p>
                        <div id="user-assignment-status"></div>
                    </div>
                    
                    <div class="assigned-users-table-container">
                        <h3><?php _e( 'Currently Assigned Users', 'sandbaai-crime' ); ?></h3>
                        <table id="assigned-users-table" class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Name', 'sandbaai-crime' ); ?></th>
                                    <th><?php _e( 'Email', 'sandbaai-crime' ); ?></th>
                                    <th><?php _e( 'Username', 'sandbaai-crime' ); ?></th>
                                    <th><?php _e( 'Actions', 'sandbaai-crime' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( $assigned_count > 0 ) : ?>
                                    <?php foreach ( $assigned_users as $user ) : ?>
                                        <tr>
                                            <td><?php echo esc_html( $user->display_name ); ?></td>
                                            <td><?php echo esc_html( $user->user_email ); ?></td>
                                            <td><?php echo esc_html( $user->user_login ); ?></td>
                                            <td>
                                                <a href="#" class="button button-small remove-user-from-group" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
                                                    <?php _e( 'Remove', 'sandbaai-crime' ); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4"><?php _e( 'No users assigned to this security group.', 'sandbaai-crime' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Auto-submit when changing security group
        $('#security-group-selector').on('change', function() {
            if ($(this).val()) {
                $(this).closest('form').submit();
            }
        });
    });
    </script>
    <?php
}

/**
 * Enqueue scripts for user management
 */
function sandbaai_enqueue_user_scripts( $hook ) {
    // Only load on our plugin pages
    if ( strpos( $hook, 'sandbaai-security-group-users' ) === false && 
         strpos( $hook, 'user-edit.php' ) === false && 
         strpos( $hook, 'profile.php' ) === false ) {
        return;
    }
    
    // Enqueue Select2 for user selection
    wp_enqueue_style( 'select2', plugin_dir_url( __FILE__ ) . '../vendor/select2/select2.min.css', array(), '4.0.13' );
    wp_enqueue_script( 'select2', plugin_dir_url( __FILE__ ) . '../vendor/select2/select2.min.js', array( 'jquery' ), '4.0.13', true );
    
    // Enqueue our custom script
    wp_enqueue_script( 'sandbaai-user-security-groups', plugin_dir_url( __FILE__ ) . '../js/user-security-groups.js', array( 'jquery', 'select2' ), SANDBAAI_CRIME_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'sandbaai_enqueue_user_scripts' );
