<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://github.com/Loderian/Activities
 * @since             1.0.0
 * @package           Activities
 *
 * @wordpress-plugin
 * Plugin Name:       Activities
 * Plugin URI:        https://github.com/Loderian/Activities
 * Description:       A plugin for managing activities, activity reports and communication with participants. Comes with WooCommerce integration.
 * Version:           1.1.8
 * Author:            Mikal Naustdal
 * Author URI:        https://github.com/Loderian
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0-standalone.html
 * Text Domain:       activities
 * Domain Path:       /languages
 *
 * WC tested up to: 3.9.2
 */

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Current plugin version
 */
define( 'ACTIVITIES_VERSION', '1.1.8' );
define( 'ACTIVITIES_DB_VERSION', '1.1.1' );

/**
 * Activities activation
 *
 * @param $nerwork_wide
 */
function activities_activate( $nerwork_wide ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-activator.php';
    if ( is_multisite() && $nerwork_wide ) {
        $sites = get_sites();
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            Activities_Activator::activate();
            restore_current_blog();
        }
    } else {
        Activities_Activator::activate();
    }
}

/**
 * Activities deactivation
 */
function activities_deactivate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-deactivator.php';
    Activities_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activities_activate' );
register_deactivation_hook( __FILE__, 'activities_deactivate' );

/**
 * Installs a the plugin on a new blog
 */
function activities_install_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( 'activities/activities.php' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-activator.php';
        switch_to_blog( $blog_id );
        Activities_Activator::activate();
        restore_current_blog();
    }
}

add_action( 'wpmu_new_blog', 'activities_install_on_new_blog', 10, 6 );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-activities.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function activities_run() {
    $plugin = new Activities();
    $plugin->run();
}

activities_run();
