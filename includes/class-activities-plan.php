<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Plan class
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Plan {
  /**
   * Plan data
   *
   * @var array
   */
  protected $plan;

  /**
   * Loads a plan by id
   *
   * @param int $id Plan id
   */
  function __construct( $id ) {
    $this->plan = self::load( $id );
  }

  /**
   * Checks if a plan exists
   *
   * @param   int|string  $plan_id Plan identifier
   * @param   string      $check_by Column to compare ('id', 'name')
   * @return  bool        True if the plan exists, false otherwise
   */
  static function exists( $plan_id, $check_by = 'id' ) {
    return Activities_Item::exists( $plan_id, 'plan', $check_by );
  }

  /**
   * Insert a plan
   *
   * @param   array     $plan_map
   * @return  int|bool  Plan id or false
   */
  static function insert( $plan_map ) {
    $plan_id = Activities_Item::insert( 'plan', $plan_map );

    if ( $plan_id && array_key_exists( 'session_text', $plan_map ) ) {
      foreach ($plan_map['session_text'] as $session => $text) {
        self::insert_session_text( $plan_id, $session, $text );
      }
    }

    return $plan_id;
  }

  /**
   * Update a plan
   *
   * @param   array     $plan_map
   * @return  int|bool  Plan id or false
   */
  static function update( $plan_map ) {
    return Activities_Item::update( 'plan', $plan_map );
  }

  /**
   * Reads plan data from the database
   *
   * @param   int         $plan_id Plan id
   * @return  array|null  Associative array of plan info, or null if error
   */
  static function load( $plan_id ) {
    global $wpdb;

    $plan = Activities_Item::load( 'plan', $plan_id );

    if ( $plan != null ) {
      $session_table = Activities::get_table_name( 'plan_session' );
      $session_map = $wpdb->get_results( $wpdb->prepare(
          "SELECT session_id, text
          FROM $session_table
          WHERE plan_id = %d
          ",
          $plan_id
        ),
        OBJECT_K
      );

      var_dump( $session_map );

      $new_session_map = array();
      foreach ($session_map as $session_id => $text) {
        $new_session_map[$session_id] = $text->text;
      }

      $plan['session_map'] = $new_session_map;
    }

    return $plan;
  }

  /**
   * Adds a session text to a plan
   *
   * @param   int     $plan_id Plan id
   * @param   int     $session Slot number
   * @param   string  $text Text to put into the session
   * @return  bool
   */
  static function insert_session_text( $plan_id, $session, $text ) {
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
   * @param   int     $session Slot number
   * @param   string  $text Text to put into the session
   * @return  bool
   */
  static function update_session_text( $plan_id, $session, $text ) {
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
   * Delete a plan
   *
   * @param   array     $plan_map
   * @return  bool      True if the plan was deleted, false otherwise
   */
  static function delete( $plan_id ) {
    return Activities_Item::delete( 'plan', $plan_id );
  }



  /**
   * Get all the columns uses to store plans
   *
   * @param   string    $type Column types to get ('string' or 'int')
   * @return  array     Array of column names
   */
  static function get_columns( $type = 'none' ) {
    $strings = array( 'name', 'description' );
    $ints = array( 'sessions' );

    switch ($type) {
      case 'string':
      case 'strings':
        return $strings;
        break;

      case 'int':
      case 'ints':
        return $ints;
        break;

      case 'none':
      default:
        return array_merge( $strings, $ints );
        break;
    }
  }
}
