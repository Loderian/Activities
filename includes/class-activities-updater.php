<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Checks version number and updates
 *
 * @since      1.0.5
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Updater {
  /**
   * List of updates
   *
   * @var array
   */
  static $db_updates = array(
    '1.0.1' => array( __CLASS__, 'db_update_1_0_1' )
  );

  static function init() {
    add_action( 'admin_init', array( __CLASS__, 'update' ), 99 );
  }

  /**
   * Update to the newset version
   */
  static function update() {
    $installed_ver = get_option( 'activities_db_version' );
    if ( version_compare( $installed_ver, ACTIVITIES_DB_VERSION ) >= 0 ) {
      return;
    }

    foreach (self::$db_updates as $update_ver => $callback) {
      if ( version_compare( $update_ver, $installed_ver ) > 0 ) {
        if ( call_user_func( $callback ) ) {
          update_option( 'activities_db_version', $update_ver );
          $installed_ver = $update_ver;
        }
        else {
          //If an update was unsuccessful, try again later
          return;
        }
      }
    }
  }

  /**
   * Update db to version 1.0.1
   *
   * @return bool Returns true on successful update
   */
  static function db_update_1_0_1() {
    return Activities_Category::add_uncategorized();
  }
}
Activities_Updater::init();
