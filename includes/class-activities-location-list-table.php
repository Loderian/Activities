<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Class for getting table display for locations
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Location_List_Table extends Activities_List_Table {

  function __construct( ) {
    parent::__construct();

    $this->type = 'location';
  }

  /**
   * Get location table name
   *
   * @return string
   */
  protected function get_table_name() {
    return Activities::get_table_name( 'location' );
  }

  /**
   * Get sql select
   *
   * @return string Select query
   */
  protected function build_sql_select() {
    $sql_select = array( 'i.location_id' );
    foreach (array_keys( $this->get_columns() ) as $key) {
      switch ($key) {
        case 'cb':
          break;

        default:
          $sql_select[] = 'i.'.$key;
          break;
      }
    }

    return implode( ', ', $sql_select );
  }

  /**
   * Build sql where clause for activities
   *
   * @param   array   $filters Filters for the current page
   * @return  string  Where clause
   */
  protected function build_where( $filters ) {
    $filters_str = array();
    foreach ($filters as $key => $value) {
      if ( $value != '' ) {
        $filters_str[] = sprintf ( "%s LIKE '%%%s%%'", $key, $value );
      }
    }

    $sql_where = '';
    if ( count( $filters_str ) > 0 ) {
      $sql_where = 'WHERE ' . implode( ' AND ', $filters_str );
    }

    return $sql_where;
  }

  /**
   * Gets bulk actions for activity list table
   *
   * @return array
   */
  protected function get_bulk_actions() {
    return $actions = array(
      'address' => esc_html__( 'Change Address', 'activities' ),
      'delete_l' => esc_html__( 'Delete', 'activities' )
    );
  }

  /**
   * Gets columns for the table
   *
   * @return array
   * 'column_key' => array(
   *    'hidden'    => bool
   *    'sortable'  => bool
   *    'display'   => string
   * )
   */
  protected function get_columns() {
    $options = Activities_Options::get_user_option( 'location', 'show_columns' );

    $columns = array(
      'cb' => array(
        'hidden' => false,
        'sortable' => false
      ),
      'name' => array(
        'hidden' => false,
        'sortable' => true
      ),
      'address' => array(
        'hidden' => !$options['address'],
        'sortable' => true
      ),
      'description' => array(
        'hidden' => !$options['description'],
        'sortable' => false
      ),
      'city' => array(
        'hidden' => !$options['city'],
        'sortable' => true
      ),
      'postcode' => array(
        'hidden' => !$options['postcode'],
        'sortable' => true
      ),
      'country' => array(
        'hidden' => !$options['country'],
        'sortable' => true
      )
    );

    return $columns;
  }
}
