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
   * Checks if a plan exists
   *
   * @param   int|string  $plan_id Plan identifier
   * @param   string      $check_by Column to compare ('id', 'name')
   * @return  bool        True if the plan exists, false otherwise
   */
  static function exists( $act_id, $check_by = 'id' ) {
    return Activities_Item::exists( $plan_id, 'plan', $check_by );
  }

  /**
   * Insert a plan
   *
   * @param   array     $plan_map
   * @return  int|bool  Plan id or false
   */
  static function insert( $plan_map ) {
    return Activities_Item::insert( 'plan', $plan_map );
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
   * Get all the columns uses to store plans
   *
   * @param   string    $type Column types to get ('string' or 'int')
   * @return  array     Array of column names
   */
  static function get_columns( $type = 'none' ) {
    $strings = array( 'name', 'description' );
    $ints = array( 'slots' );

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
