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
  $output .= '<li><input type="number" name="slots" value="' . esc_attr( stripslashes( $map['slots'] ) )  . '" /></li>';
  $output .= '<li>' . esc_html__( 'Description', 'activities' ) . '</li>';
  $output .= '<li><textarea name="description" maxlength="65535" id="acts-activity-ldesc">' . stripslashes( wp_filter_nohtml_kses ( $map['description'] ) ) . '</textarea>';
  $output .= '</li></ul>';

  $output .= '<ul class="acts-single-column">';
  for ($slot=1; $slot <= $map['slots']; $slot++) {
    $output .= '<li>' . sprintf( esc_html__( 'Slot %d', 'activities' ), $slot ) . '</li>';
    $output .= '<li><textarea name="slot[' . $slot . ']" maxlength="65535"></textarea>';
  }
  $output .= '</li></ul>';

  $output .= '</div>'; //acts-form-columns
  $output .= '</div>'; //acts-create-wrap
  $output .= '</form>';

  return $output;
}
