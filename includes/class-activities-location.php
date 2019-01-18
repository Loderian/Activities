<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Location class
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Location {
  /**
   * Contains information about the location
   *
   * @var array
   */
  private $location;

  /**
   * Loads location info based on id
   *
   * @param int $id Location id
   */
  function __construct( $id ) {
    $this->location = self::load( $id );
  }

  /**
   * Retrieve property by name, accepted input:
   *    - location_id | id | ID
   *    - name
   *    - address
   *    - description
   *    - postcode
   *    - country, returns country code
   *    - city
   *
   *
   * @param   string  $name Proprety to get
   * @return  mixed   Data found for $name key, '' if not data was found
   */
  function __get( $name ) {
    if ( !is_array( $this->location ) ) {
      return '';
    }

    switch ($name) {
      case 'location_id':
      case 'name':
      case 'address':
      case 'description':
      case 'postcode':
      case 'country':
      case 'city':
        return $this->location[$name];
        break;

      case 'id':
      case 'ID':
        return $this->location['location_id'];
        break;

      default:
        return '';
        break;
    }
  }

  /**
   * Checks if an location exists
   *
   * @param   int|string  $act_id Location identifier
   * @param   string      $check_by Column to compare ('id', 'name')
   * @return  bool        True if the location exists, false otherwise
   */
  static function exists( $loc_id, $check_by = 'id' ) {
    return Activities_Item::exists( $loc_id, 'location', $check_by );
  }

  /**
   * Inserts location data into the database
   *
   * @param   array     $loc_map Location info
   * @return  int|bool  False if it could not be inserted, 1 otherwise
   */
  static function insert( $loc_map ) {
    return Activities_Item::insert( 'location', $loc_map );
  }

  /**
   * Updates location data, requires location_id
   *
   * @param   array     $loc_map Location info
   * @return  bool  False if it could not be updated, True otherwise
   */
  static function update( $loc_map ) {
    return Activities_Item::update( 'location', $loc_map );
  }

  /**
   * Reads location data from the database
   *
   * @param   int         $location_id Location id
   * @return  array|null  Associative array of location info, or null if error
   */
  static function load( $location_id ) {
    return Activities_Item::load( 'location', $location_id );
  }

  /**
   * Reads location data from the database
   *
   * @param   string                    $name Location name
   * @return  Activities_Activity|null  Location object, or null if error
   */
  static function load_by_name( $name ) {
    global $wpdb;

    $location_table = Activities::get_table_name( 'location' );
    $location_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT location_id
      FROM $location_table
      WHERE name = %s
      ",
      $name
    ));

    if ( $location_id === null ) {
      return false;
    }

    return new Activities_Location( $location_id );
  }

  /**
   * Deletes a location
   *
   * @param   int   $location_id Location id
   * @return  bool  True if the location was deleted, false otherwise
   */
  static function delete( $location_id ) {
    $del = Activities_Item::update( 'location', $location_id );

    if ( $del ) {
      do_action( 'activities_delete_location', $location_id );
      return $del;
    }
    else {
      return false;
    }
  }

  /**
   * Get all the columns uses to store locations
   *
   * @param   string    $type Column types to get ('string' or 'int')
   * @return  array     Array of column names
   */
  static function get_columns( $type = 'none' ) {
    $strings = array( 'name', 'address', 'description', 'city', 'postcode', 'country' );
    $ints = array();

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
