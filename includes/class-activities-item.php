<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Generic item class
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Item {
  /**
   * Checks if the item exists
   *
   * @param   int|string  $val Value to check
   * @param   string      $type Type of item
   * @param   string      $check_by Column to compare ('id', 'name')
   * @return  bool        True if the item exists, false otherwise
   */
  static function exists( $val, $type, $check_by = 'id' ) {
    global $wpdb;

    $table = Activities::get_table_name( $type );
    $where = self::build_where( $type, $check_by );

    $exists = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*)
      FROM $table
      WHERE $where
      ",
      $val
    ));

    return $exists >= 1;
  }

  /**
   * Inserts item data into the database
   *
   * @param   string    $type Type of item
   * @param   array     $map Item data
   * @return  int|bool  False if it could not be inserted, new item id otherwise
   */
  static function insert( $type, $map ) {
    global $wpdb;

    if ( $map['name'] === '' || self::exists( $map['name'], $type, 'name' ) ) {
      return false;
    }

    $values = array();
    $formats = array();

    foreach (self::get_columns( $type, 'strings' ) as $str) {
      if ( array_key_exists( $str, $map ) ) {
        $values[$str] = $map[$str];
        $formats[] = '%s';
      }
    }

    foreach (self::get_columns( $type, 'ints' ) as $str) {
      if ( array_key_exists( $str, $map ) ) {
        $values[$str] = $map[$str];
        $formats[] = '%d';
      }
    }

    $item = $wpdb->insert( Activities::get_table_name( $type ), $values, $formats );

    if ( $item ) {
      return $wpdb->insert_id;
    }

    return $item;
  }

  /**
   * Updates item data, requires item_id
   *
   * @param   string    $type Type of item
   * @param   array     $map Item data
   * @return  int|bool  False if it could not be updated, 1 otherwise
   */
  static function update( $type, $map ) {
    if ( !isset( $map[$type . '_id'] ) || !self::exists( $map[$type . '_id'], $type ) ) {
      return false;
    }

    global $wpdb;

    $values = array();
    $formats = array();

    foreach (self::get_columns( $type, 'strings' ) as $str) {
      if ( array_key_exists( $str, $map ) ) {
        $values[$str] = $map[$str];
        $formats[] = '%s';
      }
    }

    foreach (self::get_columns( $type, 'ints' ) as $str) {
      if ( array_key_exists( $str, $map ) ) {
        $values[$str] = $map[$str];
        $formats[] = '%d';
      }
    }

    $update = $wpdb->update(
      Activities::get_table_name( $type ),
      $values,
      array( ($type . '_id') => $map[$type . '_id'] ),
      $formats,
      array( '%d' )
    );

    return $update;
  }

  /**
   * Reads item data from the database
   *
   * @param   string      $type Item type
   * @param   int         $item_id Item id
   * @return  array|null  Associative array of item info, or null if error
   */
  static function load( $type, $item_id ) {
    global $wpdb;

    $table = Activities::get_table_name( $type );
    $item = $wpdb->get_row( $wpdb->prepare(
        "SELECT *
         FROM $table
         WHERE {$type}_id = %d
        ",
        $item_id
      ),
      ARRAY_A
    );

    return $item;
  }

  /**
   * Deletes item
   *
   * @param   string    $type Type of item
   * @param   int       $id Item id
   * @return  bool      True if the item was deleted, false otherwise
   */
  static function delete( $type, $id ) {
    global $wpdb;

    $del = $wpdb->delete(
      Activities::get_table_name( $type ),
      array( $type . '_id' => $id ),
      array( '%d' )
    );

    return $del;
  }

  /**
   * Get columns used to store items
   *
   * @param   string    $item_type Item type
   * @param   string    $type Column types to get ('string' or 'int')
   * @return  array     Array of column names
   */
  static function get_columns( $item_type, $type = 'none' ) {
    switch ($item_type) {
      case 'activity':
        return Activities_Activity::get_columns( $type );
        break;

      case 'location':
        return Activities_Location::get_columns( $type );
        break;

      case 'plan':
        return Activities_Plan::get_columns( $type );
        break;
    }
  }

  /**
   * Builds where cluase for sql queries
   *
   * @param   string  $check_by Column find activity by
   * @return  string  String to use in where clause in sql
   */
  static function build_where( $type, $check_by = 'id' ) {
    switch ($check_by) {
      case 'name':
        $where = 'name = %s';
        break;

      case 'id':
      default:
        $where = $type . '_id = %d';
        break;
    }

    return $where;
  }
}
