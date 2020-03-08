<?php

/**
 * Common access check
 *
 * @since      1.2.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * @param array $clear_args Url arguments to remove
 *
 * @return string Current page url without arguments supplied in clear_args
 */
function activities_admin_access_activities( array $clear_args ) {
    if ( !current_user_can( ACTIVITIES_ACCESS_ACTIVITIES ) ) {
        wp_die( esc_html__( 'Access Denied', 'activities' ) );
    }

    $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    foreach ( $clear_args as $arg ) {
        $current_url = remove_query_arg( $arg, $current_url );
    }

    return $current_url;
}

function activities_admin_item_page_args() {
    return array( 'action', 'item_id', '_wpnonce' );
}