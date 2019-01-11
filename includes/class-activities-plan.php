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
   * Insert a plan
   *
   * @param   array     $plan_map
   * @return  int|bool  Plan id or false
   */
  static function insert( $plan_map ) {
    global $wpdb;

    $values = array();
    $formats = array();

    foreach (self::get_columns('string') as $column) {
      if ( array_key_exists( $column, $plan_map ) ) {
        $values[] = $plan_map[$column];
        $formats[] = '%s';
      }
    }
    foreach (self::get_columns('int') as $column) {
      if ( array_key_exists( $column, $plan_map ) ) {
        $values[] = $plan_map[$column];
        $formats[] = '%d';
      }
    }

    $plan = $wpdb->insert(
      Activities::get_table_name( 'plan' ),
      $values,
      $formats
    );

    if ( $plan ) {
      return $wpdb->insert_id;
    }

    return $plan;
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
