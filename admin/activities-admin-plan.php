<?php

/**
 * Plan management page
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Builds page for editing a activity
 *
 * @param   string  $title Page title
 * @param   string  $action Display and name text for primary button
 * @param   array   $map Information about the activity
 * @return  string  Page
 */
function acts_plan_management( $title, $action, $map = null ) {
  global $wpdb;

  $current_url = ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $current_url = remove_query_arg( 'action', $current_url );
  $current_url = remove_query_arg( 'item_id', $current_url );

  if ( $map === null ) {
    $map = array(
      'name' => '',
      'sessions' => 6,
      'description' => '',
      'session_map' => array()
    );
  }

  $output = '';

  $output .= '<h2 id="activities-title">' . $title . '</h2>';

  $output .= Activities_Admin::get_messages();

  $output .= '<form action="' . esc_url( $current_url ) . '" method="post" class="acts-form acts-create-form">';
  $output .= '<div class="acts-create-wrap acts-box-wrap acts-box-padding">';
  $output .= '<h3>' . esc_html__( 'Plan Info', 'activities' ) . '</h3>';
  $output .= '<div class="acts-form-columns">';
  $output .= '<ul class="acts-single-column">';
  $output .= '<li>' . esc_html__( 'Name', 'activities' ) . '<span class="acts-req-mark"> *</span></li>';
  $output .= '<li><input type="text" name="name" maxlength="200" value="' . esc_attr( stripslashes( $map['name'] ) ) . '" /></li>';
  $output .= '<li>' . esc_html__( 'Slots', 'activities' ) . '</li>';
  $output .= '<li><input type="number" id="plan_sessions" min="1" max="50" name="sessions" value="' . esc_attr( stripslashes( $map['sessions'] ) )  . '" /></li>';
  $output .= '</ul>';

  $output .= '<ul class="acts-single-column">';
  $output .= '<li>' . esc_html__( 'Description', 'activities' ) . '</li>';
  $output .= '<li><textarea name="description" maxlength="65535" id="acts-plan-desc">' . stripslashes( wp_filter_nohtml_kses ( $map['description'] ) ) . '</textarea></li>';
  $output .= '</ul>';

  $output .= '</div>'; //acts-form-columns

  $output .= '<ul class="acts-plan-textareas">';
  if ( $map['sessions'] < 1 ) {
    $map['sessions'] = 1;
  }
  for ($session=1; $session <= $map['sessions']; $session++) {
    $output .= '<li session="' . $session . '"><span class="acts-session-text-num">' . sprintf( esc_html__( 'Session %d', 'activities' ), $session ) . '</span></br>';
    $text = '';
    if ( array_key_exists( $session, $map['session_map'] ) ) {
      $text = stripslashes( wp_filter_nohtml_kses( $map['session_map'][$session] ) );
    }
    $output .= '<textarea name="session_map[' . $session . ']" maxlength="65535">' . $text . '</textarea>';
    $output .= '</li>';
  }
  $output .= '</ul>';

  $button = '';
  switch ($action) {
    case 'create':
      $button = esc_html__( 'Create', 'activities' );
      break;

    case 'edit':
      $button = esc_html__( 'Save', 'activities' );
      break;
  }
  $output .= '<p>';
  $output .= get_submit_button( $button, 'button-primary', $action . '_plan', false );
	$output .= ' <a href="' . esc_url( $current_url ) . '" class="button" >' . esc_html__( 'Cancel', 'activities' ) . '</a>';
  if ( isset( $_GET['item_id'] ) || isset( $map['plan_id'] ) ) {
		$plan_id = acts_validate_id( (isset( $_GET['item_id'] ) ? $_GET['item_id'] : $map['plan_id']) );
		$output .= '<input type="hidden" name="item_id" value="' . esc_attr( $plan_id ) . '" />';
	}
  $output .= '</p>';

  $output .= wp_nonce_field( 'activities_plan', ACTIVITIES_PLAN_NONCE, true, false );

  $output .= '</div>'; //acts-create-wrap
  $output .= '</form>';

  return $output;
}
