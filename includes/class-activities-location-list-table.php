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

  /**
   * Builds a singe cell on the table
   *
   * @param   array   $item Data for the cell
   * @param   string  $key Cell key
   * @return  string  The cell
   */
  protected function build_table_cell( $item, $key ) {
    if ( $key == 'name') {
      return $this->build_table_name_cell( $item );
    }
    else if ( $key == 'country' ) {
      $countries = Activities_Utility::get_countries();
      if ( isset( $countries[$item[$key]] ) ) {
        return stripslashes( wp_filter_nohtml_kses( $countries[$item[$key]] ) );
      }
    }
    else {
      return parent::build_table_cell( $item, $key );
    }
  }

  /**
   * Build row actions for name cell
   *
   * @param   int     $id Id of the item
   * @return  string  Row actions
   */
  protected function build_row_actions( $id ) {
    $output = '<a href="' . esc_url( $this->current_url . '&action=edit&item_id=' . esc_attr( $id ) ) . '">' . esc_html__( 'Edit', 'activities' ) . '</a> | ';
    $output .= '<a href="' . esc_url( $this->current_url . '&action=delete&item_id=' . esc_attr( $id ) ) . '" class="activities-delete">' . esc_html__( 'Delete', 'activities' ) . '</a>';

    return $output;
  }

  /**
   * Gets the item id
   *
   * @param   array   $item Item data
   * @return  int     Id
   */
  protected function get_item_id( $item ) {
    return $item['location_id'];
  }
}
