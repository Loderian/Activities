<?php

/**
 * Participants main page
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
 * Builds the main admin page for participants
 *
 * @return string Admin page for participants or participant
 */
function activities_admin_participants() {
    $current_url = activities_admin_access_activities( activities_admin_item_page_args() );

    $output = '<h1 id="activities-title">';
    $output .= esc_html__( 'Participants', 'activities' );
    if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
        $output .= '<a href="' . esc_url( $current_url ) . '&action=create" class="add page-title-action">' . esc_html__( 'Create new participant', 'activities' ) . '</a>';
    }
    $output .= '</h1>';

    $table_builder = new Activities_Participant_List_Table();

    $output .= $table_builder->display();

    return $output;
}