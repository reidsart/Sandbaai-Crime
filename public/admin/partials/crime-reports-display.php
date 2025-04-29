<?php
/**
 * Crime reports management page display for the Sandbaai Crime plugin
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
?>

<div class="wrap sandbaai-crime-reports">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="crime-reports-overview">
        <?php
        // Get crime statistics by status
        $published_count = wp_count_posts('crime_report')->publish;
        $pending_count = wp_count_posts('crime_report')->pending;
        $draft_count = wp_count_posts('crime_report')->draft;
        $total_count = $published_count + $pending_count + $draft_count;
        ?>
        
        <div class="reports-summary">
            <div class="report-stat-box">
                <h4>Total Reports</h4>
                <span class="stat-number"><?php echo esc_html($total_count); ?></span>
            </div>
            
            <div class="report-stat-box">
                <h4>Published</h4>
                <span class="stat-number"><?php echo esc_html($published_count); ?></span>
            </div>
            
            <div class="report-stat-box">
                <h4>Pending Review</h4>
                <span class="stat-number"><?php echo esc_html($pending_count); ?></span>
            </div>
            
            <div class="report-stat-box">
                <h4>Draft</h4>
                <span class="stat-number"><?php echo esc_html($draft_count); ?></span>
            </div>
        </div>
        
        <div class="reports-actions">
            <a href="<?php echo admin_url('post-new.php?post_type=crime_report'); ?>" class="button button-primary">Add New Report</a>
            
            <form method="post" action="" class="export-form">
                <button type="submit" name="export_crime_reports" class="button">Export Reports (CSV)</button>
            </form>
        </div>
    </div>
    
    <div class="reports-map-view">
        <h3>Crime Reports Map View</h3>
        <div id="crime-reports-map" style="height: 400px; width: 100%;"></div>
    </div>
    
    <div class="recent-reports">
        <h3>Recent Reports</h3>
        
        <?php
        // Get recent crime reports
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $reports = new WP_Query($args);
        
        if ($reports->have_posts()) :
        ?>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Crime Type</th>
                    <th>Date/Time</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($reports->have_posts()) :
                    $reports->the_post();
                    $post_id = get_the_ID();
                    
                    $crime_type = get_post_meta($post_id, '_crime_type', true);
                    $crime_date = get_post_meta($post_id, '_crime_date', true);
                    $crime_time = get_post_meta($post_id, '_crime_time', true);
                    $address = get_post_meta($post_id, '_crime_address', true);
                    
                    $crime_types = array(
                        'break-in' => 'Break-in/Burglary',
                        'theft' => 'Theft',
                        'vehicle' => 'Vehicle Crime',
                        'vandalism' => 'Vandalism',
                        'suspicious' => 'Suspicious Activity',
                        'other' => 'Other'
                    );
                    
                    $crime_type_label = isset($crime_types[$crime_type]) ? $crime_types[$crime_type] : $crime_type;
                ?>
                <tr>
                    <td><?php echo esc_html($post_id); ?></td>
                    <td>
                        <a href="<?php echo get_edit_post_link($post_id); ?>">
                            <?php the_title(); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($crime_type_label); ?></td>
                    <td><?php echo esc_html($crime_date) . '<br>' . esc_html($crime_time); ?></td>
                    <td><?php echo esc_html($address); ?></td>
                    <td><?php echo get_post_status(); ?></td>
                    <td>
                        <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-small">Edit</a>
                        <a href="<?php echo get_permalink($post_id); ?>" class="button button-small" target="_blank">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="view-all-link">
            <a href="<?php echo admin_url('edit.php?post_type=crime_report'); ?>">View All Reports</a>
        </div>
        
        <?php
        else :
            echo '<p>No crime reports found.</p>';
        endif;
        
        wp_reset_postdata();
        ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize the map if the element exists
    if ($('#crime-reports-map').length) {
        // Get map options
        var mapOptions = <?php 
            $map_options = get_option('sandbaai_crime_map_options', array());
            $default_lat = isset($map_options['map_center_lat']) ? $map_options['map_center_lat'] : '-34.4131';
            $default_lng = isset($map_options['map_center_lng']) ? $map_options['map_center_lng'] : '19.2262';
            $default_zoom = isset($map_options['map_zoom_level']) ? $map_options['map_zoom_level'] : '14';
            
            echo json_encode(array(
                'center' => array($default_lat, $default_lng),
                'zoom' => intval($default_zoom)
            ));
        ?>;
    
        // Initialize the map
        var map = L.map('crime-reports-map').setView(mapOptions.center, mapOptions.zoom);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add crime report markers
        <?php
        // Get all crime reports with location data
        $args = array(
            'post_type' => 'crime_report',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_crime_latitude',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_crime_longitude',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $map_reports = new WP_Query($args);
        
        if ($map_reports->have_posts()) :
            echo "var markers = [];\n";
            
            while ($map_reports->have_posts()) :
                $map_reports->the_post();
                $post_id = get_the_ID();
                
                $lat = get_post_meta($post_id, '_crime_latitude', true);
                $lng = get_post_meta($post_id, '_crime_longitude', true);
                $crime_type = get_post_meta($post_id, '_crime_type', true);
                $crime_date = get_post_meta($post_id, '_crime_date', true);
                
                if (!empty($lat) && !empty($lng)) :
                    // Define marker colors based on crime type
                    $marker_colors = array(
                        'break-in' => 'red',
                        'theft' => 'orange',
                        'vehicle' => 'blue',
                        'vandalism' => 'purple',
                        'suspicious' => 'yellow',
                        'other' => 'grey'
                    );
                    
                    $color = isset($marker_colors[$crime_type]) ? $marker_colors[$crime_type] : 'red';
                    
                    $crime_types = array(
                        'break-in' => 'Break-in/Burglary',
                        'theft' => 'Theft',
                        'vehicle' => 'Vehicle Crime',
                        'vandalism' => 'Vandalism',
                        'suspicious' => 'Suspicious Activity',
                        'other' => 'Other'
                    );
                    
                    $crime_type_label = isset($crime_types[$crime_type]) ? $crime_types[$crime_type] : $crime_type;
                    $title = get_the_title();
                    $edit_link = get_edit_post_link($post_id);
                ?>
                var marker = L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>], {
                    title: "<?php echo esc_js($title); ?>"
                }).addTo(map);
                
                marker.bindPopup(
                    "<strong><?php echo esc_js($title); ?></strong>" + 
                    "<br>Type: <?php echo esc_js($crime_type_label); ?>" + 
                    "<br>Date: <?php echo esc_js($crime_date); ?>" + 
                    "<br><a href='<?php echo esc_js($edit_link); ?>'>Edit Report</a>"
                );
                
                markers.push(marker);
                <?php
                endif;
            endwhile;
            
            // Reset post data
            wp_reset_postdata();
        endif;
        ?>
        
        // If we have markers, group them and fit map bounds
        if (typeof markers !== 'undefined' && markers.length > 0) {
            var group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }
});
</script>

<style>
.reports-summary {
    display: flex;
    margin-bottom: 20px;
}

.report-stat-box {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    margin-right: 20px;
    text-align: center;
    min-width: 120px;
}

.report-stat-box h4 {
    margin: 0 0 10px 0;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    display: block;
    color: #0073aa;
}

.reports-actions {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.export-form {
    display: inline;
}

.reports-map-view {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
}

.recent-reports {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
}

.view-all-link {
    margin-top: 15px;
    text-align: right;
}

.view-all-link a {
    font-weight: bold;
}
</style>
