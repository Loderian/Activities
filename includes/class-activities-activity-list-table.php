<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Class for getting table display for activities
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Activity_List_Table extends Activities_List_Table {
  /**
   * True for activity archive table
   *
   * @var bool
   */
  protected $archive;

  function __construct( $archive = false ) {
    parent::__construct();

    $this->archive = $archive;

    if ( !$this->archive ) {
      $this->type = 'activity';
    }
    else {
      $this->type = 'activity_archive';
    }
  }

  /**
   * Get acitvity table name
   *
   * @return string Table name
   */
  protected function get_table_name() {
    return Activities::get_table_name( 'activity' );
  }

  /**
   * Get select sql for query
   *
   * @return string Select
   */
  protected function build_sql_select() {
    $sql_select = array( 'i.activity_id' );
    foreach (array_keys( $this->get_columns() ) as $key) {
      switch ($key) {
        case 'responsible':
          $sql_select[] = 'u.display_name AS responsible';
          $sql_select[] = 'first.meta_value AS first_name';
          $sql_select[] = 'last.meta_value AS last_name';
          break;

        case 'location':
          $sql_select[] = 'l.name AS location';
          break;

        case 'cb':
        case 'categories':
          break;

        default:
          $sql_select[] = 'i.'.$key;
          break;
      }
    }

    return implode( ', ', $sql_select );
  }

  /**
   * Builds joins for db query
   *
   * @return string
   */
  protected function build_sql_joins() {
    global $wpdb;

    $sql_joins = "LEFT JOIN $wpdb->users u ON i.responsible_id = u.ID ";
    $sql_joins .= "LEFT JOIN $wpdb->usermeta first ON i.responsible_id = first.user_id AND first.meta_key = 'first_name' ";
    $sql_joins .= "LEFT JOIN $wpdb->usermeta last ON i.responsible_id = last.user_id AND last.meta_key = 'last_name' ";
    $location_table = Activities::get_table_name( 'location' );
    $sql_joins .= "LEFT JOIN $location_table l ON i.location_id = l.location_id ";

    return $sql_joins;
  }

  /**
   * Build sql joins for count query
   *
   * @param   array   $filters Current filters applied to the sql query
   * @return  string
   */
  protected function build_count_sql_joins( $filters ) {
    global $wpdb;
    
    $count_sql_joins = '';
    if ( isset( $filters['responsible'] ) && $filters['responsible'] != '' ) {
      $count_sql_joins .= "LEFT JOIN $wpdb->users u ON i.responsible_id = u.ID ";
      $count_sql_joins .= "LEFT JOIN $wpdb->usermeta first ON i.responsible_id = first.user_id AND first.meta_key = 'first_name' ";
      $count_sql_joins .= "LEFT JOIN $wpdb->usermeta last ON i.responsible_id = last.user_id AND last.meta_key = 'last_name' ";
    }
    if ( isset( $filters['location'] ) && $filters['location'] != '' ) {
      $location_table = Activities::get_table_name( 'location' );
      $count_sql_joins .= "LEFT JOIN $location_table l ON i.location_id = l.location_id ";
    }

    return $count_sql_joins;
  }

  /**
   * Build sql where clause for activities
   *
   * @param   array   $filters Filters for the current page
   * @return  string  Where clause
   */
  protected function build_where( $filters ) {
    $filters_str = array();
    if ( $this->archive ) {
      $filters_str[] = 'archive = 1';
    }
    else {
      $filters_str[] = 'archive = 0';
    }
    foreach ($filters as $key => $value) {
      if ( $value != '' ) {
        switch ($key) {
          case 'responsible':
            $filters_str[] = sprintf ( "(u.display_name LIKE '%%%s%%' OR CONCAT(first.meta_value, ' ', last.meta_value) LIKE '%%%s%%')", $value, $value );
            break;

          case 'location':
            $filters_str[] = sprintf ( "%s LIKE '%%%s%%'", 'l.name', $value );
            break;

          case 'category':
            $cat_acts = Activities_Category::get_activities_with_category( $value );
            if ( count( $cat_acts ) === 0 ) {
              $filters_str[] = sprintf( 'i.activity_id = 0' );
            }
            else {
              $filters_str[] = sprintf( 'i.activity_id IN (%s)', implode( ',', $cat_acts ) );
            }
            break;

          default:
            $filters_str[] = sprintf ( "%s LIKE '%%%s%%'", ('i.' . $key), $value );
            break;
        }
      }
    }
    if ( count( $filters_str ) > 0 ) {
      $sql_where = 'WHERE ' . implode( ' AND ', $filters_str );

      if ( Activities_Responsible::current_user_restricted_view() ) {
        $sql_where .= sprintf ( ' AND i.responsible_id = %d', wp_get_current_user()->ID );
      }
    }
    else if ( Activities_Responsible::current_user_restricted_view() ) {
      $sql_where = sprintf ( 'WHERE i.responsible_id = %d', wp_get_current_user()->ID );
    }

    return $sql_where;
  }

  /**
   * Gets bulk actions for activity list table
   *
   * @return array List of bulk actions
   */
  protected function get_bulk_actions() {
    $actions = array();
    if ( !$this->archive ) {
      if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) {
        $actions = array(
          'change_location' => esc_html__( 'Change Location', 'activities' ),
          'change_responsible' => esc_html__( 'Change Responsible', 'activities' ),
          'change_members' => esc_html__( 'Change Participants', 'activities' )
        );
        if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
          $actions['archive'] = esc_html_x( 'Archive', 'To archive', 'activities' );
        }
      }
    }
    else {
      $actions = array(
        'activate' => esc_html__( 'Activate', 'activities' ),
        'delete_a' => esc_html__( 'Delete', 'activities' )
      );
    }

    return $actions;
  }

  /**
   * Gets columns for the activities table
   *
   * @return array
   * 'column_key' => array(
   *    'hidden'    => bool
   *    'sortable'  => bool
   *    'display'   => string
   * )
   */
  protected function get_columns() {
    $options = Activities_Options::get_user_option( ( $this->archive ? 'activity_archive' : 'activity' ), 'show_columns' );

    $columns = array(
      'cb' => array(
        'hidden' => false,
        'sortable' => false,
      ),
      'name' => array(
        'hidden' => false,
        'sortable' => true,
      ),
      'short_desc' => array(
        'hidden' => !$options['short_desc'],
        'sortable' => false,
      ),
      'long_desc' => array(
        'hidden' => !$options['long_desc'],
        'sortable' => false,
      ),
      'start' => array(
        'hidden' => !$options['start'],
        'sortable' => true,
      ),
      'end' => array(
        'hidden' => !$options['end'],
        'sortable' => true,
      ),
      'responsible' => array(
        'hidden' => !$options['responsible'],
        'sortable' => true,
      ),
      'location' => array(
        'hidden' => !$options['location'],
        'sortable' => true,
      ),
      'categories' => array(
        'hidden' => !$options['categories'],
        'sortable' => false
      )
    );

    return $columns;
  }
}
