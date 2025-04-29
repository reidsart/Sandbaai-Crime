<?php
/**
 * The crime reporting form functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/public
 */
class Sandbaai_Crime_Report_Form {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_shortcode('sandbaai_crime_report_form', array($this, 'render_form'));
        add_action('wp_ajax_submit_crime_report', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_crime_report', array($this, 'handle_form_submission'));
    }

    /**
     * Render the crime reporting form.
     *
     * @return string Form HTML
     */
    public function render_form() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }

        // Enqueue necessary scripts and styles
        wp_enqueue_script('sandbaai-crime-form');
        wp_enqueue_style('sandbaai-crime-form');
        
        // Get security groups for dropdown
        $security_groups = $this->get_security_groups();
        
        // Get crime categories for dropdown
        $crime_categories = $this->get_crime_categories();
        
        // Start output buffering
        ob_start();
        ?>
        <div class="sandbaai-crime-report-form-container">
            <form id="sandbaai-crime-report-form" class="multi-step-form">
                <?php wp_nonce_field('sandbaai_crime_report_nonce', 'crime_report_nonce'); ?>
                
                <!-- Step 1: Location -->
                <div class="form-step" id="step-1">
                    <h3><?php _e('Step 1: Crime Location', 'sandbaai-crime'); ?></h3>
                    
                    <div class="location-type-toggle">
                        <label>
                            <input type="radio" name="location_type" value="address" checked>
                            <?php _e('Enter Address', 'sandbaai-crime'); ?>
                        </label>
                        <label>
                            <input type="radio" name="location_type" value="zone">
                            <?php _e('Select Zone', 'sandbaai-crime'); ?>
                        </label>
                    </div>
                    
                    <div class="location-address-fields">
                        <div class="form-group">
                            <label for="crime-address"><?php _e('Address', 'sandbaai-crime'); ?></label>
                            <input type="text" id="crime-address" name="address" placeholder="<?php _e('Enter street address', 'sandbaai-crime'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="crime-suburb"><?php _e('Suburb', 'sandbaai-crime'); ?></label>
                            <input type="text" id="crime-suburb" name="suburb" value="Sandbaai" readonly>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="crime-province"><?php _e('Province', 'sandbaai-crime'); ?></label>
                                <input type="text" id="crime-province" name="province" value="Western Cape" readonly>
                            </div>
                            
                            <div class="form-group half">
                                <label for="crime-postcode"><?php _e('Postcode', 'sandbaai-crime'); ?></label>
                                <input type="text" id="crime-postcode" name="postcode" value="7200" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="location-zone-selector" style="display: none;">
                        <div id="zone-map-container">
                            <!-- Map will be inserted here via JavaScript -->
                        </div>
                        <input type="hidden" name="zone_id" id="selected-zone-id">
                        <div id="selected-zone-display"></div>
                    </div>
                    
                    <div class="form-navigation">
                        <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
                    </div>
                </div>
                
                <!-- Step 2: Crime Details -->
                <div class="form-step" id="step-2" style="display: none;">
                    <h3><?php _e('Step 2: Crime Details', 'sandbaai-crime'); ?></h3>
                    
                    <div class="form-group">
                        <label for="crime-title"><?php _e('Crime Title', 'sandbaai-crime'); ?></label>
                        <input type="text" id="crime-title" name="title" required placeholder="<?php _e('Brief title of the incident', 'sandbaai-crime'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="crime-category"><?php _e('Crime Category', 'sandbaai-crime'); ?></label>
                        <select id="crime-category" name="crime_category_id" required>
                            <option value=""><?php _e('Select a category', 'sandbaai-crime'); ?></option>
                            <?php foreach ($crime_categories as $category) : ?>
                                <option value="<?php echo esc_attr($category->id); ?>"><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-navigation">
                        <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                        <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
                    </div>
                </div>
                
                <!-- Step 3: Date and Time -->
                <div class="form-step" id="step-3" style="display: none;">
                    <h3><?php _e('Step 3: Date and Time', 'sandbaai-crime'); ?></h3>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="crime-date"><?php _e('Date', 'sandbaai-crime'); ?></label>
                            <input type="date" id="crime-date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group half">
                            <label for="crime-time"><?php _e('Time', 'sandbaai-crime'); ?></label>
                            <input type="time" id="crime-time" name="time" required value="<?php echo date('H:i'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-navigation">
                        <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                        <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
                    </div>
                </div>
                
                <!-- Step 4: Result and Security Groups -->
                <div class="form-step" id="step-4" style="display: none;">
                    <h3><?php _e('Step 4: Result and Response', 'sandbaai-crime'); ?></h3>
                    
                    <div class="form-group">
                        <label for="crime-result"><?php _e('Result', 'sandbaai-crime'); ?></label>
                        <select id="crime-result" name="result" required>
                            <option value="unsolved"><?php _e('Unsolved', 'sandbaai-crime'); ?></option>
                            <option value="prevented"><?php _e('Crime Prevented', 'sandbaai-crime'); ?></option>
                            <option value="solved"><?php _e('Crime Solved', 'sandbaai-crime'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php _e('Security Groups Involved', 'sandbaai-crime'); ?></label>
                        <div class="security-groups-checkboxes">
                            <?php foreach ($security_groups as $group) : ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="security_groups[]" value="<?php echo esc_attr($group->id); ?>">
                                    <?php echo esc_html($group->title); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-navigation">
                        <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                        <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
                    </div>
                </div>
                
                <!-- Step 5: Description and Photo -->
                <div class="form-step" id="step-5" style="display: none;">
                    <h3><?php _e('Step 5: Description and Photo', 'sandbaai-crime'); ?></h3>
                    
                    <div class="form-group">
                        <label for="crime-description"><?php _e('Description', 'sandbaai-crime'); ?></label>
                        <textarea id="crime-description" name="description" rows="5" required placeholder="<?php _e('Describe what happened...', 'sandbaai-crime'); ?>"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="crime-photo"><?php _e('Photo (optional)', 'sandbaai-crime'); ?></label>
                        <input type="file" id="crime-photo" name="photo" accept="image/*">
                        <div id="photo-preview"></div>
                    </div>
                    
                    <input type="hidden" name="security_group_id" value="<?php echo esc_attr($this->get_user_security_group()); ?>">
                    <input type="hidden" name="action" value="submit_crime_report">
                    
                    <div class="form-navigation">
                        <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                        <button type="submit" class="submit-report"><?php _e('Submit Report', 'sandbaai-crime'); ?></button>
                    </div>
                </div>
                
                <div class="form-progress-indicator">
                    <span class="step active"></span>
                    <span class="step"></span>
                    <span class="step"></span>
                    <span class="step"></span>
                    <span class="step"></span>
                </div>
            </form>
            
            <div id="submission-status" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle form submission via AJAX.
     */
    public function handle_form_submission() {
        // Verify nonce
        if (!isset($_POST['crime_report_nonce']) || !wp_verify_nonce($_POST['crime_report_nonce'], 'sandbaai_crime_report_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'sandbaai-crime')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to report a crime.', 'sandbaai-crime')));
        }

        // Handle file upload if present
        $photo_url = '';
        if (!empty($_FILES['photo'])) {
            $photo_url = $this->handle_photo_upload();
            if (is_wp_error($photo_url)) {
                wp_send_json_error(array('message' => $photo_url->get_error_message()));
            }
        }

        // Prepare data for insertion
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'crime_category_id' => intval($_POST['crime_category_id']),
            'crime_date' => sanitize_text_field($_POST['date']),
            'crime_time' => sanitize_text_field($_POST['time']),
            'result' => sanitize_text_field($_POST['result']),
            'security_group_id' => intval($_POST['security_group_id']),
            'created_by' => get_current_user_id(),
            'photo_url' => $photo_url,
        );

        // Handle location (address or zone)
        if ($_POST['location_type'] === 'address') {
            $data['address'] = sanitize_text_field($_POST['address']);
            $data['zone_id'] = null;
        } else {
            $data['address'] = null;
            $data['zone_id'] = intval($_POST['zone_id']);
        }

        // Set status based on security group
        $security_group_id = intval($_POST['security_group_id']);
        if ($security_group_id === 1) { // Assuming 1 is the ID for "resident" group
            $data['status'] = 'pending';
        } else {
            $data['status'] = 'published';
        }

        // Insert into database
        global $wpdb;
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'sandbaai_crime_reports',
            $data
        );

        if ($inserted) {
            $report_id = $wpdb->insert_id;
            
            // Insert security groups involved
            if (!empty($_POST['security_groups']) && is_array($_POST['security_groups'])) {
                foreach ($_POST['security_groups'] as $group_id) {
                    $wpdb->insert(
                        $wpdb->prefix . 'sandbaai_report_groups',
                        array(
                            'report_id' => $report_id,
                            'security_group_id' => intval($group_id)
                        )
                    );
                }
            }
            
            // Send WhatsApp notification if enabled
            $this->maybe_send_whatsapp_notification($data);
            
            wp_send_json_success(array(
                'message' => __('Crime report submitted successfully!', 'sandbaai-crime'),
                'report_id' => $report_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit crime report.', 'sandbaai-crime')));
        }
    }

    /**
     * Get login message for non-logged in users.
     *
     * @return string Message HTML
     */
    private function get_login_message() {
        ob_start();
        ?>
        <div class="sandbaai-crime-login-notice">
            <p><?php _e('You must be logged in to report a crime.', 'sandbaai-crime'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button"><?php _e('Log In', 'sandbaai-crime'); ?></a>
            <?php if (get_option('users_can_register')) : ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button button-secondary"><?php _e('Register', 'sandbaai-crime'); ?></a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get all security groups.
     *
     * @return array Security groups
     */
    private function get_security_groups() {
        global $wpdb;
        return $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}sandbaai_security_groups");
    }

    /**
     * Get all crime categories.
     *
     * @return array Crime categories
     */
    private function get_crime_categories() {
        global $wpdb;
        return $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}sandbaai_crime_categories");
    }

    /**
     * Get the security group ID of the current user.
     *
     * @return int Security group ID
     */
    private function get_user_security_group() {
        global $wpdb;
        $user_id = get_current_user_id();
        
        // Check if user is assigned to any security group
        $security_group = $wpdb->get_var($wpdb->prepare(
            "SELECT security_group_id FROM {$wpdb->prefix}sandbaai_user_groups WHERE user_id = %d",
            $user_id
        ));
        
        // If not assigned, return the default "resident" group (ID: 1)
        if (empty($security_group)) {
            return 1;
        }
        
        return intval($security_group);
    }
    
    /**
     * Handle photo upload.
     *
     * @return string|WP_Error URL of uploaded photo or error
     */
    private function handle_photo_upload() {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('photo', 0);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        return wp_get_attachment_url($attachment_id);
    }
    
    /**
     * Send WhatsApp notification if enabled.
     *
     * @param array $report_data Report data
     */
    private function maybe_send_whatsapp_notification($report_data) {
        // Check if WhatsApp notifications are enabled
        $whatsapp_enabled = get_option('sandbaai_crime_whatsapp_enabled', false);
        if (!$whatsapp_enabled) {
            return;
        }
        
        // Get WhatsApp API details
        $api_key = get_option('sandbaai_crime_whatsapp_api_key', '');
        $group_id = get_option('sandbaai_crime_whatsapp_group_id', '');
        
        if (empty($api_key) || empty($group_id)) {
            return;
        }
        
        // Prepare notification message
        $category = $this->get_category_name($report_data['crime_category_id']);
        $location = !empty($report_data['address']) ? $report_data['address'] : $this->get_zone_name($report_data['zone_id']);
        
        $message = sprintf(
            __('New Crime Report: %s\nCategory: %s\nLocation: %s\nDate/Time: %s %s\nStatus: %s', 'sandbaai-crime'),
            $report_data['title'],
            $category,
            $location,
            $report_data['crime_date'],
            $report_data['crime_time'],
            ucfirst($report_data['result'])
        );
        
        // Send WhatsApp notification via API
        // This is a placeholder - you'll need to implement the actual API call
        // based on the WhatsApp API service you choose to use.
        $response = wp_remote_post('https://your-whatsapp-api-endpoint.com', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'group_id' => $group_id,
                'message' => $message
            ))
        ));
        
        // Log error if any
        if (is_wp_error($response)) {
            error_log('WhatsApp Notification Error: ' . $response->get_error_message());
        }
    }
    
    /**
     * Get category name by ID.
     *
     * @param int $category_id Category ID
     * @return string Category name
     */
    private function get_category_name($category_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}sandbaai_crime_categories WHERE id = %d",
            $category_id
        ));
    }
    
    /**
     * Get zone name by ID.
     *
     * @param int $zone_id Zone ID
     * @return string Zone name
     */
    private function get_zone_name($zone_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}sandbaai_zones WHERE id = %d",
            $zone_id
        ));
    }
}