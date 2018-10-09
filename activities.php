<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://profiles.wordpress.org/loderian
 * @since             1.0.0
 * @package           Activities
 *
 * @wordpress-plugin
 * Plugin Name:       Activities
 * Plugin URI:        https://profiles.wordpress.org/loderian
 * Description:       A plugin for administering activities, printing reports and exporting user data.
 * Version:           1.0.0
 * Author:            Mikal Naustdal
 * Author URI:        https://github.com/Loderian
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0-standalone.html
 * Text Domain:       activities
 * Domain Path:       /languages
 */

if ( !defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'ACTIVITIES_VERSION', '1.0.1' );
define( 'ACTIVITIES_DB_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
function activities_activate( $nerwork_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-activator.php';
	if ( is_multisite() && $nerwork_wide ) {
		$sites = get_sites();
		foreach ($sites as $site) {
			switch_to_blog( $site->blog_id );
			Activities_Activator::activate();
			restore_current_blog();
		}
	}
	else {
		Activities_Activator::activate();
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function activities_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-deactivator.php';
	Activities_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activities_activate' );
register_deactivation_hook( __FILE__, 'activities_deactivate' );

function activities_install_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	if ( is_plugin_active_for_network( 'activities/activities.php' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-activator.php';
		switch_to_blog( $blog_id );
		Activities_Activator::activate();
		restore_current_blog();
	}
}

/**
 * New blog installation
 */
add_action( 'wpmu_new_blog', 'activities_install_on_new_blog', 10, 6 );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-activities.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function activities_run() {
	$plugin = new Activities();
	$plugin->run();
}
activities_run();
