<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.reidsart.co.za
 * @since      1.0.0
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sandbaai_Crime
 * @subpackage Sandbaai_Crime/admin
 * @author     Reid Sart <reidsart@gmail.com>
 */
class Sandbaai_Crime_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add actions to set up admin menu and settings
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sandbaai_Crime_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sandbaai_Crime_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sandbaai-crime-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sandbaai_Crime_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sandbaai_Crime_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sandbaai-crime-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add admin menu pages for the plugin
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Add main menu page
		add_menu_page(
			'Sandbaai Crime Settings',
			'Sandbaai Crime',
			'manage_options',
			'sandbaai-crime',
			array($this, 'display_plugin_admin_page'),
			'dashicons-shield',
			30
		);

		// Add submenus
		add_submenu_page(
			'sandbaai-crime',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'sandbaai-crime',
			array($this, 'display_plugin_admin_page')
		);

		add_submenu_page(
			'sandbaai-crime',
			'Crime Reports',
			'Crime Reports',
			'manage_options',
			'sandbaai-crime-reports',
			array($this, 'display_crime_reports_page')
		);

		add_submenu_page(
			'sandbaai-crime',
			'Security Groups',
			'Security Groups',
			'manage_options',
			'sandbaai-security-groups',
			array($this, 'display_security_groups_page')
		);

		add_submenu_page(
			'sandbaai-crime',
			'WhatsApp Settings',
			'WhatsApp Settings',
			'manage_options',
			'sandbaai-whatsapp-settings',
			array($this, 'display_whatsapp_settings_page')
		);
	}

	/**
	 * Register settings for the plugin
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// Register general settings
		register_setting('sandbaai_crime_general', 'sandbaai_crime_general_options');
		
		add_settings_section(
			'sandbaai_crime_general_section',
			'General Settings',
			array($this, 'general_settings_section_callback'),
			'sandbaai_crime_general'
		);
		
		add_settings_field(
			'enable_crime_reporting',
			'Enable Crime Reporting',
			array($this, 'enable_crime_reporting_callback'),
			'sandbaai_crime_general',
			'sandbaai_crime_general_section'
		);

		add_settings_field(
			'reporting_form_page',
			'Reporting Form Page',
			array($this, 'reporting_form_page_callback'),
			'sandbaai_crime_general',
			'sandbaai_crime_general_section'
		);

		// Register WhatsApp notification settings
		register_setting('sandbaai_crime_whatsapp', 'sandbaai_crime_whatsapp_options');
		
		add_settings_section(
			'sandbaai_crime_whatsapp_section',
			'WhatsApp Notification Settings',
			array($this, 'whatsapp_settings_section_callback'),
			'sandbaai_crime_whatsapp'
		);
		
		add_settings_field(
			'whatsapp_api_key',
			'WhatsApp API Key',
			array($this, 'whatsapp_api_key_callback'),
			'sandbaai_crime_whatsapp',
			'sandbaai_crime_whatsapp_section'
		);

		add_settings_field(
			'whatsapp_template_message',
			'WhatsApp Template Message',
			array($this, 'whatsapp_template_message_callback'),
			'sandbaai_crime_whatsapp',
			'sandbaai_crime_whatsapp_section'
		);

		// Register Map settings
		register_setting('sandbaai_crime_map', 'sandbaai_crime_map_options');
		
		add_settings_section(
			'sandbaai_crime_map_section',
			'Map Settings',
			array($this, 'map_settings_section_callback'),
			'sandbaai_crime_map'
		);
		
		add_settings_field(
			'map_center_lat',
			'Default Map Center Latitude',
			array($this, 'map_center_lat_callback'),
			'sandbaai_crime_map',
			'sandbaai_crime_map_section'
		);

		add_settings_field(
			'map_center_lng',
			'Default Map Center Longitude',
			array($this, 'map_center_lng_callback'),
			'sandbaai_crime_map',
			'sandbaai_crime_map_section'
		);

		add_settings_field(
			'map_zoom_level',
			'Default Map Zoom Level',
			array($this, 'map_zoom_level_callback'),
			'sandbaai_crime_map',
			'sandbaai_crime_map_section'
		);
	}

	/**
	 * Display the main admin page
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/sandbaai-crime-admin-display.php';
	}

	/**
	 * Display the crime reports admin page
	 *
	 * @since    1.0.0
	 */
	public function display_crime_reports_page() {
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/sandbaai-crime-reports-display.php';
	}

	/**
	 * Display the security groups admin page
	 *
	 * @since    1.0.0
	 */
	public function display_security_groups_page() {
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/sandbaai-security-groups-display.php';
	}

	/**
	 * Display the WhatsApp settings admin page
	 *
	 * @since    1.0.0
	 */
	public function display_whatsapp_settings_page() {
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/sandbaai-whatsapp-settings-display.php';
	}

	/**
	 * Settings section callbacks
	 */
	public function general_settings_section_callback() {
		echo '<p>Configure general settings for the Sandbaai Crime plugin.</p>';
	}

	public function whatsapp_settings_section_callback() {
		echo '<p>Configure WhatsApp notification settings. These settings are used to send alerts when new crime reports are submitted.</p>';
	}

	public function map_settings_section_callback() {
		echo '<p>Configure map settings for displaying crime statistics.</p>';
	}

	/**
	 * Settings field callbacks - General
	 */
	public function enable_crime_reporting_callback() {
		$options = get_option('sandbaai_crime_general_options');
		$checked = isset($options['enable_crime_reporting']) ? $options['enable_crime_reporting'] : 1;
		?>
		<input type="checkbox" id="enable_crime_reporting" name="sandbaai_crime_general_options[enable_crime_reporting]" value="1" <?php checked(1, $checked, true); ?> />
		<label for="enable_crime_reporting">Enable crime reporting form on the frontend</label>
		<?php
	}

	public function reporting_form_page_callback() {
		$options = get_option('sandbaai_crime_general_options');
		$selected_page = isset($options['reporting_form_page']) ? $options['reporting_form_page'] : 0;
		
		wp_dropdown_pages(array(
			'name' => 'sandbaai_crime_general_options[reporting_form_page]',
			'selected' => $selected_page,
			'show_option_none' => 'Select a page',
			'option_none_value' => '0'
		));
		echo '<p class="description">Select the page where the crime reporting form will be displayed.</p>';
	}

	/**
	 * Settings field callbacks - WhatsApp
	 */
	public function whatsapp_api_key_callback() {
		$options = get_option('sandbaai_crime_whatsapp_options');
		$api_key = isset($options['whatsapp_api_key']) ? $options['whatsapp_api_key'] : '';
		?>
		<input type="text" id="whatsapp_api_key" name="sandbaai_crime_whatsapp_options[whatsapp_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
		<p class="description">Enter your WhatsApp API key for sending notifications.</p>
		<?php
	}

	public function whatsapp_template_message_callback() {
		$options = get_option('sandbaai_crime_whatsapp_options');
		$template = isset($options['whatsapp_template_message']) ? $options['whatsapp_template_message'] : 'Crime Alert: {crime_type} reported at {location} at {time}. Details: {description}';
		?>
		<textarea id="whatsapp_template_message" name="sandbaai_crime_whatsapp_options[whatsapp_template_message]" rows="4" class="large-text"><?php echo esc_textarea($template); ?></textarea>
		<p class="description">Customize the message template for WhatsApp notifications. Available placeholders: {crime_type}, {location}, {time}, {description}, {reporter_name}.</p>
		<?php
	}

	/**
	 * Settings field callbacks - Map
	 */
	public function map_center_lat_callback() {
		$options = get_option('sandbaai_crime_map_options');
		$lat = isset($options['map_center_lat']) ? $options['map_center_lat'] : '-34.4131';
		?>
		<input type="text" id="map_center_lat" name="sandbaai_crime_map_options[map_center_lat]" value="<?php echo esc_attr($lat); ?>" />
		<p class="description">Default latitude for map center (e.g., -34.4131 for Sandbaai)</p>
		<?php
	}

	public function map_center_lng_callback() {
		$options = get_option('sandbaai_crime_map_options');
		$lng = isset($options['map_center_lng']) ? $options['map_center_lng'] : '19.2262';
		?>
		<input type="text" id="map_center_lng" name="sandbaai_crime_map_options[map_center_lng]" value="<?php echo esc_attr($lng); ?>" />
		<p class="description">Default longitude for map center (e.g., 19.2262 for Sandbaai)</p>
		<?php
	}

	public function map_zoom_level_callback() {
		$options = get_option('sandbaai_crime_map_options');
		$zoom = isset($options['map_zoom_level']) ? $options['map_zoom_level'] : '14';
		?>
		<input type="number" id="map_zoom_level" name="sandbaai_crime_map_options[map_zoom_level]" value="<?php echo esc_attr($zoom); ?>" min="1" max="20" />
		<p class="description">Default zoom level for the map (1-20, 14 recommended for neighborhood view)</p>
		<?php
	}

	/**
	 * Add meta boxes for the crime report post type
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'crime_report_details',
			'Crime Report Details',
			array($this, 'render_crime_report_metabox'),
			'crime_report',
			'normal',
			'high'
		);

		add_meta_box(
			'crime_report_location',
			'Crime Location',
			array($this, 'render_crime_location_metabox'),
			'crime_report',
			'normal',
			'high'
		);
	}

	/**
	 * Render crime report meta box
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    The post object.
	 */
	public function render_crime_report_metabox($post) {
		// Add nonce for security
		wp_nonce_field('crime_report_meta_box', 'crime_report_meta_box_nonce');
		
		// Get saved values
		$crime_type = get_post_meta($post->ID, '_crime_type', true);
		$crime_date = get_post_meta($post->ID, '_crime_date', true);
		$crime_time = get_post_meta($post->ID, '_crime_time', true);
		$reporter_name = get_post_meta($post->ID, '_reporter_name', true);
		$reporter_contact = get_post_meta($post->ID, '_reporter_contact', true);

		// Crime types
		$crime_types = array(
			'break-in' => 'Break-in/Burglary',
			'theft' => 'Theft',
			'vehicle' => 'Vehicle Crime',
			'vandalism' => 'Vandalism',
			'suspicious' => 'Suspicious Activity',
			'other' => 'Other'
		);
		?>
		
		<div class="crime-report-field">
			<label for="crime_type"><strong>Crime Type:</strong></label>
			<select id="crime_type" name="crime_type">
				<option value="">Select crime type</option>
				<?php foreach ($crime_types as $value => $label) : ?>
					<option value="<?php echo esc_attr($value); ?>" <?php selected($crime_type, $value); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="crime-report-field">
			<label for="crime_date"><strong>Date of Incident:</strong></label>
			<input type="date" id="crime_date" name="crime_date" value="<?php echo esc_attr($crime_date); ?>" />
		</div>

		<div class="crime-report-field">
			<label for="crime_time"><strong>Time of Incident:</strong></label>
			<input type="time" id="crime_time" name="crime_time" value="<?php echo esc_attr($crime_time); ?>" />
		</div>

		<div class="crime-report-field">
			<label for="reporter_name"><strong>Reporter Name:</strong></label>
			<input type="text" id="reporter_name" name="reporter_name" value="<?php echo esc_attr($reporter_name); ?>" />
		</div>

		<div class="crime-report-field">
			<label for="reporter_contact"><strong>Reporter Contact:</strong></label>
			<input type="text" id="reporter_contact" name="reporter_contact" value="<?php echo esc_attr($reporter_contact); ?>" />
		</div>
		<?php
	}

	/**
	 * Render crime location meta box
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    The post object.
	 */
	public function render_crime_location_metabox($post) {
		// Get saved values
		$latitude = get_post_meta($post->ID, '_crime_latitude', true);
		$longitude = get_post_meta($post->ID, '_crime_longitude', true);
		$address = get_post_meta($post->ID, '_crime_address', true);
		
		// Get map default settings
		$map_options = get_option('sandbaai_crime_map_options');
		$default_lat = isset($map_options['map_center_lat']) ? $map_options['map_center_lat'] : '-34.4131';
		$default_lng = isset($map_options['map_center_lng']) ? $map_options['map_center_lng'] : '19.2262';
		
		// Use saved values or defaults
		$lat = !empty($latitude) ? $latitude : $default_lat;
		$lng = !empty($longitude) ? $longitude : $default_lng;
		?>
		
		<div class="crime-report-field">
			<label for="crime_address"><strong>Address:</strong></label>
			<input type="text" id="crime_address" name="crime_address" value="<?php echo esc_attr($address); ?>" class="large-text" />
		</div>

		<div class="crime-report-field">
			<div class="coordinate-inputs">
				<label for="crime_latitude"><strong>Latitude:</strong></label>
				<input type="text" id="crime_latitude" name="crime_latitude" value="<?php echo esc_attr($lat); ?>" />
				
				<label for="crime_longitude"><strong>Longitude:</strong></label>
				<input type="text" id="crime_longitude" name="crime_longitude" value="<?php echo esc_attr($lng); ?>" />
			</div>
		</div>

		<div id="crime-location-map" style="height: 300px; margin-top: 10px; border: 1px solid #ddd;"></div>
		<p class="description">Click on the map to set the exact location of the incident.</p>

		<script>
		jQuery(document).ready(function($) {
			// Initialize the map
			var map = L.map('crime-location-map').setView([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>], 14);
			
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
			}).addTo(map);
			
			// Add marker
			var marker = L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>], {
				draggable: true
			}).addTo(map);
			
			// Update coordinates when marker is dragged
			marker.on('dragend', function(e) {
				var position = marker.getLatLng();
				$('#crime_latitude').val(position.lat);
				$('#crime_longitude').val(position.lng);
				
				// Try to get address from coordinates
				reverseGeocode(position.lat, position.lng);
			});
			
			// Add click event on map
			map.on('click', function(e) {
				marker.setLatLng(e.latlng);
				$('#crime_latitude').val(e.latlng.lat);
				$('#crime_longitude').val(e.latlng.lng);
				
				// Try to get address from coordinates
				reverseGeocode(e.latlng.lat, e.latlng.lng);
			});
			
			// Function to get address from coordinates
			function reverseGeocode(lat, lng) {
				$.get('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng, function(data) {
					if (data && data.display_name) {
						$('#crime_address').val(data.display_name);
					}
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Save crime report meta data
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    The post ID.
	 */
	public function save_meta_data($post_id) {
		// Check if our nonce is set
		if (!isset($_POST['crime_report_meta_box_nonce'])) {
			return;
		}
		
		// Verify the nonce
		if (!wp_verify_nonce($_POST['crime_report_meta_box_nonce'], 'crime_report_meta_box')) {
			return;
		}
		
		// If this is an autosave, don't do anything
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		// Check user permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		
		// Save crime report fields
		if (isset($_POST['crime_type'])) {
			update_post_meta($post_id, '_crime_type', sanitize_text_field($_POST['crime_type']));
		}
		
		if (isset($_POST['crime_date'])) {
			update_post_meta($post_id, '_crime_date', sanitize_text_field($_POST['crime_date']));
		}
		
		if (isset($_POST['crime_time'])) {
			update_post_meta($post_id, '_crime_time', sanitize_text_field($_POST['crime_time']));
		}
		
		if (isset($_POST['reporter_name'])) {
			update_post_meta($post_id, '_reporter_name', sanitize_text_field($_POST['reporter_name']));
		}
		
		if (isset($_POST['reporter_contact'])) {
			update_post_meta($post_id, '_reporter_contact', sanitize_text_field($_POST['reporter_contact']));
		}
		
		// Save location fields
		if (isset($_POST['crime_address'])) {
			update_post_meta($post_id, '_crime_address', sanitize_text_field($_POST['crime_address']));
		}
		
		if (isset($_POST['crime_latitude'])) {
			update_post_meta($post_id, '_crime_latitude', sanitize_text_field($_POST['crime_latitude']));
		}
		
		if (isset($_POST['crime_longitude'])) {
			update_post_meta($post_id, '_crime_longitude', sanitize_text_field($_POST['crime_longitude']));
		}
	}

	/**
	 * Add custom columns to crime report list view
	 *
	 * @since    1.0.0
	 * @param    array    $columns    The default columns.
	 * @return   array    $columns    The modified columns.
	 */
	public function set_custom_columns($columns) {
		$new_columns = array();
		
		// Add ID column after checkbox
		$new_columns['cb'] = $columns['cb'];
		$new_columns['id'] = 'ID';
		
		// Add other custom columns
		$new_columns['title'] = $columns['title'];
		$new_columns['crime_type'] = 'Crime Type';
		$new_columns['crime_date'] = 'Date/Time';
		$new_columns['location'] = 'Location';
		$new_columns['reporter'] = 'Reporter';
		$new_columns['date'] = $columns['date'];
		
		return $new_columns;
	}

	/**
	 * Display custom column content
	 *
	 * @since    1.0.0
	 * @param    string    $column    The column name.
	 * @param    int       $post_id   The post ID.
	 */
	public function display_custom_columns($column, $post_id) {
		switch ($column) {
			case 'id':
				echo $post_id;
				break;
				
			case 'crime_type':
				$crime_type = get_post_meta($post_id, '_crime_type', true);
				$crime_types = array(
					'break-in' => 'Break-in/Burglary',
					'theft' => 'Theft',
					'vehicle' => 'Vehicle Crime',
					'vandalism' => 'Vandalism',
					'suspicious' => 'Suspicious Activity',
					'other' => 'Other'
				);
				echo isset($crime_types[$crime_type]) ? esc_html($crime_types[$crime_type]) : esc_html($crime_type);
				break;
				
			case 'crime_date':
				$date = get_post_meta($post_id, '_crime_date', true);
				$time = get_post_meta($post_id, '_crime_time', true);
				echo esc_html($date) . '<br>' . esc_html($time);
				break;
				
			case 'location':
				$address = get_post_meta($post_id, '_crime_address', true);
				echo esc_html($address);
				break;
				
			case 'reporter':
				$name = get_post_meta($post_id, '_reporter_name', true);
				$contact = get_post_meta($post_id, '_reporter_contact', true);
				echo esc_html($name) . '<br>' . esc_html($contact);
				break;
		}
	}

	/**
	 * Export crime reports as CSV
	 *
	 * @since    1.0.0
	 */
	public function export_crime_reports() {
		// Check if export is requested
		if (isset($_POST['export_crime_reports']) && current_user_can('manage_options')) {
			// Set headers for download
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=crime-reports-' . date('Y-m-d') . '.csv');
			
			// Create output stream
			$output = fopen('php://output', 'w');
			
			// Add CSV headers
			fputcsv($output, array(
				'ID',
				'Title',
				'Crime Type',
				'Date',
				'Time',
				'Address',
				'Latitude',
				'Longitude',
				'Reporter Name',
				'Reporter Contact',
				'Description',
				'Status',
				'Date Submitted'
			));
			
			// Get all crime reports
			$args = array(
				'post_type' => 'crime_report',
				'posts_per_page' => -1,
				'post_status' => 'any'
			);
			
			$reports = get_posts($args);
			
			// Output each report as CSV row
			foreach ($reports as $report) {
				$crime_type = get_post_meta($report->ID, '_crime_type', true);
				$crime_date = get_post_meta($report->ID, '_crime_date', true);
				$crime_time = get_post_meta($report->ID, '_crime_time', true);
				$address = get_post_meta($report->ID, '_crime_address', true);
				$latitude = get_post_meta($report->ID, '_crime_latitude', true);
				$longitude = get_post_meta($report->ID, '_crime_longitude', true);
				$reporter_name = get_post_meta($report->ID, '_reporter_name', true);
				$reporter_contact = get_post_meta($report->ID, '_reporter_contact', true);
				
				fputcsv($output, array(
					$report->ID,
					$report->post_title,
					$crime_type,
					$crime_date,
					$crime_time,
					$address,
					$latitude,
					$longitude,
					$reporter_name,
					$reporter_contact,
					$report->post_content,
					$report->post_status,
					$report->post_date
				));
			}
			
			fclose($output);
			exit;
		}
	}
}