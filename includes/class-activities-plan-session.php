<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Plan session relation class
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Plan_Session {
  /**
   * Add actions related to this class
   */
  static function init() {
  }

  /**
   * Checks if a plan session relation exists
   *
   * @param   int   $plan_id Plan relation to check
   * @param   int   $session_id Session relation to check
   * @return  bool  True if it exists, false otherwise
   */
  static function exists( $plan_id, $session_id ) {
    global $wpdb;

    $table = Activities::get_table_name( 'plan_session' );

    $exists = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*)
      FROM $table
      WHERE plan_id = %d AND session_id = %d
      ",
      array( $plan_id, $session_id )
    ));

    return $exists >= 1;
  }

  /**
   * Adds a session text to a plan
   *
   * @param   int     $plan_id Plan id
   * @param   int     $session Session number
   * @param   string  $text Text to put into the session
   * @return  bool
   */
  static function insert( $plan_id, $session, $text ) {
    global $wpdb;

    $insert = $wpdb->insert(
      Activities::get_table_name( 'plan_session' ),
      array(
        'plan_id' => $plan_id,
        'session_id' => $session,
        'text' => $text
      ),
      array( '%d', '%d', '%s' )
    );

    return $insert;
  }

  /**
   * Updates a session text in a plan
   *
   * @param   int     $plan_id Plan id
   * @param   int     $session Session number
   * @param   string  $text Text to put into the session
   * @return  bool
   */
  static function update( $plan_id, $session, $text ) {
    global $wpdb;

    $update = $wpdb->update(
      Activities::get_table_name( 'plan_session' ),
      array( 'text' => $text ),
      array(
        'plan_id' => $plan_id,
        'session_id' => $session,
      ),
      array( '%s' ),
      array( '%d', '%d' )
    );

    return $update;
  }

  /**
   * Deletes a relation between plan and session
   *
   * @param   int       $plan_id Plan relation to delete
   * @param   int       $session_id Session relation to delete
   * @return  int|bool  1 if success, false on error
   */
  static function delete( $plan_id, $session_id ) {
    global $wpdb;

    return $wpdb->delete(
      Activities::get_table_name( 'plan_session' ),
      array( 'plan_id' => $plan_id, 'session_id' => $session_id ),
      array( '%d', '%d' )
    );
  }

  /**
   * Gets sessions texts for a plan
   *
   * @param   int     $user_id User to find activities for
   * @param   string  $archive Set to 'archive' for archived activities, defaults to active activities
   * @return  array   List of activity ids
   */
  static function get_map( $plan_id ) {
    global $wpdb;

    $table = Activities::get_table_name( 'plan_session' );
    $session_map = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, text
        FROM $table
        WHERE plan_id = %d
        ",
        $plan_id
      ),
      OBJECT_K
    );

    $new_session_map = array();
    foreach ($session_map as $session_id => $text) {
      $new_session_map[$session_id] = $text->text;
    }

    return $new_session_map;
  }
}
Activities_User_Activity::init();
