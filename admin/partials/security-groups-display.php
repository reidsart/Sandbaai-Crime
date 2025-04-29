<?php
/**
 * Provides the admin area view for the Security Groups Management section
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://sandbaai.com
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin/partials
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Check for actions
if (isset($_POST['action']) && $_POST['action'] == 'add_security_group') {
    // Security check
    if (!isset($_POST['sandbaai_security_group_nonce']) || !wp_verify_nonce($_POST['sandbaai_security_group_nonce'], 'sandbaai_add_security_group')) {
        wp_die('Security check failed');
    }
    
    // Process form submission
    $group_title = sanitize_text_field($_POST['group_title']);
    $group_description = sanitize_textarea_field($_POST['group_description']);
    $group_email = sanitize_email($_POST['group_email']);
    $group_phone = sanitize_text_field($_POST['group_phone']);
    $group_address = sanitize_textarea_field($_POST['group_address']);
    $group_website = esc_url_raw($_POST['group_website']);
    
    // Create new security group post
    $new_group = array(
        'post_title'    => $group_title,
        'post_content'  => $group_description,
        'post_status'   => 'publish',
        'post_type'     => 'security_group',
    );
    
    $group_id = wp_insert_post($new_group);
    
    if ($group_id) {
        // Add meta data
        update_post_meta($group_id, '_security_group_email', $group_email);
        update_post_meta($group_id, '_security_group_phone', $group_phone);
        update_post_meta($group_id, '_security_group_address', $group_address);
        update_post_meta($group_id, '_security_group_website', $group_website);
        
        // Handle logo upload if present
        if (!empty($_FILES['group_logo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('group_logo', $group_id);
            
            if (!is_wp_error($attachment_id)) {
                update_post_meta($group_id, '_security_group_logo', $attachment_id);
            }
        }
        
        // Set success message
        add_settings_error(
            'sandbaai_crime_messages',
            'security_group_added',
            'Security group successfully added.',
            'updated'
        );
    } else {
        // Set error message
        add_settings_error(
            'sandbaai_crime_messages',
            'security_group_error',
            'Error creating security group.',
            'error'
        );
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['group_id'])) {
    // Security check
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_security_group_' . $_GET['group_id'])) {
        wp_die('Security check failed');
    }
    
    $group_id = intval($_GET['group_id']);
    
    // Delete the security group
    $result = wp_delete_post($group_id, true);
    
    if ($result) {
        add_settings_error(
            'sandbaai_crime_messages',
            'security_group_deleted',
            'Security group successfully deleted.',
            'updated'
        );
    } else {
        add_settings_error(
            'sandbaai_crime_messages',
            'security_group_delete_error',
            'Error deleting security group.',
            'error'
        );
    }
}

// Handle edit action form submission
if (isset($_POST['action']) && $_POST['action'] == 'edit_security_group') {
    // Security check
    if (!isset($_POST['sandbaai_edit_security_group_nonce']) || !wp_verify_nonce($_POST['sandbaai_edit_security_group_nonce'], 'sandbaai_edit_security_group')) {
        wp_die('Security check failed');
    }
    
    $group_id = intval($_POST['group_id']);
    $group_title = sanitize_text_field($_POST['group_title']);
    $group_description = sanitize_textarea_field($_POST['group_description']);
    $group_email = sanitize_email($_POST['group_email']);
    $group_phone = sanitize_text_field($_POST['group_phone']);
    $group_address = sanitize_textarea_field($_POST['group_address']);
    $group_website = esc_url_raw($_POST['group_website']);
    
    // Update security group post
    $updated_group = array(
        'ID'            => $group_id,
        'post_title'    => $group_title,
        'post_content'  => $group_description,
    );
    
    $result = wp_update_post($updated_group);
    
    if ($result) {
        // Update meta data
        update_post_meta($group_id, '_security_group_email', $group_email);
        update_post_meta($group_id, '_security_group_phone', $group_phone);
        update_post_meta($group_id, '_security_group_address', $group_address);
        update_post_meta($group_id, '_security_group_website', $group_website);
        
        // Handle logo upload if present
        if (!empty($_FILES['group_logo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('group_logo', $group_id);
            
            if (!is_wp_error($attachment_id)) {
                update_post_meta($group_id, '_security_group_logo', $attachment_id);
            }
        }
        
        // Set success message
        add_settings_error(
            'sandbaai_crime_messages',
            'security_group_updated',
            'Security group successfully updated.',
            'updated'
        );
    } else {
        // Set error message
        add_settings_error(
            'sandbaai_crime_messages',
            'security_group_update_error',
            'Error updating security group.',
            'error'
        );
    }
}

// Display admin notices
settings_errors('sandbaai_crime_messages');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="#" class="page-title-action" id="add-new-security-group">Add New Security Group</a>
    <hr class="wp-header-end">
    
    <?php
    // Check if we're editing a group
    $editing = false;
    $edit_group = null;
    
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['group_id'])) {
        $group_id = intval($_GET['group_id']);
        $edit_group = get_post($group_id);
        
        if ($edit_group && $edit_group->post_type == 'security_group') {
            $editing = true;
        }
    }
    
    // Display the add/edit form
    if ($editing || isset($_GET['action']) && $_GET['action'] == 'add'):
        // Get group details if editing
        $group_title = '';
        $group_description = '';
        $group_email = '';
        $group_phone = '';
        $group_address = '';
        $group_website = '';
        $group_logo_id = 0;
        
        if ($editing) {
            $group_title = $edit_group->post_title;
            $group_description = $edit_group->post_content;
            $group_email = get_post_meta($edit_group->ID, '_security_group_email', true);
            $group_phone = get_post_meta($edit_group->ID, '_security_group_phone', true);
            $group_address = get_post_meta($edit_group->ID, '_security_group_address', true);
            $group_website = get_post_meta($edit_group->ID, '_security_group_website', true);
            $group_logo_id = get_post_meta($edit_group->ID, '_security_group_logo', true);
        }
    ?>
        <div id="security-group-form" class="security-group-form">
            <form method="post" enctype="multipart/form-data">
                <?php if ($editing): ?>
                    <input type="hidden" name="action" value="edit_security_group">
                    <input type="hidden" name="group_id" value="<?php echo esc_attr($edit_group->ID); ?>">
                    <?php wp_nonce_field('sandbaai_edit_security_group', 'sandbaai_edit_security_group_nonce'); ?>
                    <h2>Edit Security Group</h2>
                <?php else: ?>
                    <input type="hidden" name="action" value="add_security_group">
                    <?php wp_nonce_field('sandbaai_add_security_group', 'sandbaai_security_group_nonce'); ?>
                    <h2>Add New Security Group</h2>
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="group_title">Group Name</label></th>
                        <td><input type="text" id="group_title" name="group_title" class="regular-text" value="<?php echo esc_attr($group_title); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_description">Description</label></th>
                        <td><textarea id="group_description" name="group_description" rows="5" class="large-text"><?php echo esc_textarea($group_description); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_email">Email</label></th>
                        <td><input type="email" id="group_email" name="group_email" class="regular-text" value="<?php echo esc_attr($group_email); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_phone">Contact Phone</label></th>
                        <td><input type="text" id="group_phone" name="group_phone" class="regular-text" value="<?php echo esc_attr($group_phone); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_address">Address</label></th>
                        <td><textarea id="group_address" name="group_address" rows="3" class="large-text"><?php echo esc_textarea($group_address); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_website">Website</label></th>
                        <td><input type="url" id="group_website" name="group_website" class="regular-text" value="<?php echo esc_url($group_website); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_logo">Group Logo</label></th>
                        <td>
                            <?php if ($group_logo_id): ?>
                                <div class="security-group-logo-preview">
                                    <?php echo wp_get_attachment_image($group_logo_id, 'thumbnail'); ?>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="group_logo" name="group_logo" accept="image/*">
                            <p class="description">Upload a logo for this security group (optional).</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $editing ? 'Update Security Group' : 'Add Security Group'; ?>">
                    <a href="<?php echo admin_url('admin.php?page=sandbaai-security-groups'); ?>" class="button button-secondary">Cancel</a>
                </p>
            </form>
        </div>
    <?php else: ?>
        <div id="security-group-form" class="security-group-form" style="display: none;">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_security_group">
                <?php wp_nonce_field('sandbaai_add_security_group', 'sandbaai_security_group_nonce'); ?>
                <h2>Add New Security Group</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="group_title">Group Name</label></th>
                        <td><input type="text" id="group_title" name="group_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_description">Description</label></th>
                        <td><textarea id="group_description" name="group_description" rows="5" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_email">Email</label></th>
                        <td><input type="email" id="group_email" name="group_email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_phone">Contact Phone</label></th>
                        <td><input type="text" id="group_phone" name="group_phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_address">Address</label></th>
                        <td><textarea id="group_address" name="group_address" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_website">Website</label></th>
                        <td><input type="url" id="group_website" name="group_website" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_logo">Group Logo</label></th>
                        <td>
                            <input type="file" id="group_logo" name="group_logo" accept="image/*">
                            <p class="description">Upload a logo for this security group (optional).</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Security Group">
                    <a href="#" class="button button-secondary cancel-add-security-group">Cancel</a>
                </p>
            </form>
        </div>
    
        <!-- Display existing security groups -->
        <div class="security-groups-list">
            <?php
            // Query security groups
            $security_groups = new WP_Query(array(
                'post_type' => 'security_group',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            if ($security_groups->have_posts()): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Website</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($security_groups->have_posts()): $security_groups->the_post(); 
                            $group_id = get_the_ID();
                            $group_email = get_post_meta($group_id, '_security_group_email', true);
                            $group_phone = get_post_meta($group_id, '_security_group_phone', true);
                            $group_website = get_post_meta($group_id, '_security_group_website', true);
                        ?>
                            <tr>
                                <td><?php the_title(); ?></td>
                                <td><?php echo esc_html($group_email); ?></td>
                                <td><?php echo esc_html($group_phone); ?></td>
                                <td>
                                    <?php if (!empty($group_website)): ?>
                                        <a href="<?php echo esc_url($group_website); ?>" target="_blank"><?php echo esc_url($group_website); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sandbaai-security-groups&action=edit&group_id=' . $group_id); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sandbaai-security-groups&action=delete&group_id=' . $group_id), 'delete_security_group_' . $group_id); ?>" class="button button-small delete-security-group" onclick="return confirm('Are you sure you want to delete this security group?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>No security groups found. Add your first security group using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide the add form
    $('#add-new-security-group').on('click', function(e) {
        e.preventDefault();
        $('#security-group-form').slideDown();
    });
    
    $('.cancel-add-security-group').on('click', function(e) {
        e.preventDefault();
        $('#security-group-form').slideUp();
    });
});
</script>

<style>
.security-group-form {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.security-group-logo-preview {
    margin-bottom: 10px;
}

.security-group-logo-preview img {
    max-width: 150px;
    height: auto;
    border: 1px solid #ddd;
    padding: 5px;
}
</style>
