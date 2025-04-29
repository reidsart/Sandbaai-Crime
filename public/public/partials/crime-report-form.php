<?php
/**
 * Crime Report Form Template
 * 
 * This template handles the frontend crime reporting form with progressive disclosure
 * through a multi-step form process optimized for mobile devices.
 *
 * @package Sandbaai_Crime
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue required scripts and styles
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
wp_enqueue_script('sandbaai-crime-form', plugin_dir_url(__FILE__) . '../js/sandbaai-crime-form.js', array('jquery'), '1.0.0', true);
wp_enqueue_style('sandbaai-crime-form-style', plugin_dir_url(__FILE__) . '../css/sandbaai-crime-form.css');

// Get security groups for dropdown
global $wpdb;
$security_groups = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}sandbaai_security_groups ORDER BY title ASC");

// Get crime categories from options
$crime_categories = get_option('sandbaai_crime_categories', array(
    'theft' => 'Theft',
    'burglary' => 'Burglary',
    'assault' => 'Assault',
    'vandalism' => 'Vandalism',
    'suspicious_activity' => 'Suspicious Activity',
    'other' => 'Other'
));

// Get zones data
$zones = get_option('sandbaai_zones', array(
    'north' => 'North Sandbaai',
    'south' => 'South Sandbaai',
    'east' => 'East Sandbaai',
    'west' => 'West Sandbaai'
));

$subzones = get_option('sandbaai_subzones', array(
    'north' => array('north-1' => 'North 1', 'north-2' => 'North 2'),
    'south' => array('south-1' => 'South 1', 'south-2' => 'South 2'),
    'east' => array('east-1' => 'East 1', 'east-2' => 'East 2'),
    'west' => array('west-1' => 'West 1', 'west-2' => 'West 2'),
));

// Get result statuses
$result_statuses = get_option('sandbaai_result_statuses', array(
    'resolved' => 'Resolved',
    'unresolved' => 'Unresolved',
    'in_progress' => 'In Progress',
    'false_alarm' => 'False Alarm'
));

// Create nonce for form submission
$nonce = wp_create_nonce('sandbaai_crime_report_nonce');
?>

<div class="sandbaai-crime-report-container">
    <h2><?php _e('Report a Crime', 'sandbaai-crime'); ?></h2>
    
    <div class="form-progress-indicator">
        <ol class="progress-steps">
            <li class="step active" data-step="1"><?php _e('Location', 'sandbaai-crime'); ?></li>
            <li class="step" data-step="2"><?php _e('Details', 'sandbaai-crime'); ?></li>
            <li class="step" data-step="3"><?php _e('Response', 'sandbaai-crime'); ?></li>
            <li class="step" data-step="4"><?php _e('Description', 'sandbaai-crime'); ?></li>
            <li class="step" data-step="5"><?php _e('Review', 'sandbaai-crime'); ?></li>
        </ol>
    </div>

    <div class="form-messages"></div>

    <form id="sandbaai-crime-report-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_crime_report">
        <input type="hidden" name="sandbaai_crime_report_nonce" value="<?php echo $nonce; ?>">
        
        <!-- Step 1: Location -->
        <div class="form-step" id="step-1">
            <h3><?php _e('Step 1: Location Information', 'sandbaai-crime'); ?></h3>
            
            <div class="form-group">
                <label for="location_type"><?php _e('Location Type:', 'sandbaai-crime'); ?></label>
                <select name="location_type" id="location_type" required>
                    <option value=""><?php _e('-- Select --', 'sandbaai-crime'); ?></option>
                    <option value="address"><?php _e('Specific Address', 'sandbaai-crime'); ?></option>
                    <option value="zone"><?php _e('Zone Selection', 'sandbaai-crime'); ?></option>
                </select>
            </div>
            
            <div class="form-group location-address" style="display:none;">
                <label for="location_address"><?php _e('Address:', 'sandbaai-crime'); ?></label>
                <input type="text" name="location_address" id="location_address" placeholder="<?php _e('Enter full address', 'sandbaai-crime'); ?>">
            </div>
            
            <div class="form-group location-zone" style="display:none;">
                <label for="location_zone"><?php _e('Zone:', 'sandbaai-crime'); ?></label>
                <select name="location_zone" id="location_zone">
                    <option value=""><?php _e('-- Select Zone --', 'sandbaai-crime'); ?></option>
                    <?php foreach ($zones as $zone_key => $zone_name) : ?>
                        <option value="<?php echo esc_attr($zone_key); ?>"><?php echo esc_html($zone_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group location-subzone" style="display:none;">
                <label for="location_subzone"><?php _e('Sub-Zone:', 'sandbaai-crime'); ?></label>
                <select name="location_subzone" id="location_subzone">
                    <option value=""><?php _e('-- Select Sub-Zone --', 'sandbaai-crime'); ?></option>
                </select>
            </div>
            
            <div class="zone-map-container" style="display:none;">
                <h4><?php _e('Click on map to select zone:', 'sandbaai-crime'); ?></h4>
                <div class="zone-map">
                    <div class="zone north" data-zone="north"><?php _e('North', 'sandbaai-crime'); ?></div>
                    <div class="zone east" data-zone="east"><?php _e('East', 'sandbaai-crime'); ?></div>
                    <div class="zone south" data-zone="south"><?php _e('South', 'sandbaai-crime'); ?></div>
                    <div class="zone west" data-zone="west"><?php _e('West', 'sandbaai-crime'); ?></div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
            </div>
        </div>
        
        <!-- Step 2: Crime Details -->
        <div class="form-step" id="step-2" style="display:none;">
            <h3><?php _e('Step 2: Crime Details', 'sandbaai-crime'); ?></h3>
            
            <div class="form-group">
                <label for="crime_title"><?php _e('Title:', 'sandbaai-crime'); ?></label>
                <input type="text" name="crime_title" id="crime_title" required placeholder="<?php _e('Brief title of incident', 'sandbaai-crime'); ?>">
            </div>
            
            <div class="form-group">
                <label for="crime_category"><?php _e('Crime Category:', 'sandbaai-crime'); ?></label>
                <select name="crime_category" id="crime_category" required>
                    <option value=""><?php _e('-- Select Category --', 'sandbaai-crime'); ?></option>
                    <?php foreach ($crime_categories as $cat_key => $cat_name) : ?>
                        <option value="<?php echo esc_attr($cat_key); ?>"><?php echo esc_html($cat_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="crime_date"><?php _e('Date:', 'sandbaai-crime'); ?></label>
                <input type="text" name="crime_date" id="crime_date" class="datepicker" required placeholder="<?php _e('YYYY-MM-DD', 'sandbaai-crime'); ?>" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="crime_time"><?php _e('Time (approx):', 'sandbaai-crime'); ?></label>
                <input type="time" name="crime_time" id="crime_time" required value="<?php echo date('H:i'); ?>">
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
            </div>
        </div>
        
        <!-- Step 3: Response Information -->
        <div class="form-step" id="step-3" style="display:none;">
            <h3><?php _e('Step 3: Response Information', 'sandbaai-crime'); ?></h3>
            
            <div class="form-group">
                <label for="crime_result"><?php _e('Result Status:', 'sandbaai-crime'); ?></label>
                <select name="crime_result" id="crime_result" required>
                    <option value=""><?php _e('-- Select Result --', 'sandbaai-crime'); ?></option>
                    <?php foreach ($result_statuses as $status_key => $status_name) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>"><?php echo esc_html($status_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label><?php _e('Security Groups Involved:', 'sandbaai-crime'); ?></label>
                <div class="security-groups-options">
                    <?php foreach ($security_groups as $group) : ?>
                        <div class="checkbox-group">
                            <input type="checkbox" name="security_groups[]" id="group-<?php echo esc_attr($group->id); ?>" value="<?php echo esc_attr($group->id); ?>">
                            <label for="group-<?php echo esc_attr($group->id); ?>"><?php echo esc_html($group->title); ?></label>
                        </div>
                    <?php endforeach; ?>
                    <div class="checkbox-group">
                        <input type="checkbox" name="security_groups[]" id="group-none" value="none">
                        <label for="group-none"><?php _e('No security groups involved', 'sandbaai-crime'); ?></label>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
            </div>
        </div>
        
        <!-- Step 4: Description & Photos -->
        <div class="form-step" id="step-4" style="display:none;">
            <h3><?php _e('Step 4: Description & Photos', 'sandbaai-crime'); ?></h3>
            
            <div class="form-group">
                <label for="crime_description"><?php _e('Description:', 'sandbaai-crime'); ?></label>
                <textarea name="crime_description" id="crime_description" rows="5" required placeholder="<?php _e('Please provide details about what happened', 'sandbaai-crime'); ?>"></textarea>
            </div>
            
            <div class="form-group">
                <label for="crime_photos"><?php _e('Upload Photos (optional):', 'sandbaai-crime'); ?></label>
                <input type="file" name="crime_photos[]" id="crime_photos" multiple accept="image/*">
                <p class="description"><?php _e('You can upload up to 5 photos (max 2MB each)', 'sandbaai-crime'); ?></p>
                <div class="photo-preview-container"></div>
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                <button type="button" class="next-step"><?php _e('Next', 'sandbaai-crime'); ?></button>
            </div>
        </div>
        
        <!-- Step 5: Review & Submit -->
        <div class="form-step" id="step-5" style="display:none;">
            <h3><?php _e('Step 5: Review & Submit', 'sandbaai-crime'); ?></h3>
            
            <div class="review-section">
                <h4><?php _e('Location', 'sandbaai-crime'); ?></h4>
                <div class="review-location"></div>
                
                <h4><?php _e('Crime Details', 'sandbaai-crime'); ?></h4>
                <div class="review-details"></div>
                
                <h4><?php _e('Response Information', 'sandbaai-crime'); ?></h4>
                <div class="review-response"></div>
                
                <h4><?php _e('Description', 'sandbaai-crime'); ?></h4>
                <div class="review-description"></div>
                
                <h4><?php _e('Photos', 'sandbaai-crime'); ?></h4>
                <div class="review-photos"></div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="confirm_accurate" id="confirm_accurate" required>
                    <label for="confirm_accurate"><?php _e('I confirm that the information provided is accurate to the best of my knowledge', 'sandbaai-crime'); ?></label>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step"><?php _e('Previous', 'sandbaai-crime'); ?></button>
                <button type="submit" class="submit-report"><?php _e('Submit Report', 'sandbaai-crime'); ?></button>
            </div>
        </div>
        
        <div class="submission-progress" style="display:none;">
            <div class="spinner"></div>
            <p><?php _e('Submitting your report...', 'sandbaai-crime'); ?></p>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize datepicker
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: '0', // Prevent future dates
        changeMonth: true,
        changeYear: true
    });

    // Location type toggle
    $('#location_type').on('change', function() {
        var locationType = $(this).val();
        
        $('.location-address, .location-zone, .zone-map-container').hide();
        
        if (locationType === 'address') {
            $('.location-address').show();
        } else if (locationType === 'zone') {
            $('.location-zone, .zone-map-container').show();
        }
    });

    // Zone selection map
    $('.zone').on('click', function() {
        var zone = $(this).data('zone');
        $('.zone').removeClass('selected');
        $(this).addClass('selected');
        $('#location_zone').val(zone).trigger('change');
    });

    // Dynamic subzone population
    $('#location_zone').on('change', function() {
        var zone = $(this).val();
        var $subzone = $('#location_subzone');
        
        // Reset and hide subzone dropdown
        $subzone.empty().append('<option value=""><?php _e("-- Select Sub-Zone --", "sandbaai-crime"); ?></option>');
        
        if (zone) {
            // Populate subzones based on selected zone
            var subzones = <?php echo json_encode($subzones); ?>;
            
            if (subzones[zone]) {
                $.each(subzones[zone], function(key, value) {
                    $subzone.append('<option value="' + key + '">' + value + '</option>');
                });
                $('.location-subzone').show();
            }
        } else {
            $('.location-subzone').hide();
        }
    });

    // Form navigation
    $('.next-step').on('click', function() {
        var $currentStep = $(this).closest('.form-step');
        var currentStepId = $currentStep.attr('id').split('-')[1];
        var nextStepId = parseInt(currentStepId) + 1;
        
        // Basic validation before proceeding
        var isValid = validateStep(currentStepId);
        
        if (isValid) {
            // Update progress indicator
            $('.progress-steps .step').removeClass('active');
            $('.progress-steps .step[data-step="' + nextStepId + '"]').addClass('active');
            
            // If moving to review step, populate review data
            if (nextStepId === 5) {
                populateReviewData();
            }
            
            // Hide current step and show next
            $currentStep.hide();
            $('#step-' + nextStepId).show();
        }
    });

    $('.prev-step').on('click', function() {
        var $currentStep = $(this).closest('.form-step');
        var currentStepId = $currentStep.attr('id').split('-')[1];
        var prevStepId = parseInt(currentStepId) - 1;
        
        // Update progress indicator
        $('.progress-steps .step').removeClass('active');
        $('.progress-steps .step[data-step="' + prevStepId + '"]').addClass('active');
        
        // Hide current step and show previous
        $currentStep.hide();
        $('#step-' + prevStepId).show();
    });

    // Photo preview
    $('#crime_photos').on('change', function() {
        var $previewContainer = $('.photo-preview-container');
        $previewContainer.empty();
        
        if (this.files && this.files.length > 0) {
            if (this.files.length > 5) {
                alert('<?php _e("You can only upload a maximum of 5 photos", "sandbaai-crime"); ?>');
                $(this).val('');
                return;
            }
            
            for (var i = 0; i < this.files.length; i++) {
                if (this.files[i].size > 2 * 1024 * 1024) {
                    alert('<?php _e("File size exceeds 2MB limit", "sandbaai-crime"); ?>: ' + this.files[i].name);
                    $(this).val('');
                    $previewContainer.empty();
                    return;
                }
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    $previewContainer.append('<div class="photo-preview"><img src="' + e.target.result + '"></div>');
                }
                reader.readAsDataURL(this.files[i]);
            }
        }
    });

    // Exclusive checkbox for "No security groups involved"
    $('#group-none').on('change', function() {
        if ($(this).is(':checked')) {
            $('input[name="security_groups[]"]').not(this).prop('checked', false).prop('disabled', true);
        } else {
            $('input[name="security_groups[]"]').prop('disabled', false);
        }
    });

    $('input[name="security_groups[]"]').not('#group-none').on('change', function() {
        if ($(this).is(':checked')) {
            $('#group-none').prop('checked', false);
        }
    });

    // Form submission
    $('#sandbaai-crime-report-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('#confirm_accurate').is(':checked')) {
            alert('<?php _e("Please confirm that the information is accurate", "sandbaai-crime"); ?>');
            return false;
        }
        
        $('.form-step').hide();
        $('.submission-progress').show();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('.submission-progress').hide();
                
                if (response.success) {
                    $('.form-messages').html('<div class="success-message">' + response.data.message + '</div>');
                    $('#sandbaai-crime-report-form')[0].reset();
                } else {
                    $('.form-messages').html('<div class="error-message">' + response.data.message + '</div>');
                    $('#step-5').show();
                }
            },
            error: function() {
                $('.submission-progress').hide();
                $('.form-messages').html('<div class="error-message"><?php _e("An error occurred. Please try again later.", "sandbaai-crime"); ?></div>');
                $('#step-5').show();
            }
        });
    });

    // Helper functions
    function validateStep(stepId) {
        var isValid = true;
        
        switch(stepId) {
            case '1':
                var locationType = $('#location_type').val();
                
                if (!locationType) {
                    alert('<?php _e("Please select a location type", "sandbaai-crime"); ?>');
                    isValid = false;
                } else if (locationType === 'address' && !$('#location_address').val()) {
                    alert('<?php _e("Please enter an address", "sandbaai-crime"); ?>');
                    isValid = false;
                } else if (locationType === 'zone' && !$('#location_zone').val()) {
                    alert('<?php _e("Please select a zone", "sandbaai-crime"); ?>');
                    isValid = false;
                }
                break;
                
            case '2':
                if (!$('#crime_title').val()) {
                    alert('<?php _e("Please enter a title", "sandbaai-crime"); ?>');
                    isValid = false;
                } else if (!$('#crime_category').val()) {
                    alert('<?php _e("Please select a crime category", "sandbaai-crime"); ?>');
                    isValid = false;
                } else if (!$('#crime_date').val()) {
                    alert('<?php _e("Please enter a date", "sandbaai-crime"); ?>');
                    isValid = false;
                } else if (!$('#crime_time').val()) {
                    alert('<?php _e("Please enter a time", "sandbaai-crime"); ?>');
                    isValid = false;
                }
                break;
                
            case '3':
                if (!$('#crime_result').val()) {
                    alert('<?php _e("Please select a result status", "sandbaai-crime"); ?>');
                    isValid = false;
                }
                
                var securityGroupSelected = false;
                $('input[name="security_groups[]"]').each(function() {
                    if ($(this).is(':checked')) {
                        securityGroupSelected = true;
                    }
                });
                
                if (!securityGroupSelected) {
                    alert('<?php _e("Please select at least one security group or 'None'", "sandbaai-crime"); ?>');
                    isValid = false;
                }
                break;
                
            case '4':
                if (!$('#crime_description').val()) {
                    alert('<?php _e("Please enter a description", "sandbaai-crime"); ?>');
                    isValid = false;
                }
                break;
        }
        
        return isValid;
    }

    function populateReviewData() {
        // Location
        var locationHtml = '';
        var locationType = $('#location_type').val();
        
        if (locationType === 'address') {
            locationHtml = '<p><strong><?php _e("Address", "sandbaai-crime"); ?>:</strong> ' + $('#location_address').val() + '</p>';
        } else if (locationType === 'zone') {
            var zone = $('#location_zone').val();
            var zoneName = $('#location_zone option:selected').text();
            locationHtml = '<p><strong><?php _e("Zone", "sandbaai-crime"); ?>:</strong> ' + zoneName + '</p>';
            
            var subzone = $('#location_subzone').val();
            if (subzone) {
                var subzoneName = $('#location_subzone option:selected').text();
                locationHtml += '<p><strong><?php _e("Sub-Zone", "sandbaai-crime"); ?>:</strong> ' + subzoneName + '</p>';
            }
        }
        
        $('.review-location').html(locationHtml);
        
        // Crime Details
        var detailsHtml = '';
        detailsHtml += '<p><strong><?php _e("Title", "sandbaai-crime"); ?>:</strong> ' + $('#crime_title').val() + '</p>';
        detailsHtml += '<p><strong><?php _e("Category", "sandbaai-crime"); ?>:</strong> ' + $('#crime_category option:selected').text() + '</p>';
        detailsHtml += '<p><strong><?php _e("Date", "sandbaai-crime"); ?>:</strong> ' + $('#crime_date').val() + '</p>';
        detailsHtml += '<p><strong><?php _e("Time", "sandbaai-crime"); ?>:</strong> ' + $('#crime_time').val() + '</p>';
        
        $('.review-details').html(detailsHtml);
        
        // Response
        var responseHtml = '';
        responseHtml += '<p><strong><?php _e("Result Status", "sandbaai-crime"); ?>:</strong> ' + $('#crime_result option:selected').text() + '</p>';
        
        var securityGroups = [];
        $('input[name="security_groups[]"]:checked').each(function() {
            if ($(this).val() === 'none') {
                securityGroups.push('<?php _e("No security groups involved", "sandbaai-crime"); ?>');
            } else {
                securityGroups.push($(this).next('label').text());
            }
        });
        
        responseHtml += '<p><strong><?php _e("Security Groups Involved", "sandbaai-crime"); ?>:</strong> ' + securityGroups.join(', ') + '</p>';
        
        $('.review-response').html(responseHtml);
        
        // Description
        $('.review-description').html('<p>' + $('#crime_description').val() + '</p>');
        
        // Photos
        var photosHtml = '';
        var fileInput = document.getElementById('crime_photos');
        
        if (fileInput.files && fileInput.files.length > 0) {
            photosHtml += '<p>' + fileInput.files.length + ' <?php _e("photo(s) selected for upload", "sandbaai-crime"); ?></p>';
            photosHtml += '<div class="review-photo-thumbnails">';
            
            for (var i = 0; i < fileInput.files.length; i++) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('.review-photo-thumbnails').append('<div class="photo-thumbnail"><img src="' + e.target.result + '"></div>');
                }
                reader.readAsDataURL(fileInput.files[i]);
            }
            
            photosHtml += '</div>';
        } else {
            photosHtml = '<p><?php _e("No photos uploaded", "sandbaai-crime"); ?></p>';
        }
        
        $('.review-photos').html(photosHtml);
    }
});
</script>
