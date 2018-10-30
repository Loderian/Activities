<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * User activity relation class
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_User_Activity {
  /**
   * Add actions related to this class
   */
  static function init() {
    add_action( 'deleted_user', array( __CLASS__, 'deleted_user' ) );
    add_action( 'remove_user_from_blog', array( __CLASS__, 'remove_user_from_blog' ), 10, 2 );
  }

  /**
   * Checks if a user activity relation exists
   *
   * @param   int   $user_id User relation to check
   * @param   int   $activity_id Activity relation to check
   * @return  bool  True if it exists, false otherwise
   */
  static function exists( $user_id, $activity_id ) {
    global $wpdb;

    $user_activity_table = Activities::get_table_name( 'user_activity' );

    $exists = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*)
      FROM $user_activity_table
      WHERE user_id = %d AND activity_id = %d
      ",
      array( $user_id, $activity_id )
    ));

    return $exists == 1;
  }

  /**
   * Inserts a relation between user and activity
   *
   * Archived activities will not be accepted unless $override is true
   *
   * @param   int       $user_id User relation to insert
   * @param   int       $activity_id Activity relation to insert
   * @param   bool      $override To override archive check, used by importers
   * @return  int|bool  1 if success, false on error
   */
  static function insert( $user_id, $activity_id, $override = false ) {
    global $wpdb;

    $user_exists = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*)
      FROM $wpdb->users
      WHERE ID = %d
      ",
      $user_id
    ));

    if (
      $user_exists == 1
      && Activities_Activity::exists( $activity_id )
      && ( !Activities_Activity::is_archived( $activity_id ) || $override )
      && !self::exists( $user_id, $activity_id )
    ) {
      return $wpdb->insert(
        Activities::get_table_name( 'user_activity' ),
        array( 'user_id' => $user_id, 'activity_id' => $activity_id),
        array( '%d', '%d' )
      );
    }
    else {
      return false;
    }
  }

  /**
   * Deletes a relation between user and activity
   *
   * Can delete relations on a archived activity, for example if a user is deleted.
   *
   * @param   int       $user_id User relation to delete
   * @param   int       $activity_id Activity relation to delete
   * @return  int|bool  1 if success, false on error
   */
  static function delete( $user_id, $activity_id ) {
    global $wpdb;

    $user_activity_table = Activities::get_table_name( 'user_activity' );

    return $wpdb->delete(
      $user_activity_table,
      array( 'user_id' => $user_id, 'activity_id' => $activity_id ),
      array( '%d', '%d' )
    );
  }

  /**
   * Inserts if a new relation is found
   * Deletes a relation if it's not found
   *
   * @param   string|array    $value String of comma seperated ids to enter or array of ids
   * @param   int             $static_id Id from where the change was made (user_id on user pages or activity_id from activity edit page)
   * @param   string          $static_field Name of the $static_id (user_id or activity_id)
   * @return  int             Number of changes made
   */
  static function insert_delete( $value, $static_id, $static_field ) {
    global $wpdb;

    if ( $static_field === 'user_id' ) {
      $enter_field = 'activity_id';
    }
    else {
      $enter_field = 'user_id';
    }

    $table_name = Activities::get_table_name( 'user_activity' );

    $present_values = $wpdb->get_col( $wpdb->prepare(
      "SELECT $enter_field
      FROM $table_name
      WHERE $static_field = %d
      ",
      $static_id
    ));

    $entered_values = array();
    if ( is_string( $value ) ) {
      $entered_values = explode( ',', $value );
    }
    elseif ( is_array( $value ) ) {
      $entered_values = $value;
    }

    if ( count( $present_values ) == 0 && count( $entered_values ) == 0 ){
      return 0;
    }

    $changes = 0;
    foreach ($entered_values as $enter_id) {
      $key = array_search( $enter_id, $present_values );
      if ( $key === false ) {
        if ( $static_field === 'user_id' ) {
          $changes += self::insert( $static_id, $enter_id );
        }
        elseif ( $static_field === 'activity_id' ) {
          $changes += self::insert( $enter_id, $static_id );
        }
      }
      else {
        unset( $present_values[$key] );
      }
    }

    foreach ($present_values as $del_id) {
      if ( $static_field === 'user_id' ) {
        $changes += self::delete( $static_id, $del_id );
      }
      elseif ( $static_field === 'activity_id' ) {
        $changes += self::delete( $del_id, $static_id );
      }
    }

    return $changes;
  }

  /**
   * Callback for user deletion
   *
   * Removes all relations for user with $user_id
   *
   * @param   int   $user_id User id
   */
  static function deleted_user( $user_id ) {
    global $wpdb;

    $user_activity_table = Activities::get_table_name( 'user_activity' );

    $a_ids = $wpdb->get_col( $wpdb->prepare(
      "SELECT activity_id
      FROM $user_activity_table
      WHERE user_id = %d
      ",
      $user_id
    ));

    foreach ($a_ids as $activity_id) {
      self::delete( $user_id, $activity_id );
    }
  }

  /**
   * Callback for blog user deletion
   *
   * Switches to $blog_id and removes user relations
   *
   * @param   int   $user_id User id
   * @param   int   $blog_id Blog id
   */
  static function remove_user_from_blog( $user_id, $blog_id ) {
    if ( is_multisite() ) {
      switch_to_blog( $blog_id );
    }

    global $wpdb;
    $user_activity_table = Activities::get_table_name( 'user_activity' );
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$user_activity_table'") == $user_activity_table ) {
      self::deleted_user( $user_id );
    }

    if ( is_multisite() ) {
      restore_current_blog();
    }
  }

  /**
   * Gets all activities related to a user
   *
   * @param   int     $user_id User to find activities for
   * @param   string  $archive Set to 'archive' for archived activities, defaults to active activities
   * @return  array   List of activity ids
   */
  static function get_user_activities( $user_id, $archive = '' ) {
    global $wpdb;

    $archive = $archive == 'archive' ? 1 : 0;
    $activity_table = Activities::get_table_name( 'activity' );
    $user_activity_table = Activities::get_table_name( 'user_activity' );

    $activities = $wpdb->get_col( $wpdb->prepare(
      "SELECT activity_id
      FROM $activity_table
      WHERE archive = %d AND activity_id IN (
        SELECT activity_id FROM $user_activity_table WHERE user_id = %d
      )
      ",
      array( $archive, $user_id )
    ));

    return $activities;
  }

  /**
   * Gets all user related to an activity
   *
   * @param   int     $act_id Activity to find user for
   * @return  array   List of user ids
   */
  static function get_activity_users( $act_id ) {
    global $wpdb;

    $user_activity_table = Activities::get_table_name( 'user_activity' );

    $users = $wpdb->get_col( $wpdb->prepare(
      "SELECT user_id
      FROM $user_activity_table
      WHERE activity_id = %d
      ",
      $act_id
    ));

    return $users;
  }
}
Activities_User_Activity::init();
