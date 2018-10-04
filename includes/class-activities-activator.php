<?php

/**
 * Fired during plugin activation
 *
 * @link       mikal.bindu.no
 * @since      1.0.0
 *
 * @package    Activities
 * @subpackage Activities/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Activator {

	/**
	 * Creates tables and adds capabilities to administrators
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once dirname( __FILE__ ) . '/class-activities-installer.php';

		$installer = new Activities_Installer;
		$installer->install_all_default_tables();
		$installer->add_capabilities();
	}
}
