<?php
/**
 * Security groups management page display for the Sandbaai Crime plugin
 *
 * @link       https://www.reidsart.co.za
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin/partials
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Get security groups class
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-security-group.php';
$security_group = new Sandbaai_Crime_Security_Group();

// Handle form submissions
if (isset($_POST['add_security_group']) && current_user_can('manage_options')) {
    // Verify nonce
    if (isset($_POST['security_group_nonce']) && wp_verify_nonce($_POST['security_group_nonce'], 'sandbaai_security_group_action')) {
        // Sanitize inputs
        $group_name = sanitize_text_field($_POST['group_name']);
        $group_contact = sanitize_text_field($_POST['group_contact']);
        $whatsapp_group = sanitize_text_field($_POST['whatsapp_group']);
        $group_area = sanitize_textarea_field($_POST['group_area']);
        
        // Add security group
        $result = $security_group->add_security_group($group_name, $group_contact, $whatsapp_group, $group_area);
        
        if ($result) {
            $message = 'Security group added successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to add security group.';
            $message_type = 'error';
        }
    } else {
        $message = 'Security verification failed.';
        $message_type = 'error';
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && current_user_can('manage_options')) {
    // Verify nonce
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_security_group')) {
        $group_id = intval($_GET['id']);
        $result = $security_group->delete_security_group($group_id);
        
        if ($result) {
            $message = 'Security group deleted successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete security group.';
            $message_type = 'error';
        }
    } else {
        $message = 'Security verification failed.';
        $message_type = 'error';
    }
}

// Get all security groups
$security_groups = $security_group->get_security_groups();
?>

<div class="wrap sandbaai-security-groups">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($message)) : ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="security-groups-container">
        <div class="add-security-group">
            <h3>Add New Security Group</h3>
            
            <form method="post" action="">
                <?php wp_nonce_field('sandbaai_security_group_action', 'security_group_nonce'); ?>
                
                <div class="form-field">
                    <label for="group_name">Group Name:</label>
                    <input type="text" id="group_name" name="group_name" required class="regular-text">
                </div>
                
                <div class="form-field">
                    <label for="group_contact">Contact Person:</label>
                    <input type="text" id="group_contact" name="group_contact" class="regular-text">
                </div>
                
                <div class="form-field">
                    <label for="whatsapp_group">WhatsApp Group Link:</label>
                    <input type="url" id="whatsapp_group" name="whatsapp_group" class="regular-text">
                </div>
                
                <div class="form-field">
                    <label for="group_area">Coverage Area Description:</label>
                    <textarea id="group_area" name="group_area" rows="4" class="large-text"></textarea>
                    <p class="description">Describe the geographical area covered by this security group.</p>
                </div>
                
                <button type="submit" name="add_security_group" class="button button-primary">Add Security Group</button>
            </form>
        </div>
        
        <div class="existing-security-groups">
            <h3>Existing Security Groups</h3>
            
            <?php if (!empty($security_groups)) : ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Group Name</th>
                        <th>Contact Person</th>
                        <th>WhatsApp Group</th>
                        <th>Coverage Area</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_groups as $group) : ?>
                    <tr>
                        <td><?php echo esc_html($group->id); ?></td>
                        <td><?php echo esc_html($group->group_name); ?></td>
                        <td><?php echo esc_html($group->contact_person); ?></td>
                        <td>
                            <?php if (!empty($group->whatsapp_group)) : ?>
                            <a href="<?php echo esc_url($group->whatsapp_group); ?>" target="_blank">
                                Join Group
                            </a>
                            <?php else : ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($group->coverage_area); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=sandbaai-security-groups&action=edit&id=' . $group->id)); ?>" class="button button-small">Edit</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sandbaai-security-groups&action=delete&id=' . $group->id), 'delete_security_group'); ?>" class="button button-small delete-group" onclick="return confirm('Are you sure you want to delete this security group?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p>No security groups found. Add a new group using the form on the left.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="security-groups-map">
        <h3>Security Groups Coverage Map</h3>
        <div id="security-groups-map" style="height: 400px; width: 100%;"></div>
        <p class="description">This map shows the approximate coverage areas of each security group.</p>
    </div>
</div>

<style>
.security-groups-container {
    display: flex;
    margin-bottom: 30px;
}

.add-security-group {
    flex: 0 0 40%;
    margin-right: 2%;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
}

.existing-security-groups {
    flex: 0 0 58%;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.security-groups-map {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    margin-top: 20px;
}

@media screen and (max-width: 782px) {
    .security-groups-container {
        flex-direction: column;
    }
    
    .add-security-group,
    .existing-security-groups {
        flex: 0 0 100%;
        margin-right: 0;
        margin-bottom: 20px;
    }
}
</style>

<?php
// Check if we're in edit mode
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) :
    $group_id = intval($_GET['id']);
    $group = $security_group->get_security_group($group_id);
    
    // Handle edit form submission
    if (isset($_POST['update_security_group']) && current_user_can('manage_options')) {
        // Verify nonce
        if (isset($_POST['security_group_edit_nonce']) && wp_verify_nonce($_POST['security_group_edit_nonce'], 'sandbaai_security_group_edit')) {
            // Sanitize inputs
            $group_name = sanitize_text_field($_POST['group_name']);
            $group