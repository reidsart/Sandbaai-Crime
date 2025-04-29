/**
 * User Security Group Assignment JavaScript
 *
 * Handles the AJAX operations for assigning users to security groups
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin/js
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle security group assignment in user profile
        $('.security-group-select').on('change', function() {
            const userId = $(this).data('user-id');
            const groupId = $(this).val();
            const securityNonce = $('#sandbaai_user_group_nonce').val();
            
            // Show loading indicator
            $(this).after('<span class="spinner is-active" style="float: none;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sandbaai_assign_security_group',
                    user_id: userId,
                    group_id: groupId,
                    security: securityNonce
                },
                success: function(response) {
                    // Remove spinner
                    $('.spinner').remove();
                    
                    if (response.success) {
                        // Show success message
                        $('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>')
                            .insertAfter('.security-group-select')
                            .delay(2000)
                            .fadeOut();
                    } else {
                        // Show error message
                        $('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>')
                            .insertAfter('.security-group-select');
                    }
                },
                error: function() {
                    // Remove spinner
                    $('.spinner').remove();
                    
                    // Show error message
                    $('<div class="notice notice-error inline"><p>Error connecting to server.</p></div>')
                        .insertAfter('.security-group-select');
                }
            });
        });
        
        // Handle bulk user assignment on the security groups page
        $('#assign-users-to-group').on('click', function(e) {
            e.preventDefault();
            
            const groupId = $('#security-group-id').val();
            const userIds = $('#selected-users').val();
            const securityNonce = $('#sandbaai_group_users_nonce').val();
            
            if (!userIds || userIds.length === 0) {
                alert('Please select at least one user to assign.');
                return;
            }
            
            // Show loading indicator
            $('#user-assignment-status').html('<span class="spinner is-active" style="float: none;"></span> Assigning users...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sandbaai_bulk_assign_users',
                    group_id: groupId,
                    user_ids: userIds,
                    security: securityNonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('#user-assignment-status').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        
                        // Refresh the user list
                        refreshAssignedUsers(groupId);
                    } else {
                        // Show error message
                        $('#user-assignment-status').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    // Show error message
                    $('#user-assignment-status').html('<div class="notice notice-error inline"><p>Error connecting to server.</p></div>');
                }
            });
        });
        
        // Handle user removal from security group
        $(document).on('click', '.remove-user-from-group', function(e) {
            e.preventDefault();
            
            const userId = $(this).data('user-id');
            const groupId = $('#security-group-id').val();
            const securityNonce = $('#sandbaai_group_users_nonce').val();
            
            if (confirm('Are you sure you want to remove this user from the security group?')) {
                // Show loading indicator next to the clicked button
                $(this).after('<span class="spinner is-active" style="float: none;"></span>');
                const $row = $(this).closest('tr');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sandbaai_remove_user_from_group',
                        user_id: userId,
                        group_id: groupId,
                        security: securityNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove the row with animation
                            $row.fadeOut(function() {
                                $(this).remove();
                                
                                // Update count
                                const currentCount = parseInt($('#assigned-users-count').text(), 10);
                                $('#assigned-users-count').text(currentCount - 1);
                                
                                // Show message if no users left
                                if (currentCount - 1 === 0) {
                                    $('#assigned-users-table tbody').html('<tr><td colspan="4">No users assigned to this security group.</td></tr>');
                                }
                            });
                        } else {
                            // Remove spinner
                            $('.spinner').remove();
                            
                            // Show error message
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        // Remove spinner
                        $('.spinner').remove();
                        
                        // Show error message
                        alert('Error connecting to server.');
                    }
                });
            }
        });
        
        // Function to refresh the assigned users list
        function refreshAssignedUsers(groupId) {
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'sandbaai_get_assigned_users',
                    group_id: groupId,
                    security: $('#sandbaai_group_users_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Update the assigned users table
                        $('#assigned-users-table tbody').html(response.data.html);
                        $('#assigned-users-count').text(response.data.count);
                        
                        // Clear the user selection
                        $('#selected-users').val(null).trigger('change');
                    }
                }
            });
        }
        
        // Initialize select2 for user selection
        if ($.fn.select2) {
            $('.user-select2').select2({
                placeholder: 'Search for users...',
                allowClear: true,
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'sandbaai_search_users',
                            security: $('#sandbaai_search_users_nonce').val(),
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        
                        return {
                            results: data.data.users,
                            pagination: {
                                more: data.data.more
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2
            });
        }
    });
})(jQuery);
