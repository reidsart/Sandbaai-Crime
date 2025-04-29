/**
 * Crime Statistics JavaScript
 * 
 * This file handles the frontend functionality for the crime statistics display,
 * including charts, map, and filter updates.
 */

(function($) {
    'use strict';
    
    // Store global variables
    var crimeMap = null;
    var crimeMarkers = [];
    var crimesByDayChart = null;
    var crimeCategoriesChart = null;
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize charts and map
        initializeCharts();
        
        // Initialize map if the container exists
        if ($('#sandbaai-crime-map').length) {
            initializeMap();
        }
        
        // Initialize filters
        initializeFilters();
        
        // Populate crime list
        populateCrimeList(sandbaaiCrimeStats.initialData.recentCrimes);
    });
    
    /**
     * Initialize the charts
     */
    function initializeCharts() {
        // Initialize crimes by day chart
        var crimesByDayCtx = document.getElementById('sandbaai-crime-by-day-chart');
        if (crimesByDayCtx) {
            crimesByDayChart = new Chart(crimesByDayCtx, {
                type: 'bar',
                data: sandbaaiCrimeStats.initialData.crimesByDay,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize crime categories chart
        var crimeCategoriesCtx = document.getElementById('sandbaai-crime-categories-chart');
        if (crimeCategoriesCtx) {
            crimeCategoriesChart = new Chart(crimeCategoriesCtx, {
                type: 'pie',
                data: sandbaaiCrimeStats.initialData.crimeCategories,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Initialize the map
     */
    function initializeMap() {
        // Initialize map centered on Sandbaai or first crime location
        var initialCenter = [-34.4184, 19.2344]; // Default Sandbaai coordinates
        var initialZoom = 14;
        
        // Try to get first crime location
        if (sandbaaiCrimeStats.initialData.crimeLocations.length > 0) {
            initialCenter = [
                sandbaaiCrimeStats.initialData.crimeLocations[0].lat,
                sandbaaiCrimeStats.initialData.crimeLocations[0].lng
            ];
        }
        
        // Create the map
        crimeMap = L.map('sandbaai-crime-map').setView(initialCenter, initialZoom);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(crimeMap);
        
        // Add crime markers
        addCrimeMarkers(sandbaaiCrimeStats.initialData.crimeLocations);
        
        // Fit bounds to markers if we have any
        if (sandbaaiCrimeStats.initialData.crimeLocations.length > 0) {
            var bounds = L.latLngBounds();
            sandbaaiCrimeStats.initialData.crimeLocations.forEach(function(location) {
                bounds.extend([location.lat, location.lng]);
            });
            crimeMap.fitBounds(bounds, { maxZoom: 15 });
        }
    }
    
    /**
     * Add crime markers to the map
     */
    function addCrimeMarkers(locations) {
        // Remove existing markers
        crimeMarkers.forEach(function(marker) {
            crimeMap.removeLayer(marker);
        });
        crimeMarkers = [];
        
        // Add new markers
        locations.forEach(function(location) {
            // Determine marker color based on crime category
            var markerColor = getMarkerColor(location.category);
            
            // Create marker
            var marker = L.circleMarker([location.lat, location.lng], {
                radius: 8,
                fillColor: markerColor,
                color: '#000',
                weight: 1,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(crimeMap);
            
            // Add popup
            marker.bindPopup(
                '<div class="crime-marker-popup">' +
                '<h4>' + location.title + '</h4>' +
                '<p><strong>Category:</strong> ' + location.category + '</p>' +
                '<p><strong>Date:</strong> ' + location.date + '</p>' +
                '<p><strong>Status:</strong> ' + getStatusLabel(location.status) + '</p>' +
                '</div>'
            );
            
            // Store marker
            crimeMarkers.push(marker);
        });
    }
    
    /**
     * Get marker color based on crime category
     */
    function getMarkerColor(category) {
        // Define colors for common categories
        var categoryColors = {
            'Theft': '#ff6384',
            'Burglary': '#ff9f40',
            'Robbery': '#ff6384',
            'Assault': '#ff4560',
            'Vandalism': '#36a2eb',
            'Suspicious Activity': '#ffcd56',
            'Trespassing': '#4bc0c0',
            'Other': '#9966ff'
        };
        
        // Return color for category or default
        return categoryColors[category] || '#9966ff';
    }
    
    /**
     * Get human-readable status label
     */
    function getStatusLabel(status) {
        var statusLabels = {
            'resolved': 'Resolved',
            'unresolved': 'Unresolved',
            'in_progress': 'In Progress'
        };
        
        return statusLabels[status] || status;
    }
    
    /**
     * Initialize filters
     */
    function initializeFilters() {
        // Handle filter form submission
        $('#sandbaai-crime-filters-form').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            $('.sandbaai-crime-statistics-container').addClass('loading');
            
            // Get form data
            var formData = $(this).serialize();
            
            // Add additional data
            formData += '&action=update_crime_statistics';
            formData += '&nonce=' + sandbaaiCrimeStats.nonce;
            
            // Send AJAX request
            $.post(sandbaaiCrimeStats.ajaxUrl, formData, function(response) {
                if (response.success) {
                    // Update charts and map with new data
                    updateCharts(response.data);
                    updateMap(response.data.crimeLocations);
                    populateCrimeList(response.data.recentCrimes);
                } else {
                    alert('Error updating statistics: ' + response.data.message);
                }
                
                // Remove loading state
                $('.sandbaai-crime-statistics-container').removeClass('loading');
            });
        });
        
        // Handle filter reset
        $('#sandbaai-crime-filters-form button[type="reset"]').on('click', function() {
            // Wait for form to reset
            setTimeout(function() {
                // Trigger form submission to reload with default values
                $('#sandbaai-crime-filters-form').submit();
            }, 10);
        });
    }
    
    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        // Update crimes by day chart
        if (crimesByDayChart) {
            crimesByDayChart.data = data.crimesByDay;
            crimesByDayChart.update();
        }
        
        // Update crime categories chart
        if (crimeCategoriesChart) {
            crimeCategoriesChart.data = data.crimeCategories;
            crimeCategoriesChart.update();
        }
    }
    
    /**
     * Update map with new data
     */
    function updateMap(locations) {
        if (crimeMap) {
            // Add new markers
            addCrimeMarkers(locations);
            
            // Fit bounds to markers if we have any
            if (locations.length > 0) {
                var bounds = L.latLngBounds();
                locations.forEach(function(location) {
                    bounds.extend([location.lat, location.lng]);
                });
                crimeMap.fitBounds(bounds, { maxZoom: 15 });
            }
        }
    }
    
    /**
     * Populate crime list
     */
    function populateCrimeList(crimes) {
        var $listBody = $('#sandbaai-crime-list-body');
        if (!$listBody.length) return;
        
        // Clear existing rows
        $listBody.empty();
        
        // Add new rows
        if (crimes.length > 0) {
            crimes.forEach(function(crime) {
                var statusClass = 'status-' + crime.status;
                var $row = $('<tr></tr>');
                
                $row.append('<td>' + crime.date + '</td>');
                $row.append('<td><a href="' + crime.url + '">' + crime.title + '</a></td>');
                $row.append('<td>' + crime.category + '</td>');
                $row.append('<td>' + crime.location + '</td>');
                $row.append('<td><span class="crime-status ' + statusClass + '">' + getStatusLabel(crime.status) + '</span></td>');
                
                // Add actions column if in admin
                if ($('.wrap').length) {
                    $row.append(
                        '<td>' +
                        '<a href="post.php?post=' + crime.id + '&action=edit" class="button button-small">' +
                        '<span class="dashicons dashicons-edit"></span> Edit' +
                        '</a> ' +
                        '<a href="' + crime.url + '" class="button button-small">' +
                        '<span class="dashicons dashicons-visibility"></span> View' +
                        '</a>' +
                        '</td>'
                    );
                }
                
                $listBody.append($row);
            });
        } else {
            // No crimes found
            var colSpan = $('.wrap').length ? 6 : 5;
            $listBody.append(
                '<tr>' +
                '<td colspan="' + colSpan + '" class="no-results">' +
                'No crime reports found matching your criteria.' +
                '</td>' +
                '</tr>'
            );
        }
    }
    
})(jQuery);