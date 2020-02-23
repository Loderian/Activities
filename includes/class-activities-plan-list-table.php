<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Class for getting table display for plans
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Plan_List_Table extends Activities_List_Table {
    function __construct() {
        parent::__construct();

        $this->type = 'plan';
    }

    /**
     * Get location table name
     *
     * @return string
     */
    protected function get_table_name() {
        return Activities::get_table_name( 'plan' );
    }

    /**
     * Get where builders
     *
     * @return array List of column $key => callback
     */
    protected function get_where_builders() {
        return array(
            'sessions' => function ( $value ) {
                return sprintf( "sessions = %d", $value );
            }
        );
    }

    /**
     * Gets bulk actions for activity list table
     *
     * @return array
     */
    protected function get_bulk_actions() {
        return $actions = array(
            'delete_p' => esc_html__( 'Delete', 'activities' )
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
        $options = Activities_Options::get_user_option( 'plan', 'show_columns' );

        $columns = array(
            'cb'          => array(
                'hidden'   => false,
                'sortable' => false
            ),
            'name'        => array(
                'hidden'   => false,
                'sortable' => true
            ),
            'description' => array(
                'hidden'   => !$options['description'],
                'sortable' => false
            ),
            'sessions'    => array(
                'hidden'   => !$options['sessions'],
                'sortable' => true
            )
        );

        return $columns;
    }

    /**
     * Build row actions for name cell
     *
     * @param int $id Id of the item
     *
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
     * @param array $item Item data
     *
     * @return  int     Id
     */
    protected function get_item_id( $item ) {
        return $item['plan_id'];
    }
}
