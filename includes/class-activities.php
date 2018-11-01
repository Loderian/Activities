<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Activities_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
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
		if ( defined( 'ACTIVITIES_VERSION' ) ) {
			$this->version = ACTIVITIES_VERSION;
		}
		else {
			$this->version = '1.0.3';
		}
		$this->plugin_name = 'activities';

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
	 * - Activities_Loader. Orchestrates the hooks of the plugin.
	 * - Activities_i18n. Defines internationalization functionality.
	 * - Activities_Admin. Defines all hooks for the admin area.
	 * - Activities_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-activities-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-activities-public.php';

	  require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-responsible.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-activity.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-user-activity.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-location.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-pagination.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-options.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/activities-constants.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/activities-functions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-woocommerce.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-csv-importer.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-activities-utility.php';

		if ( is_admin() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-activities-admin.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-activity.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-options.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-activities.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-locations.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-archive.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-activity-nice.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-generic.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-location.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-export.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/activities-admin-import.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-activities-admin-utility.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-activities-bulk-action.php';
		}

		$this->loader = new Activities_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Activities_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Activities_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Activities_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_styles' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_scripts' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_woocommerce' );

		$this->loader->add_action( 'wp_ajax_acts_get_member_info', $plugin_admin, 'ajax_get_member_info' );
    $this->loader->add_action( 'wp_ajax_acts_get_user_info', $plugin_admin, 'ajax_get_user_info' );
    $this->loader->add_action( 'wp_ajax_acts_quick_save', $plugin_admin, 'ajax_acts_quick_save' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'activities_admin_menu' );

		$this->loader->add_action( 'show_user_profile', $plugin_admin, 'activities_add_user_option' );
		$this->loader->add_action( 'edit_user_profile', $plugin_admin, 'activities_add_user_option' );
		$this->loader->add_action( 'personal_options_update', $plugin_admin, 'activities_save_user_activities' );
		$this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'activities_save_user_activities' );

		$this->loader->add_filter( 'screen_settings', $plugin_admin, 'show_screen_options', 10, 2 );
		$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'set_screen_options', 10, 3 );

		$this->loader->add_filter( 'admit-init', $plugin_admin, 'show_help', 10, 2 );

		$this->loader->add_action( 'pre_user_query', $plugin_admin, 'better_user_search' );
		$this->loader->add_action( 'wp_login', $plugin_admin, 'remove_guest_flag', 10, 2 );
		$this->loader->add_filter( 'admin_title', $plugin_admin, 'set_activity_nice_title', 10, 2 );

		add_shortcode( 'acts', array( $plugin_admin, 'acts_shortcode' ) );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Activities_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'wp_ajax_acts_join', $plugin_public, 'ajax_join' );
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
	 * @return    Activities_Loader    Orchestrates the hooks of the plugin.
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

	/**
	 * Get activities SQL table name
	 *
	 * @param 	string $name Postfix of the table name
	 * @return 	string Full table name
	 */
	static function get_table_name( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'activities_' . $name;
	}
}
