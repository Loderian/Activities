<?php

/**
 * Activity report plan editing
 *
 * @since      1.1.8
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Builds the plan box under the activity report
 *
 * @param int $plan_id Plan saved to activity
 * @param array $session_map Mapping of session texts
 * @param int $time_slots Amount of time slots (sessions)
 * @param int $last_session The last session someone attended
 *
 * @return  string
 */
function acts_build_plans_box( int $plan_id, array $session_map, int $time_slots, int $last_session ) {
    $plan = Activities_Plan::load( $plan_id );

    $plan_name = '';
    $plan_map  = array();
    $sessions  = $time_slots;
    if ( $plan !== null ) {
        $plan_map = $plan['session_map'];
        if ( $plan['sessions'] > $time_slots ) {
            $sessions = $plan['sessions'];
        }
        $plan_name = $plan['name'];
    }

    $output = '<div class="acts-nice-wrap">';
    $output .= '<h3>' . esc_html( ucfirst( acts_get_multi_item_translation( 'plan', $sessions ) ) );
    if ( $plan_name != '' ) {
        $output .= ' <span style="color: grey;">(<span class="acts-nice-plan-name">' . esc_html( $plan_name ) . '</span>)</span>';
    }
    $output .= '</h3>';

    $output .= '<ul class="acts-nice-session-list">';
    for ( $session_id = 1; $session_id <= $sessions; $session_id++ ) {
        $text = '';
        if ( array_key_exists( $session_id, $session_map ) ) {
            $text = $session_map[ $session_id ];
        } elseif ( array_key_exists( $session_id, $plan_map ) ) {
            $text = $plan_map[ $session_id ];
        }
        $output .= acts_build_session_box( $session_id, $text, $last_session );
    }
    $output .= '</ul>';

    if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
        $output .= '<div>';
        $output .= '<span class="acts-nice-new-response"></span>';
        $output .= '<span class="acts-nice-new-plan">';
        $output .= '<input type="text" maxlength="200" placeholder="' . esc_html__( 'Plan name', 'activities' ) . '" name="plan_name" value="' . esc_attr( $plan_name ) . '" /> ';
        $button = __( 'Create plan', 'activities' );
        if ( $plan_name != '' ) {
            $button = __( 'Update plan', 'activities' );
        }
        $output .= get_submit_button( $button, 'button-primary', 'create_plan', false );
        $output .= '</span>';
        $output .= '</div>';
    }

    $output .= '<div class="clear"></div>';

    $output .= '<input type="hidden" name="plan_id" value="' . esc_attr( $plan_id ) . '" />';
    $output .= '</div>';

    return $output;
}

/**
 * Builds a single session text box
 *
 * @param int $session_id Session
 * @param string $text Session text
 * @param int $last_session The next session after someone attended
 *
 * @return  string
 */
function acts_build_session_box( $session_id, $text, $last_session ) {
    $empty_text = '';
    if ( $text == '' ) {
        $empty_text = '<div class="acts-nice-session-empty">' . esc_html__( 'Empty', 'activities' ) . '</div>';
    }
    $arrow  = ' dashicons-arrow-down';
    $hidden = ' acts-nice-session-hidden';
    if ( $session_id == $last_session || $session_id == $last_session - 1 ) {
        $arrow  = ' dashicons-arrow-up';
        $hidden = '';
    }

    $output = '<li session="' . $session_id . '" class="acts-nice-session">';
    $output .= '<b class="acts-nice-session-expand">' . esc_html__( 'Session', 'activities' ) . ' <span >' . $session_id . '</span><span class="dashicons' . $arrow . '"></span></b>';
    $output .= '<span class="acts-nice-print-hidden"> | </span><span class="acts-nice-session-edit">' . esc_html__( 'Edit', 'activities' ) . '<span class="dashicons dashicons-edit"></span></span>';
    $output .= '</br>';

    $output .= '<div class="acts-nice-session-text' . $hidden . '" name="session_map[' . $session_id . ']">' . $empty_text . esc_html( $text ) . '</div>';
    $output .= '</li>';

    return $output;
}

function acts_report_plan_session_edit_box() {
    $output = '<div id="acts-plan-session-edit" style="display: none">';
    $output .= '<form action="' . admin_url( 'admin-ajax.php' ) . '" class="acts-plan-session-edit-box acts-form" method="post">';
    $output .= '<h4></h4>';
    $output .= '<textarea></textarea>';
    $output .= '<input type="hidden" name="session_number" value="" />';
    $output .= get_submit_button( esc_html__( 'Update session on report', 'activities' ), 'button-primary', 'acts_save_plan_session', false );
    $output .= '</form>';
    $output .= '</div>';

    return $output;
}