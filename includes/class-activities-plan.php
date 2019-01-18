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
   * Retrieve property by name, accepted input:
   *    - plan_id | id | ID
   *    - name
   *    - description
   *    - sessions
   *    - session_map
   *
   * @param   string  $name Proprety to get
   * @return  mixed   Data found for $name key, '' if not data was found
   */
  function __get( $name ) {
    if ( !is_array( $this->plan ) ) {
      return '';
    }

    global $wpdb;

    switch ($name) {
      case 'plan_id':
      case 'name':
      case 'description':
      case 'sessions':
      case 'session_map':
        return $this->plan[$name];
        break;

      case 'id':
      case 'ID':
        return $this->plan['plan_id'];
        break;

      default:
        return '';
        break;
    }
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

    if ( $plan_id && array_key_exists( 'session_map', $plan_map ) ) {
      foreach ($plan_map['session_map'] as $session => $text) {
        Activities_Plan_Session::insert( $plan_id, $session, $text );
      }
    }

    return $plan_id;
  }

  /**
   * Update a plan
   *
   * @param   array     $plan_map
   * @return  bool  False if it could not be updated, True otherwise
   */
  static function update( $plan_map ) {
    global $wpdb;
    $update = Activities_Item::update( 'plan', $plan_map );

    if ( $update && array_key_exists( 'session_map', $plan_map ) ) {
      $plan_id = $plan_map['plan_id'];

      $current_session_count = count( $plan_map['session_map'] );
      $plan_session_table = Activities::get_table_name( 'plan_session' );
      $prev_sessions_count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*)
        FROM $plan_session_table
        WHERE plan_id = %d
        ",
        $plan_id
      ));

      for ($session_id=1; $session_id <= $current_session_count; $session_id++) {
        if ( $session_id > $prev_sessions_count || !Activities_Plan_Session::exists( $plan_id, $session_id ) ) {
          Activities_Plan_Session::insert( $plan_id, $session_id, $plan_map['session_map'][$session_id] );
        }
        else {
          Activities_Plan_Session::update( $plan_id, $session_id, $plan_map['session_map'][$session_id] );
        }
      }

      if ( $prev_sessions_count > $current_session_count ) {
        for ($session_id=$current_session_count+1; $session_id <= $prev_sessions_count; $session_id++) {
          Activities_Plan_Session::delete( $plan_id, $session_id);
        }
      }
    }

    return $update;
  }

  /**
   * Reads plan data from the database
   *
   * @param   int         $plan_id Plan id
   * @return  array|null  Associative array of plan info, or null if error
   */
  static function load( $plan_id ) {
    $plan = Activities_Item::load( 'plan', $plan_id );

    if ( $plan != null ) {
      $plan['session_map'] = Activities_Plan_Session::get_map( $plan_id );
    }

    return $plan;
  }

  /**
   * Delete a plan
   *
   * @param   array     $plan_map
   * @return  bool      True if the plan was deleted, false otherwise
   */
  static function delete( $plan_id ) {
    global $wpdb;
    $del = Activities_Item::delete( 'plan', $plan_id );

    if ( $del ) {
      $wpdb->delete(
        Activities::get_table_name( 'plan_session' ),
        array( 'plan_id' => $plan_id ),
        array( '%d' )
      );
    }

    return $del;
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
