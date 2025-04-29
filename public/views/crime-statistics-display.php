<?php
/**
 * Crime Statistics Display functionality for Sandbaai Crime Plugin
 * 
 * This file handles the display of crime statistics including graphs, 
 * charts, maps, and filterable views of crime data.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Sandbaai_Crime_Statistics {
    /**
     * Initialize the class and set up hooks
     */
    public function __construct() {
        // Add shortcode for crime statistics display
        add_shortcode('sandbaai_crime_statistics', array($this, 'render_statistics_shortcode'));
        
        // Add admin menu page for statistics
        add_action('admin_menu', array($this, 'add_statistics_menu'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        
        // AJAX handlers for filter updates
        add_action('wp_ajax_update_crime_statistics', array($this, 'ajax_update_statistics'));
        add_action('wp_ajax_nopriv_update_crime_statistics', array($this, 'ajax_update_statistics'));
    }
    
    /**
     * Register scripts and styles for frontend
     */
    public function register_scripts() {
        // Register Chart.js
        wp_register_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
            array(),
            '3.7.1',
            true
        );
        
        // Register Leaflet.js for maps
        wp_register_script(
            'leafletjs',
            'https://unpkg.com/leaflet@1.8.0/dist/leaflet.js',
            array(),
            '1.8.0',
            true
        );
        
        wp_register_style(
            'leafletcss',
            'https://unpkg.com/leaflet@1.8.0/dist/leaflet.css',
            array(),
            '1.8.0'
        );
        
        // Register plugin specific script
        wp_register_script(
            'sandbaai-crime-statistics',
            SANDBAAI_CRIME_URL . 'assets/js/crime-statistics.js',
            array('jquery', 'chartjs', 'leafletjs'),
            SANDBAAI_CRIME_VERSION,
            true
        );
        
        // Register plugin specific style
        wp_register_style(
            'sandbaai-crime-statistics',
            SANDBAAI_CRIME_URL . 'assets/css/crime-statistics.css',
            array('leafletcss'),
            SANDBAAI_CRIME_VERSION
        );
    }
    
    /**
     * Register scripts and styles for admin
     */
    public function register_admin_scripts($hook) {
        // Only load on our plugin's admin page
        if (strpos($hook, 'sandbaai-crime-statistics') === false) {
            return;
        }
        
        // Enqueue the same scripts as frontend
        $this->register_scripts();
        wp_enqueue_script('chartjs');
        wp_enqueue_script('leafletjs');
        wp_enqueue_style('leafletcss');
        wp_enqueue_script('sandbaai-crime-statistics');
        wp_enqueue_style('sandbaai-crime-statistics');
        
        // Localize script with data
        wp_localize_script(
            'sandbaai-crime-statistics',
            'sandbaaiCrimeStats',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sandbaai_crime_stats_nonce'),
                'initialData' => $this->get_initial_statistics_data()
            )
        );
    }
    
    /**
     * Add admin menu page for statistics
     */
    public function add_statistics_menu() {
        add_submenu_page(
            'edit.php?post_type=crime_report',
            __('Crime Statistics', 'sandbaai-crime'),
            __('Statistics', 'sandbaai-crime'),
            'edit_posts',
            'sandbaai-crime-statistics',
            array($this, 'render_admin_statistics_page')
        );
    }
    
    /**
     * Render the admin statistics page
     */
    public function render_admin_statistics_page() {
        // Enqueue required scripts and styles
        wp_enqueue_script('chartjs');
        wp_enqueue_script('leafletjs');
        wp_enqueue_style('leafletcss');
        wp_enqueue_script('sandbaai-crime-statistics');
        wp_enqueue_style('sandbaai-crime-statistics');
        
        // Localize script with data
        wp_localize_script(
            'sandbaai-crime-statistics',
            'sandbaaiCrimeStats',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sandbaai_crime_stats_nonce'),
                'initialData' => $this->get_initial_statistics_data()
            )
        );
        
        // Output the admin page HTML
        echo '<div class="wrap">';
        echo '<h1>' . __('Crime Statistics', 'sandbaai-crime') . '</h1>';
        
        // Add filters UI
        $this->render_filters_ui();
        
        // Add statistics containers
        echo '<div class="sandbaai-crime-statistics-container">';
        echo '<div class="sandbaai-crime-row">';
        echo '<div class="sandbaai-crime-col">';
        echo '<div class="sandbaai-crime-card">';
        echo '<h2>' . __('Crimes by Day', 'sandbaai-crime') . '</h2>';
        echo '<div class="sandbaai-crime-chart-container">';
        echo '<canvas id="sandbaai-crime-by-day-chart"></canvas>';
        echo '</div>';
        echo '</div>'; // End card
        echo '</div>'; // End col
        
        echo '<div class="sandbaai-crime-col">';
        echo '<div class="sandbaai-crime-card">';
        echo '<h2>' . __('Crime Categories', 'sandbaai-crime') . '</h2>';
        echo '<div class="sandbaai-crime-chart-container">';
        echo '<canvas id="sandbaai-crime-categories-chart"></canvas>';
        echo '</div>';
        echo '</div>'; // End card
        echo '</div>'; // End col
        echo '</div>'; // End row
        
        echo '<div class="sandbaai-crime-row">';
        echo '<div class="sandbaai-crime-col sandbaai-crime-col-full">';
        echo '<div class="sandbaai-crime-card">';
        echo '<h2>' . __('Crime Locations', 'sandbaai-crime') . '</h2>';
        echo '<div id="sandbaai-crime-map" style="height: 400px;"></div>';
        echo '</div>'; // End card
        echo '</div>'; // End col
        echo '</div>'; // End row
        
        echo '<div class="sandbaai-crime-row">';
        echo '<div class="sandbaai-crime-col sandbaai-crime-col-full">';
        echo '<div class="sandbaai-crime-card">';
        echo '<h2>' . __('Recent Crime Reports', 'sandbaai-crime') . '</h2>';
        echo '<div id="sandbaai-crime-list-container">';
        echo '<table class="sandbaai-crime-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Date', 'sandbaai-crime') . '</th>';
        echo '<th>' . __('Title', 'sandbaai-crime') . '</th>';
        echo '<th>' . __('Category', 'sandbaai-crime') . '</th>';
        echo '<th>' . __('Location', 'sandbaai-crime') . '</th>';
        echo '<th>' . __('Status', 'sandbaai-crime') . '</th>';
        echo '<th>' . __('Actions', 'sandbaai-crime') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody id="sandbaai-crime-list-body">';
        // Will be populated by JavaScript
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // End list container
        echo '</div>'; // End card
        echo '</div>'; // End col
        echo '</div>'; // End row
        
        echo '</div>'; // End statistics container
        echo '</div>'; // End wrap
    }
    
    /**
     * Render filters UI
     */
    public function render_filters_ui() {
        // Get data for filter options
        $months = array(
            1 => __('January', 'sandbaai-crime'),
            2 => __('February', 'sandbaai-crime'),
            3 => __('March', 'sandbaai-crime'),
            4 => __('April', 'sandbaai-crime'),
            5 => __('May', 'sandbaai-crime'),
            6 => __('June', 'sandbaai-crime'),
            7 => __('July', 'sandbaai-crime'),
            8 => __('August', 'sandbaai-crime'),
            9 => __('September', 'sandbaai-crime'),
            10 => __('October', 'sandbaai-crime'),
            11 => __('November', 'sandbaai-crime'),
            12 => __('December', 'sandbaai-crime')
        );
        
        $current_month = date('n');
        $current_year = date('Y');
        
        // Get crime categories
        $categories = get_terms(array(
            'taxonomy' => 'crime_category',
            'hide_empty' => false,
        ));
        
        // Results options
        $results = array(
            'resolved' => __('Resolved', 'sandbaai-crime'),
            'unresolved' => __('Unresolved', 'sandbaai-crime'),
            'in_progress' => __('In Progress', 'sandbaai-crime')
        );
        
        ?>
        <div class="sandbaai-crime-filters">
            <h2><?php _e('Filter Crime Statistics', 'sandbaai-crime'); ?></h2>
            <form id="sandbaai-crime-filters-form" class="sandbaai-crime-filters-form">
                <div class="sandbaai-crime-filters-row">
                    <div class="sandbaai-crime-filter-group">
                        <label for="filter-month"><?php _e('Month', 'sandbaai-crime'); ?></label>
                        <select id="filter-month" name="month">
                            <option value="all"><?php _e('All Months', 'sandbaai-crime'); ?></option>
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?php echo esc_attr($num); ?>" <?php selected($num, $current_month); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sandbaai-crime-filter-group">
                        <label for="filter-year"><?php _e('Year', 'sandbaai-crime'); ?></label>
                        <select id="filter-year" name="year">
                            <?php for ($i = $current_year; $i >= $current_year - 5; $i--): ?>
                                <option value="<?php echo esc_attr($i); ?>" <?php selected($i, $current_year); ?>>
                                    <?php echo esc_html($i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="sandbaai-crime-filter-group">
                        <label for="filter-category"><?php _e('Crime Type', 'sandbaai-crime'); ?></label>
                        <select id="filter-category" name="category">
                            <option value="all"><?php _e('All Categories', 'sandbaai-crime'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sandbaai-crime-filter-group">
                        <label for="filter-result"><?php _e('Result', 'sandbaai-crime'); ?></label>
                        <select id="filter-result" name="result">
                            <option value="all"><?php _e('All Results', 'sandbaai-crime'); ?></option>
                            <?php foreach ($results as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="sandbaai-crime-filters-row">
                    <div class="sandbaai-crime-filter-group sandbaai-crime-filter-timerange">
                        <label><?php _e('Time Range', 'sandbaai-crime'); ?></label>
                        <div class="sandbaai-crime-time-range">
                            <div class="sandbaai-crime-time-range-item">
                                <label for="filter-time-start"><?php _e('From', 'sandbaai-crime'); ?></label>
                                <input type="time" id="filter-time-start" name="time_start" value="00:00">
                            </div>
                            <div class="sandbaai-crime-time-range-item">
                                <label for="filter-time-end"><?php _e('To', 'sandbaai-crime'); ?></label>
                                <input type="time" id="filter-time-end" name="time_end" value="23:59">
                            </div>
                        </div>
                    </div>
                    
                    <div class="sandbaai-crime-filter-group sandbaai-crime-filter-zone">
                        <label for="filter-zone"><?php _e('Zone', 'sandbaai-crime'); ?></label>
                        <select id="filter-zone" name="zone">
                            <option value="all"><?php _e('All Zones', 'sandbaai-crime'); ?></option>
                            <?php
                            // Get zones from custom taxonomy
                            $zones = get_terms(array(
                                'taxonomy' => 'crime_zone',
                                'hide_empty' => false,
                            ));
                            
                            foreach ($zones as $zone): ?>
                                <option value="<?php echo esc_attr($zone->term_id); ?>">
                                    <?php echo esc_html($zone->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sandbaai-crime-filter-group sandbaai-crime-filter-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Apply Filters', 'sandbaai-crime'); ?>
                        </button>
                        <button type="reset" class="button">
                            <?php _e('Reset', 'sandbaai-crime'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render the statistics shortcode
     */
    public function render_statistics_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'show_filters' => 'yes',
                'show_map' => 'yes',
                'show_charts' => 'yes',
                'show_list' => 'yes',
                'limit' => 10,
            ),
            $atts,
            'sandbaai_crime_statistics'
        );
        
        // Enqueue required scripts and styles
        wp_enqueue_script('chartjs');
        wp_enqueue_script('leafletjs');
        wp_enqueue_style('leafletcss');
        wp_enqueue_script('sandbaai-crime-statistics');
        wp_enqueue_style('sandbaai-crime-statistics');
        
        // Localize script with data
        wp_localize_script(
            'sandbaai-crime-statistics',
            'sandbaaiCrimeStats',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sandbaai_crime_stats_nonce'),
                'initialData' => $this->get_initial_statistics_data($atts['limit'])
            )
        );
        
        // Start output buffering
        ob_start();
        
        echo '<div class="sandbaai-crime-statistics-container">';
        
        // Show filters if enabled
        if ($atts['show_filters'] === 'yes') {
            $this->render_filters_ui();
        }
        
        // Show charts if enabled
        if ($atts['show_charts'] === 'yes') {
            echo '<div class="sandbaai-crime-row">';
            echo '<div class="sandbaai-crime-col">';
            echo '<div class="sandbaai-crime-card">';
            echo '<h2>' . __('Crimes by Day', 'sandbaai-crime') . '</h2>';
            echo '<div class="sandbaai-crime-chart-container">';
            echo '<canvas id="sandbaai-crime-by-day-chart"></canvas>';
            echo '</div>';
            echo '</div>'; // End card
            echo '</div>'; // End col
            
            echo '<div class="sandbaai-crime-col">';
            echo '<div class="sandbaai-crime-card">';
            echo '<h2>' . __('Crime Categories', 'sandbaai-crime') . '</h2>';
            echo '<div class="sandbaai-crime-chart-container">';
            echo '<canvas id="sandbaai-crime-categories-chart"></canvas>';
            echo '</div>';
            echo '</div>'; // End card
            echo '</div>'; // End col
            echo '</div>'; // End row
        }
        
        // Show map if enabled
        if ($atts['show_map'] === 'yes') {
            echo '<div class="sandbaai-crime-row">';
            echo '<div class="sandbaai-crime-col sandbaai-crime-col-full">';
            echo '<div class="sandbaai-crime-card">';
            echo '<h2>' . __('Crime Locations', 'sandbaai-crime') . '</h2>';
            echo '<div id="sandbaai-crime-map" style="height: 400px;"></div>';
            echo '</div>'; // End card
            echo '</div>'; // End col
            echo '</div>'; // End row
        }
        
        // Show list if enabled
        if ($atts['show_list'] === 'yes') {
            echo '<div class="sandbaai-crime-row">';
            echo '<div class="sandbaai-crime-col sandbaai-crime-col-full">';
            echo '<div class="sandbaai-crime-card">';
            echo '<h2>' . __('Recent Crime Reports', 'sandbaai-crime') . '</h2>';
            echo '<div id="sandbaai-crime-list-container">';
            echo '<table class="sandbaai-crime-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Date', 'sandbaai-crime') . '</th>';
            echo '<th>' . __('Title', 'sandbaai-crime') . '</th>';
            echo '<th>' . __('Category', 'sandbaai-crime') . '</th>';
            echo '<th>' . __('Location', 'sandbaai-crime') . '</th>';
            echo '<th>' . __('Status', 'sandbaai-crime') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody id="sandbaai-crime-list-body">';
            // Will be populated by JavaScript
            echo '</tbody>';
            echo '</table>';
            echo '</div>'; // End list container
            echo '</div>'; // End card
            echo '</div>'; // End col
            echo '</div>'; // End row
        }
        
        echo '</div>'; // End statistics container
        
        // Return the output
        return ob_get_clean();
    }
    
    /**
     * Get initial statistics data for charts and tables
     */
    public function get_initial_statistics_data($limit = 10) {
        global $wpdb;
        
        // Get data for crimes by day chart (last 30 days)
        $crimes_by_day = $this->get_crimes_by_day();
        
        // Get data for crime categories chart
        $crime_categories = $this->get_crime_categories();
        
        // Get data for crime locations map
        $crime_locations = $this->get_crime_locations();
        
        // Get recent crime reports
        $recent_crimes = $this->get_recent_crimes($limit);
        
        return array(
            'crimesByDay' => $crimes_by_day,
            'crimeCategories' => $crime_categories,
            'crimeLocations' => $crime_locations,
            'recentCrimes' => $recent_crimes
        );
    }
    
    /**
     * Get crimes by day for the last 30 days
     */
    private function get_crimes_by_day() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        
        $query = $wpdb->prepare(
            "SELECT DATE(incident_date) as day, COUNT(*) as count 
            FROM $table_name 
            WHERE incident_date >= %s 
            GROUP BY DATE(incident_date) 
            ORDER BY day ASC",
            $thirty_days_ago
        );
        
        $results = $wpdb->get_results($query);
        
        // Format the data for Chart.js
        $days = array();
        $counts = array();
        
        // Fill in all days in the last 30 days
        for ($i = 0; $i < 30; $i++) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $days[29 - $i] = date('j M', strtotime($day));
            $counts[29 - $i] = 0;
        }
        
        // Fill in actual counts
        foreach ($results as $result) {
            $day_index = 29 - (strtotime('today') - strtotime($result->day)) / 86400;
            if ($day_index >= 0 && $day_index < 30) {
                $counts[(int)$day_index] = (int)$result->count;
            }
        }
        
        return array(
            'labels' => array_values($days),
            'datasets' => array(
                array(
                    'label' => __('Number of Crimes', 'sandbaai-crime'),
                    'data' => array_values($counts),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Get crime categories distribution
     */
    private function get_crime_categories() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $categories_table = $wpdb->prefix . 'sandbaai_crime_categories';
        
        $query = "SELECT c.name, COUNT(*) as count 
                 FROM $table_name r
                 JOIN $categories_table c ON r.category_id = c.id
                 GROUP BY r.category_id
                 ORDER BY count DESC";
        
        $results = $wpdb->get_results($query);
        
        // Format the data for Chart.js
        $labels = array();
        $data = array();
        $background_colors = array(
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(40, 159, 64, 0.7)',
            'rgba(210, 199, 199, 0.7)',
        );
        
        foreach ($results as $index => $result) {
            $labels[] = $result->name;
            $data[] = (int)$result->count;
        }
        
        // If we have more categories than colors, repeat the color array
        while (count($background_colors) < count($labels)) {
            $background_colors = array_merge($background_colors, $background_colors);
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'backgroundColor' => array_slice($background_colors, 0, count($labels)),
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Get crime locations for map
     */
    private function get_crime_locations() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $categories_table = $wpdb->prefix . 'sandbaai_crime_categories';
        
        $query = "SELECT r.id, r.title, r.latitude, r.longitude, c.name as category,
                 DATE_FORMAT(r.incident_date, '%d %b %Y %H:%i') as formatted_date,
                 r.status
                 FROM $table_name r
                 JOIN $categories_table c ON r.category_id = c.id
                 WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
                 ORDER BY r.incident_date DESC
                 LIMIT 100";
        
        $results = $wpdb->get_results($query);
        
        // Format the data for the map
        $locations = array();
        
        foreach ($results as $result) {
            $locations[] = array(
                'id' => $result->id,
                'title' => $result->title,
                'lat' => (float)$result->latitude,
                'lng' => (float)$result->longitude,
                'category' => $result->category,
                'date' => $result->formatted_date,
                'status' => $result->status
            );
        }
        
        return $locations;
    }
    
    /**
     * Get recent crime reports
     */
    private function get_recent_crimes($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $categories_table = $wpdb->prefix . 'sandbaai_crime_categories';
        
        $query = $wpdb->prepare(
            "SELECT r.id, r.title, r.address, r.incident_date, 
             c.name as category, r.status
             FROM $table_name r
             JOIN $categories_table c ON r.category_id = c.id
             ORDER BY r.incident_date DESC
             LIMIT %d",
            $limit
        );
        
        $results = $wpdb->get_results($query);
        
        // Format the data for the table
        $crimes = array();
        
        foreach ($results as $result) {
            $crimes[] = array(
                'id' => $result->id,
                'title' => $result->title,
                'date' => mysql2date('d M Y H:i', $result->incident_date),
                'category' => $result->category,
                'location' => $result->address,
                'status' => $result->status,
                'url' => get_permalink($result->id)
            );
        }
        
        return $crimes;
    }
    
    /**
     * Handle AJAX request to update statistics
     */
    public function ajax_update_statistics() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sandbaai_crime_stats_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            exit;
        }
        
        // Get filter parameters
        $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : 'all';
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'all';
        $result = isset($_POST['result']) ? sanitize_text_field($_POST['result']) : 'all';
        $time_start = isset($_POST['time_start']) ? sanitize_text_field($_POST['time_start']) : '00:00';
        $time_end = isset($_POST['time_end']) ? sanitize_text_field($_POST['time_end']) : '23:59';
        $zone = isset($_POST['zone']) ? sanitize_text_field($_POST['zone']) : 'all';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        // Apply filters and get updated data
        $filtered_data = $this->get_filtered_statistics_data(
            $month,
            $year,
            $category,
            $result,
            $time_start,
            $time_end,
            $zone,
            $limit
        );
        
        // Send response
        wp_send_json_success($filtered_data);
        exit;
    }
    
    /**
     * Get filtered statistics data
     */
    private function get_filtered_statistics_data($month, $year, $category, $result, $time_start, $time_end, $zone, $limit) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sandbaai_crime_reports';
        $categories_table = $wpdb->prefix . 'sandbaai_crime_categories';
        
        // Build WHERE clause based on filters
        $where_clauses = array();
        $where_args = array();
        
        // Month filter
        if ($month !== 'all') {
            $where_clauses[] = 'MONTH(r.incident_date) = %d';
            $where_args[] = $month;
        }
        
        // Year filter
        $where_clauses[] = 'YEAR(r.incident_date) = %d';
        $where_args[] = $year;
        
        // Category filter
        if ($category !== 'all') {
            $where_clauses[] = 'r.category_id = %d';
            $where_args[] = $category;
        }
        
        // Result status filter
        if ($result !== 'all') {
            $where_clauses[] = 'r.status = %s';
            $where_args[] = $result;
        }
        
        // Time range filter
        $where_clauses[] = 'TIME(r.incident_date) >= %s';
        $where_args[] = $time_start;
        
        $where_clauses[] = 'TIME(r.incident_date) <= %s';
        $where_args[] = $time_end;
        
        // Zone filter
        if ($zone !== 'all') {
            $where_clauses[] = 'r.zone_id = %d';
            $where_args[] = $zone;
        }
        
        // Combine WHERE clauses
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Get crimes by day for chart
        $day_query = $this->prepare_filtered_query(
            "SELECT DATE(r.incident_date) as day, COUNT(*) as count 
             FROM $table_name r
             $where_sql
             GROUP BY DATE(r.incident_date) 
             ORDER BY day ASC",
            $where_args
        );
        
        $day_results = $wpdb->get_results($day_query);
        
        // Format the data for chart.js
        $days = array();
        $counts = array();
        
        // Determine the date range based on filters
        if ($month !== 'all') {
            // If month is specified, show all days in that month
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            for ($i = 1; $i <= $days_in_month; $i++) {
                $day = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $days[$i - 1] = date('j M', strtotime($day));
                $counts[$i - 1] = 0;
            }
            
            // Fill in actual counts
            foreach ($day_results as $result) {
                $day_num = (int)date('j', strtotime($result->day));
                $counts[$day_num - 1] = (int)$result->count;
            }
        } else {
            // If no month specified, show last 30 days or appropriate range
            for ($i = 0; $i < 30; $i++) {
                $day = date('Y-m-d', strtotime("-$i days"));
                if (date('Y', strtotime($day)) == $year) {
                    $days[29 - $i] = date('j M', strtotime($day));
                    $counts[29 - $i] = 0;
                }
            }
            
            // Fill in actual counts
            foreach ($day_results as $result) {
                if (date('Y', strtotime($result->day)) == $year) {
                    $day_index = 29 - (strtotime('today') - strtotime($result->day)) / 86400;
                    if ($day_index >= 0 && $day_index < 30) {
                        $counts[(int)$day_index] = (int)$result->count;
                    }
                }
            }
        }
        
        // Get crime categories data
        $category_query = $this->prepare_filtered_query(
            "SELECT c.name, COUNT(*) as count 
             FROM $table_name r
             JOIN $categories_table c ON r.category_id = c.id
             $where_sql
             GROUP BY r.category_id
             ORDER BY count DESC",
            $where_args
        );
        
        $category_results = $wpdb->get_results($category_query);
        
        // Format the data for Chart.js
        $cat_labels = array();
        $cat_data = array();
        $background_colors = array(
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(40, 159, 64, 0.7)',
            'rgba(210, 199, 199, 0.7)',
        );
        
        foreach ($category_results as $index => $result) {
            $cat_labels[] = $result->name;
            $cat_data[] = (int)$result->count;
        }
        
        // Get crime locations for map
        $location_query = $this->prepare_filtered_query(
            "SELECT r.id, r.title, r.latitude, r.longitude, c.name as category,
             DATE_FORMAT(r.incident_date, '%d %b %Y %H:%i') as formatted_date,
             r.status
             FROM $table_name r
             JOIN $categories_table c ON r.category_id = c.id
             $where_sql
             AND r.latitude IS NOT NULL AND r.longitude IS NOT NULL
             ORDER BY r.incident_date DESC
             LIMIT 100",
            $where_args
        );
        
        $location_results = $wpdb->get_results($location_query);
        
        // Format the data for the map
        $locations = array();
        
        foreach ($location_results as $result) {
            $locations[] = array(
                'id' => $result->id,
                'title' => $result->title,
                'lat' => (float)$result->latitude,
                'lng' => (float)$result->longitude,
                'category' => $result->category,
                'date' => $result->formatted_date,
                'status' => $result->status
            );
        }
        
        // Get recent crime reports
        $crime_query = $this->prepare_filtered_query(
            "SELECT r.id, r.title, r.address, r.incident_date, 
             c.name as category, r.status
             FROM $table_name r
             JOIN $categories_table c ON r.category_id = c.id
             $where_sql
             ORDER BY r.incident_date DESC
             LIMIT %d",
            array_merge($where_args, array($limit))
        );
        
        $crime_results = $wpdb->get_results($crime_query);
        
        // Format the data for the table
        $crimes = array();
        
        foreach ($crime_results as $result) {
            $crimes[] = array(
                'id' => $result->id,
                'title' => $result->title,
                'date' => mysql2date('d M Y H:i', $result->incident_date),
                'category' => $result->category,
                'location' => $result->address,
                'status' => $result->status,
                'url' => get_permalink($result->id)
            );
        }
        
        return array(
            'crimesByDay' => array(
                'labels' => array_values($days),
                'datasets' => array(
                    array(
                        'label' => __('Number of Crimes', 'sandbaai-crime'),
                        'data' => array_values($counts),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    )
                )
            ),
            'crimeCategories' => array(
                'labels' => $cat_labels,
                'datasets' => array(
                    array(
                        'data' => $cat_data,
                        'backgroundColor' => array_slice($background_colors, 0, count($cat_labels)),
                        'borderWidth' => 1
                    )
                )
            ),
            'crimeLocations' => $locations,
            'recentCrimes' => $crimes
        );
    }
    
    /**
     * Helper function to prepare SQL with filters
     */
    private function prepare_filtered_query($query, $args) {
        global $wpdb;
        
        if (!empty($args)) {
            return $wpdb->prepare($query, $args);
        }
        
        return $query;
    }
}

// Initialize the class
new Sandbaai_Crime_Statistics();