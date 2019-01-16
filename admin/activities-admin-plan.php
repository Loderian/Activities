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
function activities_plan_management( $title, $action, $map = null ) {
  global $wpdb;

  $current_url = ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $current_url = remove_query_arg( 'action', $current_url );
  $current_url = remove_query_arg( 'item_id', $current_url );

  if ( $map === null ) {
    $map = array(
      'name' => '',
      'slots' => 6,
      'description' => '',
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
  $output .= '<li><input type="number" id="plan_slots" min="1" max="50" name="slots" value="' . esc_attr( stripslashes( $map['slots'] ) )  . '" /></li>';
  $output .= '<li>' . esc_html__( 'Description', 'activities' ) . '</li>';
  $output .= '<li><textarea name="description" maxlength="65535" id="acts-activity-ldesc">' . stripslashes( wp_filter_nohtml_kses ( $map['description'] ) ) . '</textarea>';
  $output .= '</li></ul>';

  $output .= '<ul class="acts-single-column acts-plan-textareas">';
  if ( $map['slots'] < 1 ) {
    $map['slots'] = 1;
  }
  for ($slot=1; $slot <= $map['slots']; $slot++) {
    $output .= '<li slot="' . $slot . '"><span class="acts-slot-text-num">' . sprintf( esc_html__( 'Session %d', 'activities' ), $slot ) . '</span></br>';
    $output .= '<textarea name="slot[' . $slot . ']" maxlength="65535"></textarea></li>';
  }
  $output .= '</ul>';

  $output .= '</div>'; //acts-form-columns

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
