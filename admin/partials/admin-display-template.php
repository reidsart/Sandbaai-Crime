<?php
/**
 * Main admin dashboard display for the Sandbaai Crime plugin
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

<div class="wrap sandbaai-crime-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="sandbaai-crime-admin-header">
        <div class="sandbaai-crime-logo">
            <img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/sandbaai-crime-logo.png'; ?>" alt="Sandbaai Crime">
        </div>
        <div class="sandbaai-crime-version">
            <span>Version: <?php echo SANDBAAI_CRIME_VERSION; ?></span>
        </div>
    </div>

    <div class="sandbaai-crime-admin-tabs">
        <h2 class="nav-tab-wrapper">
            <a href="#general-settings" class="nav-tab nav-tab-active">General Settings</a>
            <a href="#map-settings" class="nav-tab">Map Settings</a>
            <a href="#stats-overview" class="nav-tab">Statistics Overview</a>
        </h2>

        <div id="general-settings" class="tab-content active">
            <form method="post" action="options.php">
                <?php
                settings_fields('sandbaai_crime_general');
                do_settings_sections('sandbaai_crime_general');
                submit_button();
                ?>
            </form>
        </div>

        <div id="map-settings" class="tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('sandbaai_crime_map');
                do_settings_sections('sandbaai_crime_map');
                submit_button();
                ?>
            </form>
        </div>

        <div id="stats-overview" class="tab-content">
            <div class="stats-dashboard">
                <h3>Crime Statistics Overview</h3>
                
                <?php
                // Get crime statistics
                $crime_stats = array();
                $crime_types = array(
                    'break-in' => 'Break-in/Burglary',
                    'theft' => 'Theft',
                    'vehicle' => 'Vehicle Crime',
                    'vandalism' => 'Vandalism',
                    'suspicious' => 'Suspicious Activity',
                    'other' => 'Other'
                );
                
                foreach ($crime_types as $slug => $label) {
                    $args = array(
                        'post_type' => 'crime_report',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => '_crime_type',
                                'value' => $slug,
                                'compare' => '='
                            )
                        )
                    );
                    
                    $query = new WP_Query($args);
                    $crime_stats[$label] = $query->found_posts;
                }
                
                // Get total reports
                $args = array(
                    'post_type' => 'crime_report',
                    'posts_per_page' => -1
                );
                $total_query = new WP_Query($args);
                $total_reports = $total_query->found_posts;
                
                // Get reports last 30 days
                $args = array(
                    'post_type' => 'crime_report',
                    'posts_per_page' => -1,
                    'date_query' => array(
                        array(
                            'after' => '30 days ago'
                        )
                    )
                );
                $recent_query = new WP_Query($args);
                $recent_reports = $recent_query->found_posts;
                ?>
                
                <div class="stats-summary">
                    <div class="stat-box">
                        <h4>Total Reports</h4>
                        <span class="stat-number"><?php echo esc_html($total_reports); ?></span>
                    </div>
                    
                    <div class="stat-box">
                        <h4>Last 30 Days</h4>
                        <span class="stat-number"><?php echo esc_html($recent_reports); ?></span>
                    </div>
                </div>
                
                <div class="stats-by-type">
                    <h4>Reports by Crime Type</h4>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Crime Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($crime_stats as $type => $count) : 
                                $percentage = $total_reports > 0 ? round(($count / $total_reports) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo esc_html($type); ?></td>
                                <td><?php echo esc_html($count); ?></td>
                                <td><?php echo esc_html($percentage); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="export-section">
                <h3>Export Data</h3>
                <form method="post" action="">
                    <p>Download all crime reports as a CSV file for analysis in spreadsheet software.</p>
                    <button type="submit" name="export_crime_reports" class="button button-primary">Export Crime Reports</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.sandbaai-crime-admin-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Hide all tab contents
        $('.tab-content').removeClass('active');
        
        // Remove active class from all tabs
        $('.sandbaai-crime-admin-tabs .nav-tab').removeClass('nav-tab-active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        $($(this).attr('href')).addClass('active');
    });
});
</script>

<style>
.sandbaai-crime-admin-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.sandbaai-crime-logo {
    margin-right: 20px;
}

.sandbaai-crime-logo img {
    max-height: 60px;
}

.tab-content {
    display: none;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
}

.tab-content.active {
    display: block;
}

.stats-summary {
    display: flex;
    margin-bottom: 30px;
}

.stat-box {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    margin-right: 20px;
    text-align: center;
    min-width: 150px;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    display: block;
    margin-top: 5px;
    color: #0073aa;
}

.stats-by-type {
    margin-bottom: 30px;
}

.export-section {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}
</style>
