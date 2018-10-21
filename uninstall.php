<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since 		 1.0.0
 * @package    Activities
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

// If uninstall not called from WordPress, then exit.
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Get some required files for uninstalling
require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-woocommerce.php'; // Archive actions and delete actions
require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-responsible.php'; // Remove responsibility
require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities-options.php'; 		// Delete all options
require_once plugin_dir_path( __FILE__ ) . 'includes/class-activities.php'; 						// Get table names
require_once plugin_dir_path( __FILE__ ) . 'includes/activities-constants.php';

if ( !is_multisite() ) {
	activtities_uninstall_site();
}
else {
	$sites = get_sites();
	foreach ($sites as $site) {
		switch_to_blog( $site->blog_id );
		activtities_uninstall_site();
		restore_current_blog();
	}
}

function activtities_uninstall_site() {
	if ( !Activities_Options::get_option( ACTIVITIES_DELETE_DATA_KEY ) ) {
		return;
	}
	global $wpdb, $wp_roles;

	foreach (array_keys( $wp_roles->get_names() ) as $r_key) {
		$wp_roles->remove_cap( $r_key, ACTIVITIES_ACCESS_ACTIVITIES );
		$wp_roles->remove_cap( $r_key, ACTIVITIES_ADMINISTER_ACTIVITIES );
		$wp_roles->remove_cap( $r_key, ACTIVITIES_ADMINISTER_OPTIONS );
	}

	Activities_Responsible::remove_all_users_responsiblity();

	$activity_table = Activities::get_table_name( 'activity' );
	$a_ids = $wpdb->get_col(
		"SELECT activity_id
		FROM $activity_table
		WHERE archive = 0
		"
	);

	foreach ($a_ids as $id) {
		do_action( 'activities_archive_activity', $id );
	}

	$aa_ids = $wpdb->get_col(
		"SELECT activity_id
		FROM $activity_table
		WHERE archive = 1
		"
	);

	foreach ($aa_ids as $id) {
		do_action( 'activities_delete_activity', $id );
	}

	$tables = array(
		$activity_table,
		Activities::get_table_name( 'activity_meta' ),
		Activities::get_table_name( 'user_activity' ),
		Activities::get_table_name( 'location' )
	);

	foreach ($tables as $table_name) {
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}

	Activities_Options::flush_options();
	delete_option( 'activities_db_version' );
}
