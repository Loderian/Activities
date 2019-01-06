<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Class for getting table display for admin areas
 *
 * //TODO Improve code structure
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_List_Table {
  /**
   * Columns to display
   *
   * @var array
   */
  protected $columns;

  /**
   * Type of data to display (activity, location, activity_archive)
   *
   * @var string
   */
  protected $type;

  /**
   * Current ordering of a columm
   *
   * @var string
   */
  protected $order = 'asc';

  /**
   * Reverse ordering of a columm
   *
   * @var string
   */
  protected $order_switch = 'desc';

  /**
   * Current column whitch is ordered
   *
   * @var string
   */
  protected $orderby = 'name';

  /**
   * Current url
   *
   * @var string
   */
  protected $current_url;

  /**
   * Pagination object
   *
   * @var Activities_Pagination
   */
  protected $pagination;

  /**
   * Array containing data to be printed on the page
   *
   * @var array
   */
  protected $items;

  /**
   * Initializes data required to begin creating the page
   *
   * @param array $columns Information about the columns to display
   * 'column_key' => array(
   *    'hidden'    => bool
   *    'sortable'  => bool
   *    'display'   => string
   * )
   * @param string $type Type to display (activity, location, activity_archive)
   */
  public function __construct( $columns, $type ) {
    $this->columns = $columns;
    $this->type = $type;
  }

  /**
   * Builds the page
   *
   * @return string The page to display
   */
  public function display() {
  	global $wpdb;

  	$output = '';

    $table_name = '';

    switch ($this->type) {
      case 'activity':
      case 'activity_archive':
        $table_name = Activities::get_table_name( 'activity' );
        break;

      case 'location':
        $table_name = Activities::get_table_name( 'location' );
        break;
    }


  	$current_url = ( isset($_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  	$current_url = remove_query_arg( 'action', $current_url );
  	$current_url = remove_query_arg( 'item_id', $current_url );
    $current_url = remove_query_arg( ACTIVITIES_ARCHIVE_NONCE_GET, $current_url );

  	$output .= Activities_Admin::get_messages();

  	$sql_filter = '';
  	$items_per_page = Activities_Options::get_user_option( $this->type, 'items_per_page' );

  	$filters = array();
  	if ( isset( $_POST['apply_filters'] ) && isset( $_POST['filters'] ) && is_array( $_POST['filters'] ) ) {
      $default_filters = Activities_Options::get_default_user_option( $this->type, 'filters' );
  		foreach ($_POST['filters'] as $key => $value) {
        $key = sanitize_key( $key );
        if ( array_key_exists( $key, $default_filters ) ) {
    			$filters[$key] = sanitize_text_field( $value );
        }
  		}
  		Activities_Options::update_user_option( $this->type, 'filters', $filters );
  	}
  	else if ( isset( $_POST['clear_filters'] ) ) {
  		Activities_Options::delete_user_option( $this->type, 'filters' );
  	}
  	if ( !isset( $_POST['apply_filters'] ) ) {
  		$filters = Activities_Options::get_user_option( $this->type, 'filters' );
  	}
    $filters_str = array();
    if ( $this->type == 'activity' ) {
      $filters_str[] = 'archive = 0';
    }
    if ( $this->type == 'activity_archive' ) {
      $filters_str[] = 'archive = 1';
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
  		$sql_filter = 'WHERE ' . implode( ' AND ', $filters_str );

  		if ( $this->type == 'activity' && Activities_Responsible::current_user_restricted_view() ) {
  			$sql_filter .= sprintf ( ' AND i.responsible_id = %d', wp_get_current_user()->ID );
  		}
  	}
  	else if ( $this->type == 'activity' && Activities_Responsible::current_user_restricted_view() ) {
  		$sql_filter = sprintf ( 'WHERE i.responsible_id = %d', wp_get_current_user()->ID );
  	}

  	$sql_joins = '';
    $count_sql_joins = '';
  	$sql_select = array();
  	if ( $this->type == 'location' ) {
  		$sql_select[] = 'i.location_id';
  	}
  	else {
  		$sql_select[] = 'i.activity_id';
  	}
  	foreach ($this->columns as $key => $info) {
  		if ( $key == 'responsible' ) {
  			$sql_select[] = 'u.display_name AS responsible';
  			$sql_select[] = 'first.meta_value AS first_name';
  			$sql_select[] = 'last.meta_value AS last_name';
  			$sql_joins .= "LEFT JOIN $wpdb->users u ON i.responsible_id = u.ID ";
  			$sql_joins .= "LEFT JOIN $wpdb->usermeta first ON i.responsible_id = first.user_id AND first.meta_key = 'first_name' ";
  			$sql_joins .= "LEFT JOIN $wpdb->usermeta last ON i.responsible_id = last.user_id AND last.meta_key = 'last_name' ";
        if ( isset( $filters['responsible'] ) && $filters['responsible'] != '' ) {
          $count_sql_joins .= "LEFT JOIN $wpdb->users u ON i.responsible_id = u.ID ";
          $count_sql_joins .= "LEFT JOIN $wpdb->usermeta first ON i.responsible_id = first.user_id AND first.meta_key = 'first_name' ";
          $count_sql_joins .= "LEFT JOIN $wpdb->usermeta last ON i.responsible_id = last.user_id AND last.meta_key = 'last_name' ";
        }
  		}
  		else if ( $key == 'location' ) {
  			$sql_select[] = 'l.name AS location';
  			$location_table = Activities::get_table_name( 'location' );
  			$sql_joins .= "LEFT JOIN $location_table l ON i.location_id = l.location_id ";
        if ( isset( $filters['location'] ) && $filters['location'] != '' ) {
          $count_sql_joins .= "LEFT JOIN $location_table l ON i.location_id = l.location_id ";
        }
  		}
  		else if ( !in_array( $key, array( 'cb', 'categories') ) ) {
  			$sql_select[] = 'i.'.$key;
  		}
  	}

  	$sql_select = implode( ', ', $sql_select );

    $total_items = $wpdb->get_var(
      "SELECT COUNT(*)
      FROM $table_name i
      $count_sql_joins
      $sql_filter"
    );

  	$this->pagination = new Activities_Pagination( $total_items, $items_per_page );

  	if ( $this->pagination->check_if_paged() ) {
  		$current_url = remove_query_arg( 'paged', $current_url );
  	}

    $this->current_url = $current_url;
  	//Url ready

  	$output .= $this->field_filters( $filters );

  	switch ($this->type) {
  		case 'activity':
  			$actions = array();
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
  			break;

  		case 'location':
  			$actions = array(
  				'address' => esc_html__( 'Change Address', 'activities' ),
  				'delete_l' => esc_html__( 'Delete', 'activities' )
  			);
  			break;

  		case 'activity_archive':
  			$actions = array(
  				'activate' => esc_html__( 'Activate', 'activities' ),
  				'delete_a' => esc_html__( 'Delete', 'activities' )
  			);
  			break;
  	}

    $output .= '<form action="' . $current_url . '" method="post">';

    $output .= '<div class="tablenav top">';
    $output .= $this->bulk_actions( $actions );

    $output .= $this->pagination->get_pagination_control( $current_url, $this->type );
    $output .= '</div>';

  	if ( isset( $_GET['order'] ) ) {
  		$this->order = sanitize_key( $_GET['order'] );
  	}
  	if ( isset( $_GET['orderby'] ) ) {
  		$this->orderby = sanitize_key( $_GET['orderby'] );
  	}
  	$this->order_switch = $this->order == 'asc' ? 'desc' : 'asc';

    $orderby = sanitize_sql_orderby( $this->orderby . ' ' . strtoupper( $this->order ) );
    $order_prefix = '';

    if ( $orderby ) {
      if ($this->orderby != 'responsible' && $this->orderby != 'location') {
        $order_prefix = 'i.';
      }

      $sql_order = sprintf( 'ORDER BY %s%s', $order_prefix, $orderby );
    }
    else {
      $this->order = 'name';
      $this->orderby = 'asc';

      $sql_order = 'ORDER BY i.name ASC';
    }

    $sql_limit_offset = $this->pagination->get_sql();

    $this->items = $wpdb->get_results(
      "SELECT $sql_select
       FROM $table_name i
       $sql_joins
       $sql_filter
       $sql_order
       $sql_limit_offset
      ",
      ARRAY_A
    );

    $output .= $this->get_table();

    $output .= '</form>';

  	$output .= '<div class="tablenav bottom">';
  	$output .= $this->pagination->get_pagination_control( $current_url, $this->type );
  	$output .= '</div>';

  	return $output;
  }

  /**
   * Builds the table
   *
   * @return string Table
   */
  protected function get_table() {
    $output = '';
    $output .= '<table class="wp-list-table widefat fixed striped activities">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= $this->get_column_headers( $this->current_url );
    $output .= '</tr>';
    $output .= '</thead>';

    $output .=	'<tbody id="the-list"';
    $output .= " data-wp-lists='list:$this->type'";
    $output .= '>';
    $output .= $this->get_rows_or_placeholder();
    $output .= '</tbody>';

    $output .= '<tfoot>';
    $output .= '<tr>';
    $output .= $this->get_column_headers( $this->current_url, false );
    $output .= '</tr>';
    $output .= '</tfoot>';

    $output .= '</table>';

    return $output;
  }

  /**
   * Builds header/footer for the table
   *
   * @param   bool    $with_id True to build top header, false for footer
   * @return  string  Header/Footer
   */
  protected function get_column_headers( $with_id = true ) {
  	$output = '';
  	foreach ( $this->columns as $key => $info ) {
  		$class = array( 'manage-column', "column-$key" );
  		$column_display_name = Activities_Admin_Utility::get_column_display( $key );

  		if ( $info['hidden'] ) {
  			$class[] = 'hidden';
  		}

  		if ( $key === 'cb' ) {
  			$class[] = 'check-column';
  			$column_display_name = '<label class="screen-reader-text" for="activities-select-all">' . esc_html__( 'Select All', 'activities' ) . '</label>';
  			$column_display_name .= '<input id="activities-select-all" type="checkbox" />';
  		}
  		if ( $key === 'name' ) {
  			$class[] = 'column-primary';
  		}

  		if ( $info['sortable'] ) {
  			if ( $this->orderby === $key ) {
  				$class[] = 'sorted';
  				$class[] = $this->order_switch;
  			} else {
  				$class[] = 'sortable';
  				$class[] = 'asc';
  			}

  			$column_display_name = '<a href="' . esc_url( add_query_arg( array( 'orderby' => $key, 'order' => ( $this->orderby == $key ? $this->order_switch : 'asc' ) ), $this->current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
  		}

  		$tag = ( $key === 'cb' ) ? 'td' : 'th';
  		$scope = ( $tag === 'th' ) ? 'scope="col"' : '';
  		$id = $with_id ? "id='$key'" : '';

  		if ( !empty( $class ) ) {
  			$class = "class='" . implode( ' ', $class ) . "'";
  		}

  		$output .= "<$tag $scope $id $class>$column_display_name</$tag>";
  	}

    return $output;
  }

  /**
   * Gets the rows or placeholder for the table
   *
   * @return  string  Rows or placeholder if 0 items
   */
  function get_rows_or_placeholder() {
    $output = '';
    if ( $this->pagination->total_items > 0 ) {
      foreach ( $this->items as $item ) {
        $output .= $this->single_row( $item );
      }
    } else {
      $col_count = 0;
      foreach ($this->columns as $info) {
        if ( !$info['hidden'] ) {
          $col_count++;
        }
      }
      $output .= '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $col_count ) . '">';
      if ( $this->type != 'location' ) {
        $output .= 'No activities found.';
      }
      else {
        $output .= 'No locations found.';
      }
      $output .= '</td></tr>';
    }

    return $output;
  }

  /**
   * Builds a single row to display (planned to be updated)
   *
   * @param   array   $item Data for a single row
   * @return  string  The row
   */
  function single_row( $item ) {
    global $wpdb;

    $countries = array();
    if ( $this->type == 'location' ) {
      $countries = Activities_Utility::get_countries();
    }
    $output = '<tr>';

		foreach ( $this->columns as $key => $info ) {
			$classes = "$key column-$key";
			if ( $key === 'name' ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( $info['hidden'] ) {
				$classes .= ' hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . wp_strip_all_tags( Activities_Admin_Utility::get_column_display( $key ) ) . '"';

			$attributes = "class='$classes' $data";

      $id = $this->type == 'location' ? 'location_id' : 'activity_id';

      if ( $key === 'cb' ) {
        $output .= '<th scope="row" class="check-column">';
        $output .= '<input type="checkbox" name="selected_activities[]" value="' . esc_attr( $item[$id] ) . '" />';
        $output .= '</th>';
      }
      else {
        $output .= "<td $attributes>";

        if ( $key == 'name' ) {
  				$count_display = '';
  				if ( $this->type != 'location' ) {
            $user_act_table = Activities::get_table_name( 'user_activity' );
  					if ( $this->type == 'activity' ) {
              $archive_url = wp_nonce_url( $this->current_url, 'activities_archive_activity', ACTIVITIES_ARCHIVE_NONCE_GET );
  					}
  					else {
              $activate_url = wp_nonce_url( $this->current_url, 'activities_activate_activity', ACTIVITIES_ARCHIVE_NONCE_GET );
  					}

  					$count = $wpdb->get_var( $wpdb->prepare(
  						"SELECT COUNT(*)
  						FROM $user_act_table
  						WHERE activity_id = %d
  						",
  						$item[$id]
  					));
  					$count_display = '(' . $count . ')';
            $export_url = remove_query_arg( array( 'paged', 'order', 'orderby', 'action', 'view' ) , $this->current_url );
            $export_url = add_query_arg( array( 'page' => 'activities-admin-export', 'item_id' => $item[$id] ), $export_url );
  				}
  				if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) {
  					$name_action = 'edit';
  				}
  				else {
  					$name_action = 'view';
  				}

          $output .= '<div ' . ( $this->type != 'location' ? 'class="activities-name-wrap"' : '' ) . '>';
  				$output .= '<a href="' . esc_url( $this->current_url . '&action=' . $name_action ) . '&item_id=' . $item[$id] . '">' . stripslashes( wp_filter_nohtml_kses( $item['name'] ) ) . '</a> ';
  				$output .= $count_display;

  				$output .= '<div class="row-actions">';

  				switch ($this->type) {
  					case 'activity':

              $output .= '<a href="' . esc_url( $this->current_url . '&action=view&item_id=' . esc_attr( $item[$id] ) ) . '">' . esc_html__( 'View', 'activities' ) . '</a>';

  						if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) {
  							$output .= ' | <a href="' . esc_url( $this->current_url . '&action=edit&item_id=' . esc_attr( $item[$id] ) ) . '">' . esc_html__( 'Edit', 'activities' ) . '</a>';
  						}

              $output .= ' | <a href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export', 'activities' ) . '</a>';
  						break;

  					case 'location':
  						$output .= '<a href="' . esc_url( $this->current_url . '&action=edit&item_id=' . esc_attr( $item[$id] ) ) . '">' . esc_html__( 'Edit', 'activities' ) . '</a> | ';
  						$output .= '<a href="' . esc_url( $this->current_url . '&action=delete&item_id=' . esc_attr( $item[$id] ) ) . '" class="activities-delete">' . esc_html__( 'Delete', 'activities' ) . '</a>';
  						break;

  					case 'activity_archive':
  						$output .= '<a href="' . esc_url( $this->current_url . '&action=view&item_id=' . esc_attr( $item[$id] ) ) . '">' . esc_html__( 'View', 'activities' ) . '</a> | ' ;
              $output .= '<a href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export', 'activities' ) . '</a> | ';
  						$output .= '<a href="' . esc_url( $activate_url . '&action=activate&item_id=' . esc_attr( $item[$id] ) ) . '" class="activities-activate">' . esc_html__( 'Activate', 'activities' ) . '</a> | ';
  						$output .= '<a href="' . esc_url( $this->current_url . '&action=delete&item_id=' . esc_attr( $item[$id] ) ) . '" class="activities-delete">' . esc_html__( 'Delete', 'activities' ) . '</a>';
  				}
          $output .= '</div>';
          $output .= '</div>';

          if ( $this->type != 'location' ) {
            $output .= '<div class="activities-nice-link"><a href="' . esc_url( $this->current_url . '&action=view&item_id=' . esc_attr( $item[$id] ) ) . '"><span class="dashicons dashicons-visibility"></span></a></div>';
          }

          $output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'activities' ) . '</span></button>';
  			}
  			else if ( $key == 'start' || $key == 'end') {
          $output .= stripslashes( wp_filter_nohtml_kses( Activities_Utility::format_date( $item[$key] ) ) );;
  			}
  			else if ( $key == 'responsible' ) {
  				if ( $item['responsible'] === null ) {
  					$display = '&mdash;';
  				}
  				else {
  					$display = $item['first_name'] . ' ' . $item['last_name'];
  					if ( $display == ' ') {
  						$display = $item['responsible'];
  					}
  				}
          $output .= stripslashes( wp_filter_nohtml_kses( $display ) );
        }
  			else if ( $key == 'location' ) {
          $output .= $item['location'] === null ? '&mdash;' : stripslashes( wp_filter_nohtml_kses( $item['location'] ) );
  			}
        else if ( $key == 'country' ) {
          if ( isset( $countries[$item[$key]] ) ) {
            $output .= stripslashes( wp_filter_nohtml_kses( $countries[$item[$key]] ) );
          }
        }
        else if ( $key == 'categories' ) {
          $output .= stripslashes( wp_filter_nohtml_kses( implode( ', ', Activities_Category::get_act_categories( $item[$id], true ) ) ) );
        }
  			else {
          $output .= stripslashes( wp_filter_nohtml_kses( $item[$key] ) );
  			}
  		}

      $output .= "</td>";
    }

    $output .= '</tr>';

    return $output;
  }

  /**
   * Builds the filter part of the page
   *
   * @param   array   $filters Current filter values
   * @return  string  Filter box for display
   */
  function field_filters( $filters ) {
  	$output = '<div id="activities-filter-wrap" class="acts-box-wrap acts-box-padding">';
  	$output .= '<b>' . esc_html__( 'Filters', 'activities' ) . '</b>';
  	$output .= '<form action="' . esc_url( $this->current_url ) . '" method="post" class="acts-form">';

  	foreach ($filters as $key => $value) {
      $output .= '<div>';
      switch ($key) {
        case 'category':
          $output .= '<p>' . esc_html__( ucfirst( $key ), 'activities' ) . '</p>';
          $output .= acts_build_select(
            Activities_Category::get_categories( 'id=>name' ),
            array(
              'name' => 'filters[category]',
              'selected' => $value,
              'blank' => __( 'No Category Filter', 'activities' ),
              'blank_val' => ''
            )
          );
          break;

        default:
          $output .= '<p>' . esc_html__( ucfirst( $key ), 'activities' ) . '</p>';
          $output .= '<input type="text" placeholder="' . sprintf( esc_html__( 'Filter %s', 'activities' ),  esc_html__( ucfirst( $key ), 'activities' ) ) . '" name="filters[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" />';
          break;
      }

  		$output .= '</div>';
  	}

    $output .= '<div class="acts-filter-buttons">';
    $output .= get_submit_button( esc_html__( 'Apply', 'activities' ), 'button', 'apply_filters', false ) . ' ';
    $output .= get_submit_button( esc_html__( 'Clear', 'activities' ), 'button', 'clear_filters', false );
    $output .= '</div>';
  	$output .= '</form></div>';

  	return $output;
  }

  /**
   * Builds bulk action selecter
   *
   * @param   array   $actions Possible bulk actions for the current page
   * @return  string  Bulk actions selecter
   */
  function bulk_actions( $actions ) {
  	$output = '<div id="activities-bulk-wrap">';
  	$output .= '<select name="bulk">';
  	$output .= '<option selected="selected" value="0">' . esc_html__( 'Bulk Actions', 'activities' ) . '</option>';
  	foreach ($actions as $value => $display) {
  		$output .= '<option value="' . esc_attr( $value ) . '">' . stripslashes( wp_filter_nohtml_kses( $display ) ) . '</option>';
  	}
  	$output .= '</select> ';
  	$output .= '<input type="submit" name="apply_bulk" class="button" value="' . esc_html__( 'Apply', 'activities' ) . '" />';
  	$output .= '</div>';

  	return $output;
  }
}
