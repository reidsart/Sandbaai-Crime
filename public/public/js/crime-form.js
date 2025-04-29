/**
 * Sandbaai Crime Report Form JavaScript
 * 
 * Handles client-side functionality for the crime reporting form including
 * form navigation, validation, and submission.
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Form wizard navigation
        initFormNavigation();
        
        // Form field dependencies and conditional display
        initFieldDependencies();
        
        // Photo upload handling and preview
        initPhotoUpload();
        
        // Form validation and AJAX submission
        initFormSubmission();
    });
    
    /**
     * Initialize form step navigation
     * Handles the next/previous buttons and progress indicators
     */
    function initFormNavigation() {
        // Next button handler
        $('.next-step').on('click', function() {
            var $currentStep = $(this).closest('.form-step');
            var currentStepNum = parseInt($currentStep.attr('id').split('-')[1]);
            var $nextStep = $('#step-' + (currentStepNum + 1));
            
            // Validate current step before proceeding
            if (validateStep(currentStepNum)) {
                // Update progress indicator
                $('.progress-steps .step').removeClass('active');
                $('.progress-steps .step[data-step="' + (currentStepNum + 1) + '"]').addClass('active');
                
                // If moving to review step, populate review data
                if (currentStepNum === 4) {
                    populateReviewData();
                }
                
                // Hide current step and show next
                $currentStep.hide();
                $nextStep.show();
                
                // Scroll to top of form
                $('html, body').animate({
                    scrollTop: $('.sandbaai-crime-report-container').offset().top - 50
                }, 500);
            }
        });
        
        // Previous button handler
        $('.prev-step').on('click', function() {
            var $currentStep = $(this).closest('.form-step');
            var currentStepNum = parseInt($currentStep.attr('id').split('-')[1]);
            var $prevStep = $('#step-' + (currentStepNum - 1));
            
            // Update progress indicator
            $('.progress-steps .step').removeClass('active');
            $('.progress-steps .step[data-step="' + (currentStepNum - 1) + '"]').addClass('active');
            
            // Hide current step and show previous
            $currentStep.hide();
            $prevStep.show();
            
            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $('.sandbaai-crime-report-container').offset().top - 50
            }, 500);
        });
    }
    
    /**
     * Initialize field dependencies and conditional display logic
     */
    function initFieldDependencies() {
        // Location type toggle
        $('#location_type').on('change', function() {
            var locationType = $(this).val();
            
            $('.location-address, .location-zone, .location-subzone, .zone-map-container').hide();
            
            if (locationType === 'address') {
                $('.location-address').fadeIn(300);
            } else if (locationType === 'zone') {
                $('.location-zone, .zone-map-container').fadeIn(300);
            }
        });
        
        // Zone selection via clickable map
        $('.zone').on('click', function() {
            var zone = $(this).data('zone');
            $('.zone').removeClass('selected');
            $(this).addClass('selected');
            $('#location_zone').val(zone).trigger('change');
        });
        
        // Populate subzones based on selected zone
        $('#location_zone').on('change', function() {
            var zone = $(this).val();
            var $subzone = $('#location_subzone');
            
            $subzone.empty().append('<option value="">-- Select Sub-Zone --</option>');
            $('.location-subzone').hide();
            
            if (zone) {
                // Get subzones from data attribute or via AJAX
                var zoneData = window.sandbaaiZones || {};
                
                if (zoneData[zone]) {
                    $.each(zoneData[zone], function(key, value) {
                        $subzone.append('<option value="' + key + '">' + value + '</option>');
                    });
                    $('.location-subzone').fadeIn(300);
                }
                
                // Highlight zone on map
                $('.zone').removeClass('selected');
                $('.zone[data-zone="' + zone + '"]').addClass('selected');
            }
        });
        
        // Handle security group exclusivity
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
    }
    
    /**
     * Initialize photo upload preview and validation
     */
    function initPhotoUpload() {
        $('#crime_photos').on('change', function() {
            var $previewContainer = $('.photo-preview-container');
            $previewContainer.empty();
            
            if (this.files && this.files.length > 0) {
                // Validate number of files
                if (this.files.length > 5) {
                    alert('You can only upload a maximum of 5 photos');
                    $(this).val('');
                    return;
                }
                
                // Validate file size and create previews
                for (var i = 0; i < this.files.length; i++) {
                    if (this.files[i].size > 2 * 1024 * 1024) {
                        alert('File size exceeds 2MB limit: ' + this.files[i].name);
                        $(this).val('');
                        $previewContainer.empty();
                        return;
                    }
                    
                    // Create preview image
                    (function(file) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            $previewContainer.append(
                                '<div class="photo-preview">' +
                                '<img src="' + e.target.result + '" alt="Preview">' +
                                '<span class="filename">' + file.name + '</span>' +
                                '</div>'
                            );
                        };
                        reader.readAsDataURL(file);
                    })(this.files[i]);
                }
            }
        });
    }
    
    /**
     * Initialize form validation and submission
     */
    function initFormSubmission() {
        $('#sandbaai-crime-report-form').on('submit', function(e) {
            e.preventDefault();
            
            // Final validation checks
            if (!$('#confirm_accurate').is(':checked')) {
                alert('Please confirm that the information is accurate');
                return false;
            }
            
            // Hide form steps and show progress indicator
            $('.form-step').hide();
            $('.submission-progress').show();
            
            // Prepare form data for AJAX submission
            var formData = new FormData(this);
            
            // Submit form via AJAX
            $.ajax({
                url: sandbaai_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('.submission-progress').hide();
                    
                    if (response.success) {
                        // Show success message
                        $('.form-messages').html('<div class="success-message">' + response.data.message + '</div>');
                        $('#sandbaai-crime-report-form')[0].reset();
                        
                        // Reset form state
                        $('.progress-steps .step').removeClass('active');
                        $('.progress-steps .step[data-step="1"]').addClass('active');
                        $('.form-step').hide();
                        $('#step-1').show();
                    } else {
                        // Show error message
                        $('.form-messages').html('<div class="error-message">' + response.data.message + '</div>');
                        $('#step-5').show();
                    }
                },
                error: function() {
                    // Handle AJAX errors
                    $('.submission-progress').hide();
                    $('.form-messages').html('<div class="error-message">An error occurred. Please try again later.</div>');
                    $('#step-5').show();
                }
            });
        });
    }
    
    /**
     * Validate form step before proceeding
     * 
     * @param {number} stepNum - The step number to validate
     * @return {boolean} - Whether the step is valid
     */
    function validateStep(stepNum) {
        var isValid = true;
        
        switch(stepNum) {
            case 1:
                // Validate location step
                var locationType = $('#location_type').val();
                
                if (!locationType) {
                    alert('Please select a location type');
                    isValid = false;
                } else if (locationType === 'address' && !$('#location_address').val()) {
                    alert('Please enter an address');
                    isValid = false;
                } else if (locationType === 'zone' && !$('#location_zone').val()) {
                    alert('Please select a zone');
                    isValid = false;
                }
                break;
                
            case 2:
                // Validate crime details step
                if (!$('#crime_title').val()) {
                    alert('Please enter a title');
                    isValid = false;
                } else if (!$('#crime_category').val()) {
                    alert('Please select a crime category');
                    isValid = false;
                } else if (!$('#crime_date').val()) {
                    alert('Please enter a date');
                    isValid = false;
                } else if (!$('#crime_time').val()) {
                    alert('Please enter a time');
                    isValid = false;
                }
                break;
                
            case 3:
                // Validate response step
                if (!$('#crime_result').val()) {
                    alert('Please select a result status');
                    isValid = false;
                }
                
                // Check if at least one security group is selected
                var securityGroupSelected = false;
                $('input[name="security_groups[]"]').each(function() {
                    if ($(this).is(':checked')) {
                        securityGroupSelected = true;
                        return false; // Break loop
                    }
                });
                
                if (!securityGroupSelected) {
                    alert('Please select at least one security group or "None"');
                    isValid = false;
                }
                break;
                
            case 4:
                // Validate description step
                if (!$('#crime_description').val()) {
                    alert('Please enter a description');
                    isValid = false;
                }
                break;
        }
        
        return isValid;
    }
    
    /**
     * Populate review step with data from previous steps
     */
    function populateReviewData() {
        // Location review
        var locationHtml = '';
        var locationType = $('#location_type').val();
        
        if (locationType === 'address') {
            locationHtml = '<p><strong>Address:</strong> ' + $('#location_address').val() + '</p>';
        } else if (locationType === 'zone') {
            var zoneName = $('#location_zone option:selected').text();
            locationHtml = '<p><strong>Zone:</strong> ' + zoneName + '</p>';
            
            var subzone = $('#location_subzone').val();
            if (subzone) {
                var subzoneName = $('#location_subzone option:selected').text();
                locationHtml += '<p><strong>Sub-Zone:</strong> ' + subzoneName + '</p>';
            }
        }
        
        $('.review-location').html(locationHtml);
        
        // Crime details review
        var detailsHtml = '';
        detailsHtml += '<p><strong>Title:</strong> ' + $('#crime_title').val() + '</p>';
        detailsHtml += '<p><strong>Category:</strong> ' + $('#crime_category option:selected').text() + '</p>';
        detailsHtml += '<p><strong>Date:</strong> ' + $('#crime_date').val() + '</p>';
        detailsHtml += '<p><strong>Time:</strong> ' + $('#crime_time').val() + '</p>';
        
        $('.review-details').html(detailsHtml);
        
        // Response review
        var responseHtml = '';
        responseHtml += '<p><strong>Result Status:</strong> ' + $('#crime_result option:selected').text() + '</p>';
        
        var securityGroups = [];
        $('input[name="security_groups[]"]:checked').each(function() {
            if ($(this).val() === 'none') {
                securityGroups.push('No security groups involved');
            } else {
                securityGroups.push($(this).next('label').text());
            }
        });
        
        responseHtml += '<p><strong>Security Groups Involved:</strong> ' + securityGroups.join(', ') + '</p>';
        
        $('.review-response').html(responseHtml);
        
        // Description review
        $('.review-description').html('<p>' + $('#crime_description').val() + '</p>');
        
        // Photos review
        var photosHtml = '';
        var fileInput = document.getElementById('crime_photos');
        
        if (fileInput.files && fileInput.files.length > 0) {
            photosHtml += '<p>' + fileInput.files.length + ' photo(s) selected for upload</p>';
            photosHtml += '<div class="review-photo-thumbnails">';
            
            for (var i = 0; i < fileInput.files.length; i++) {
                (function(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('.review-photo-thumbnails').append(
                            '<div class="photo-thumbnail">' +
                            '<img src="' + e.target.result + '" alt="Photo">' +
                            '<span class="filename">' + file.name + '</span>' +
                            '</div>'
                        );
                    };
                    reader.readAsDataURL(file);
                })(fileInput.files[i]);
            }
            
            photosHtml += '</div>';
        } else {
            photosHtml = '<p>No photos uploaded</p>';
        }
        
        $('.review-photos').html(photosHtml);
    }
    
})(jQuery);