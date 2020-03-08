<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Class for getting table display for activities
 *
 * @since      1.2.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Participant_List_Table extends Activities_List_Table {

    /**
     * @inheritDoc
     */
    protected function get_table_name() {
        return Activities::get_table_name( 'participant' );
    }

    /**
     * @inheritDoc
     */
    protected function get_bulk_actions() {
        // TODO: Implement get_bulk_actions() method.
    }

    /**
     * @inheritDoc
     */
    protected function get_columns() {
        // TODO: Implement get_bulk_actions() method.
    }

    /**
     * @inheritDoc
     */
    protected function build_row_actions( $id ) {
        // TODO: Implement build_row_actions() method.
    }

    /**
     * @inheritDoc
     */
    protected function get_item_id( $item ) {
        // TODO: Implement get_item_id() method.
    }
}