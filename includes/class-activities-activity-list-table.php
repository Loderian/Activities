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
        } else {
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
     * Get additional sql selects
     *
     * @return array Additional selects for query
     */
    protected function get_additional_sql_select() {
        return array(
            'u.display_name AS responsible',
            'first.meta_value AS first_name',
            'last.meta_value AS last_name',
            'l.name AS location',
            'p.name AS plan'
        );
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
        $sql_joins      .= "LEFT JOIN $location_table l ON i.location_id = l.location_id ";

        $plan_table = Activities::get_table_name( 'plan' );
        $sql_joins  .= "LEFT JOIN $plan_table p ON i.plan_id = p.plan_id ";

        return $sql_joins;
    }

    /**
     * Build sql joins for count query
     *
     * @param array $filters Current filters applied to the sql query
     *
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
            $location_table  = Activities::get_table_name( 'location' );
            $count_sql_joins .= "LEFT JOIN $location_table l ON i.location_id = l.location_id ";
        }

        return $count_sql_joins;
    }

    /**
     * Build sql where clause for activities
     *
     * @param array $filters Filters for the current page
     *
     * @return  string  Where clause
     */
    protected function build_where( $filters ) {
        $sql_where = parent::build_where( $filters );
        if ( $sql_where === '' ) {
            $sql_where .= 'WHERE ';
        } else {
            $sql_where .= ' AND ';
        }
        $sql_where .= 'archive = ' . ( $this->archive ? 1 : 0 );

        if ( Activities_Responsible::current_user_restricted_view() ) {
            $sql_where .= sprintf( ' AND i.responsible_id = %d', wp_get_current_user()->ID );
        }

        return $sql_where;
    }

    /**
     * Get where builders
     *
     * @return array List of column $key => callback
     */
    protected function get_where_builders() {
        return array(
            'responsible' => function ( $value ) {
                return sprintf( "(u.display_name LIKE '%%%s%%' OR CONCAT(first.meta_value, ' ', last.meta_value) LIKE '%%%s%%')", $value, $value );
            },
            'location'    => function ( $value ) {
                return sprintf( "%s LIKE '%%%s%%'", 'l.name', $value );
            },
            'category'    => function ( $value ) {
                $cat_acts = Activities_Category::get_activities_with_category( $value );
                if ( count( $cat_acts ) === 0 ) {
                    return sprintf( 'i.activity_id = 0' );
                } else {
                    return sprintf( 'i.activity_id IN (%s)', implode( ',', $cat_acts ) );
                }
            }
        );
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
                    'change_location'    => esc_html__( 'Change Location', 'activities' ),
                    'change_responsible' => esc_html__( 'Change Responsible', 'activities' ),
                    'change_members'     => esc_html__( 'Change Participants', 'activities' )
                );
                if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
                    $actions['archive'] = esc_html_x( 'Archive', 'To archive', 'activities' );
                }

            }
        } else {
            if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
                $actions = array(
                    'activate' => esc_html__( 'Activate', 'activities' ),
                    'delete_a' => esc_html__( 'Delete', 'activities' )
                );
            }
        }

        $actions['export_user_data'] = esc_html__( 'Export', 'activities' );

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

        return array(
            'cb'          => array(
                'hidden'   => false,
                'sortable' => false,
            ),
            'name'        => array(
                'hidden'   => false,
                'sortable' => true,
            ),
            'short_desc'  => array(
                'hidden'   => !$options['short_desc'],
                'sortable' => false,
            ),
            'long_desc'   => array(
                'hidden'   => !$options['long_desc'],
                'sortable' => false,
            ),
            'start'       => array(
                'hidden'   => !$options['start'],
                'sortable' => true,
            ),
            'end'         => array(
                'hidden'   => !$options['end'],
                'sortable' => true,
            ),
            'responsible' => array(
                'hidden'   => !$options['responsible'],
                'sortable' => true,
            ),
            'location'    => array(
                'hidden'   => !$options['location'],
                'sortable' => true,
            ),
            'categories'  => array(
                'hidden'   => !$options['categories'],
                'sortable' => false
            ),
            'plan'        => array(
                'hidden'   => !$options['plan'],
                'sortable' => false
            )
        );
    }

    /**
     * Builds a singe cell on the table
     *
     * @param array $item Data for the cell
     * @param string $key Cell key
     *
     * @return  string  The cell
     */
    protected function build_table_cell( $item, $key ) {
        if ( $key == 'name' ) {
            return $this->build_table_name_cell( $item );
        } else if ( $key == 'start' || $key == 'end' ) {
            return stripslashes( wp_filter_nohtml_kses( Activities_Utility::format_date( $item[ $key ] ) ) );
        } else if ( $key == 'responsible' ) {
            if ( $item['responsible'] === null ) {
                $display = '&mdash;';
            } else {
                $display = $item['first_name'] . ' ' . $item['last_name'];
                if ( $display == ' ' ) {
                    $display = $item['responsible'];
                }
            }

            return stripslashes( wp_filter_nohtml_kses( $display ) );
        } else if ( $key == 'location' || $key == 'plan' ) {
            return $item[ $key ] === null ? '&mdash;' : stripslashes( wp_filter_nohtml_kses( $item[ $key ] ) );
        } else if ( $key == 'categories' ) {
            return stripslashes( wp_filter_nohtml_kses( implode( ', ', Activities_Category::get_act_categories( $item['activity_id'], true ) ) ) );
        } else {
            return parent::build_table_cell( $item, $key );
        }
    }

    /**
     * Builds a the special name cell on the table
     *
     * @param array $item Data for the cell
     *
     * @return  string  The cell
     */
    protected function build_table_name_cell( $item ) {
        global $wpdb;

        $id = $item['activity_id'];

        $user_act_table = Activities::get_table_name( 'user_activity' );
        $count          = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*)
      FROM $user_act_table
      WHERE activity_id = %d
      ",
            $id
        ) );
        $count_display  = '(' . $count . ')';

        if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) {
            $name_action = 'edit';
        } else {
            $name_action = 'view';
        }

        $output = '<div class="activities-name-wrap">';
        $output .= '<a href="' . esc_url( $this->current_url . '&action=' . $name_action . '&item_id=' . $id ) . '">' . stripslashes( wp_filter_nohtml_kses( $item['name'] ) ) . '</a> ';
        $output .= $count_display;

        $output .= '<div class="row-actions">';

        $output .= $this->build_row_actions( $id );

        $output .= '</div>'; //row-actions
        $output .= '</div>'; //name-wrap

        $output .= '<div class="activities-nice-link"><a href="' . esc_url( $this->current_url . '&action=view&item_id=' . esc_attr( $id ) ) . '"><span class="dashicons dashicons-visibility"></span></a></div>';
        $output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'activities' ) . '</span></button>';

        return $output;
    }

    /**
     * Build row actions for the name cell
     *
     * @param int $id Id of the item
     *
     * @return  string  Row actions
     */
    protected function build_row_actions( $id ) {
        $output     = '';
        $export_url = remove_query_arg( array( 'paged', 'order', 'orderby', 'action', 'view' ), $this->current_url );
        $export_url = add_query_arg( array( 'page' => 'activities-admin-export', 'item_id' => $id ), $export_url );

        if ( !$this->archive ) {
            $output .= '<a href="' . esc_url( $this->current_url . '&action=view&item_id=' . esc_attr( $id ) ) . '">' . esc_html__( 'View', 'activities' ) . '</a>';

            if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) {
                $output .= ' | <a href="' . esc_url( $this->current_url . '&action=edit&item_id=' . esc_attr( $id ) ) . '">' . esc_html__( 'Edit', 'activities' ) . '</a>';
            }

            $output .= ' | <a href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export', 'activities' ) . '</a>';
            if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
                $output .= ' | <a href="' . wp_nonce_url( $this->current_url . '&action=duplicate&item_id=' . esc_attr( $id ), 'duplicate_act_' . $id ) . '">' . esc_html__( 'Duplicate', 'activities' ) . '</a>';
            }
        } else {
            $output       .= '<a href="' . esc_url( $this->current_url . '&action=view&item_id=' . esc_attr( $id ) ) . '">' . esc_html__( 'View', 'activities' ) . '</a> | ';
            $output       .= '<a href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export', 'activities' ) . '</a> | ';
            $activate_url = wp_nonce_url( $this->current_url, 'activities_activate_activity', ACTIVITIES_ARCHIVE_NONCE_GET ) . '&action=activate&item_id=' . esc_attr( $id );
            $output       .= '<a href="' . esc_url( $activate_url ) . '" class="activities-activate">' . esc_html__( 'Activate', 'activities' ) . '</a> | ';
            $output       .= '<a href="' . esc_url( $this->current_url . '&action=delete&item_id=' . esc_attr( $id ) ) . '" class="activities-delete">' . esc_html__( 'Delete', 'activities' ) . '</a>';
        }

        return $output;
    }

    /**
     * Gets the item id
     *
     * @param array $item Item data
     *
     * @return  int     Id
     */
    protected function get_item_id( $item ) {
        return $item['activity_id'];
    }
}
