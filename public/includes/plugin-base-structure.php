<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Sandbaai_Crime
 */

class Sandbaai_Crime {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Sandbaai_Crime_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier for this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = SANDBAAI_CRIME_VERSION;
        $this->plugin_name = 'sandbaai-crime';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Sandbaai_Crime_Loader. Orchestrates the hooks of the plugin.
     * - Sandbaai_Crime_i18n. Defines internationalization functionality.
     * - Sandbaai_Crime_Admin. Defines all hooks for the admin area.
     * - Sandbaai_Crime_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-i18n.php';

        /**
         * The class responsible for defining custom post types.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-post-types.php';

        /**
         * The class responsible for crime report functionality.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-crime-report.php';

        /**
         * The class responsible for security group functionality.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/class-security-group.php';

        /**
         * The class responsible for WhatsApp notifications.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'includes/whatsapp-notifications.php';

        /**
         * The class responsible for all admin-specific functionality.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'admin/class-admin.php';

        /**
         * The class responsible for all public-facing functionality.
         */
        require_once SANDBAAI_CRIME_PLUGIN_DIR . 'public/class-public.php';

        $this->loader = new Sandbaai_Crime_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Sandbaai_Crime_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Sandbaai_Crime_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Sandbaai_Crime_Admin($this->get_plugin_name(), $this->get_version());

        // Admin styles and scripts
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu and pages
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Register settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Meta boxes for custom post types
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_meta_boxes');
        $this->loader->add_action('save_post', $plugin_admin, 'save_meta_boxes', 10, 2);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Sandbaai_Crime_Public($this->get_plugin_name(), $this->get_version());

        // Public styles and scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // AJAX handlers for the crime reporting form
        $this->loader->add_action('wp_ajax_submit_crime_report', $plugin_public, 'process_crime_report_submission');
        $this->loader->add_action('wp_ajax_nopriv_submit_crime_report', $plugin_public, 'process_crime_report_submission');
        
        // AJAX handlers for the crime statistics
        $this->loader->add_action('wp_ajax_get_crime_statistics', $plugin_public, 'get_crime_statistics');
        $this->loader->add_action('wp_ajax_nopriv_get_crime_statistics', $plugin_public, 'get_crime_statistics');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Sandbaai_Crime_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
