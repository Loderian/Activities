<?php

/**
 * Functions
 *
 * @since      1.0.1
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Sanitizes and validates an item id
 *
 * @param int $id Id
 *
 * @return  int $id if validated, otherwise 0
 */
function acts_validate_id( $id ) {
    $id = sanitize_key( $id );
    if ( is_numeric( $id ) ) {
        $id = intval( $id );
        if ( $id < 0 ) {
            return 0;
        } else {
            return $id;
        }
    } else {
        return 0;
    }
}

/**
 * Loads items and maps ids to value
 *
 * @param string $type Type of item
 * @param string $key Value to get
 * @param bool $responsible_filter Get only items where the current user is responsible
 *
 * @return  array   Map of id to data
 */
function acts_get_items_map( $type, $key = 'name', $responsible_filter = false ) {
    global $wpdb;

    switch ( $type ) {
        case 'activity':
        case 'activity_archive':
        case 'all_activities':
            $table_name = Activities::get_table_name( 'activity' );
            $id         = 'activity_id';

            if ( $type === 'activity' ) {
                $where = 'archive = 0';
                if ( $responsible_filter ) {
                    $where .= sprintf( ' AND responsible_id = %d', get_current_user_id() );
                }
            } elseif ( $type === 'activity_archive' ) {
                $where = 'archive = 1';
            } else {
                if ( $responsible_filter ) {
                    $where = sprintf( 'archive = 0 AND responsible_id = %d', get_current_user_id() );
                }
            }
            break;

        case 'location':
            $table_name = Activities::get_table_name( 'location' );
            $id         = 'location_id';
            break;

        case 'plan':
            $table_name = Activities::get_table_name( 'plan' );
            $id         = 'plan_id';
            break;
    }

    if ( isset( $table_name ) && isset( $id ) ) {
        $where_sql = '';
        if ( isset( $where ) ) {
            $where_sql = "WHERE $where";
        }
        $items = $wpdb->get_results(
            "SELECT $id, $key
      FROM $table_name
      $where_sql"
        );

        $map = array();
        foreach ( $items as $item ) {
            $map[ $item->$id ] = $item->$key;
        }

        return $map;
    }

    return array();
}
