<?php
/**
 * Crime Report Form Template
 * 
 * Template for the crime report submission form
 * 
 * @package SandbaaiCrime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get security groups for dropdown
$security_groups_args = array(
    'post_type' => 'security_group',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish'
);
$security_groups = get_posts($security_groups_args);

// Get crime categories (either from options or hardcoded default)
$crime_categories = get_option('sandbaai_crime_categories', array(
    'burglary' => 'Burglary',
    'theft' => 'Theft',
    'vandalism' => 'Vandalism',
    'suspicious' => 'Suspicious Activity',
    'other' => 'Other'
));

// Generate nonce for form security
$nonce = wp_create_nonce('sandbaai_crime_form');
?>

<div class="sandbaai-crime-form-container">
    <div class="form-progress-bar">
        <div class="progress-step active" data-step="1">Location</div>
        <div class="progress-step" data-step="2">Details</div>
        <div class="progress-step" data-step="3">Description</div>
        <div class="progress-step" data-step="4">Review</div>
    </div>

    <form id="sandbaai-crime-form" class="sandbaai-crime-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_crime_report">
        <input type="hidden" name="sandbaai_crime_nonce" value="<?php echo $nonce; ?>">
        
        <!-- Step 1: Location -->
        <div class="form-step" id="step-1">
            <h3>Where did the incident occur?</h3>
            
            <!-- Location field -->
            <div class="form-field">
                <label for="crime_location">Address/Location <span class="required">*</span></label>
                <input type="text" id="crime_location" name="crime_location" required>
            </div>
            
            <!-- Zone selection map -->
            <div class="form-field">
                <label for="crime_zone">Zone</label>
                <div class="zone-map-container">
                    <div class="zone-map">
                        <!-- Zone map will be loaded dynamically via JS -->
                        <div class="zone-placeholder">
                            <p>Loading zone map...</p>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="crime_zone" name="crime_zone">
            </div>
            
            <div class="form-navigation">
                <button type="button" class="next-step">Next: Incident Details</button>
            </div>
        </div>
        
        <!-- Step 2: Details -->
        <div class="form-step hidden" id="step-2">
            <h3>Incident Details</h3>
            
            <!-- Title field -->
            <div class="form-field">
                <label for="crime_title">Title <span class="required">*</span></label>
                <input type="text" id="crime_title" name="crime_title" required>
            </div>
            
            <!-- Category selection -->
            <div class="form-field">
                <label for="crime_category">Category <span class="required">*</span></label>
                <select id="crime_category" name="crime_category" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($crime_categories as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Date and time fields -->
            <div class="form-field">
                <label for="crime_date">Date <span class="required">*</span></label>
                <input type="date" id="crime_date" name="crime_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-field">
                <label for="crime_time">Time <span class="required">*</span></label>
                <input type="time" id="crime_time" name="crime_time" required value="<?php echo date('H:i'); ?>">
            </div>
            
            <!-- Result field -->
            <div class="form-field">
                <label for="crime_result">Result/Outcome</label>
                <select id="crime_result" name="crime_result">
                    <option value="">-- Select Outcome --</option>
                    <option value="caught">Perpetrator Caught</option>
                    <option value="deterred">Perpetrator Deterred</option>
                    <option value="unresolved">Unresolved</option>
                    <option value="reported">Only Reported</option>
                </select>
            </div>
            
            <!-- Security groups involved -->
            <div class="form-field">
                <label>Security Groups Involved</label>
                <div class="security-groups-list">
                    <?php foreach ($security_groups as $group) : ?>
                        <div class="security-group-item">
                            <input type="checkbox" id="security_group_<?php echo $group->ID; ?>" name="security_groups[]" value="<?php echo $group->ID; ?>">
                            <label for="security_group_<?php echo $group->ID; ?>"><?php echo get_the_title($group); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-navigation">
                <button type="button" class="prev-step">Previous</button>
                <button type="button" class="next-step">Next: Description & Photos</button>
            </div>
        </div>
        
        <!-- Step 3: Description & Photos -->
        <div class="form-step hidden" id="step-3">
            <h3>Description & Photos</h3>
            
            <!-- Description field -->
            <div class="form-field">
                <label for="crime_description">Description</label>
                <textarea id="crime_description" name="crime_description" rows="5"></textarea>
            </div>
            
            <!-- Photo upload -->
            <div class="form-field">
                <label for="crime_images">Upload Photos</label>
                <input type="file" id="crime_images" name="crime_images[]" multiple accept="image/*">
                <div class="preview-images-container"></div>
            </div>
            
            <div class="form-navigation">
                <button type="button" class="prev-step">Previous</button>
                <button type="button" class="next-step">Next: Review Report</button>
            </div>
        </div>
        
        <!-- Step 4: Review -->
        <div class="form-step hidden" id="step-4">
            <h3>Review Your Report</h3>
            
            <div class="review-content">
                <!-- Will be populated dynamically via JS -->
                <div class="review-section">
                    <h4>Location</h4>
                    <p id="review-location"></p>
                    <p id="review-zone"></p>
                </div>
                
                <div class="review-section">
                    <h4>Incident Details</h4>
                    <p id="review-title"></p>
                    <p id="review-category"></p>
                    <p id="review-datetime"></p>
                    <p id="review-result"></p>
                </div>
                
                <div class="review-section">
                    <h4>Security Groups</h4>
                    <div id="review-security-groups"></div>
                </div>
                
                <div class="review-section">
                    <h4>Description</h4>
                    <p id="review-description"></p>
                </div>
                
                <div class="review-section">
                    <h4>Uploaded Photos</h4>
                    <div id="review-photos"></div>
                </div>
            </div>
            
            <div class="form-navigation">
                <button type="button" class="prev-step">Edit Report</button>
                <button type="submit" class="submit-report">Submit Crime Report</button>
            </div>
        </div>
        
        <!-- Response messages -->
        <div class="form-messages">
            <div class="success-message hidden">
                <h3>Thank You!</h3>
                <p>Your crime report has been submitted successfully.</p>
                <p>Report ID: <span class="report-id"></span></p>
            </div>
            
            <div class="error-message hidden">
                <h3>Error</h3>
                <p class="error-content"></p>
            </div>
        </div>
    </form>
</div>
