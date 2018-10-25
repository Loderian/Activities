<?php

/**
 * Activity management page
 *
 * @since      1.0.0
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
 * @param   string  $button Display and name text for primary button
 * @param   array   $map Information about the activity
 * @param   string  $archive Set to 'archive' to disable all fields, default ''
 * @return  array   A list of user info to display in coloumn 1 ('col1') and column 2 ('col2')
 */
function acts_activity_management( $title, $action, $map = null, $archive = '' ) {
	global $wpdb;

	$current_url = ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'item_id', $current_url );

	if ( $map === null ) {
		$map = array(
			'name' => '',
			'short_desc' => '',
			'long_desc' => '',
			'start' => date('Y-m-d'),
			'end' => date('Y-m-d'),
			'location_id' => '',
			'responsible_id' => '',
			'members' => array()
		);
	}

	$disabled = '';
	if ( $archive == 'archive' || ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) && !Activities_Responsible::current_user_restricted_edit() ) ) {
		$disabled = 'disabled';
	}
	$output = '<h2 id="activities-title">' . $title . '</h2>';

	$output .= Activities_Admin::get_messages();

	$output .= '<form action="' . esc_url( $current_url ) . '" method="post">';
	$output .= '<div id="acts-activity-create-wrap" class="activities-box-wrap activities-box-padding">';
	$output .= '<h3>' . esc_html__( 'Activity Info', 'activities' ) . '</h3>';
	$output .= '<ul id="acts-activity-form-columns">';
	$output .= '<li id="acts-activity-left-column"><ul>';
	$output .= '<li>' . esc_html__( 'Name', 'activities' ) . '<span class="acts-req-mark"> *</span></li>';
	$output .= '<li><input type="text" name="name" maxlength="100" value="' . esc_attr( stripslashes( $map['name'] ) )  . '" id="acts-activity-name" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'Short Description', 'activities' ) . '</li>';
	$output .= '<li><input type="text" name="short_desc" maxlength="255" value="' . esc_attr( stripslashes( $map['short_desc'] ) )  . '" id="acts-activity-short-desc" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'Long Description', 'activities' ) . '</li>';
	$output .= '<li><textarea name="long_desc" maxlength="65535" id="acts-activity-long-desc" ' . $disabled . ' >' . stripslashes( wp_filter_nohtml_kses ( $map['long_desc'] ) ) . '</textarea></li></ul></li>';

	$output .= '<li id="acts-activity-right-column"><ul>';
	$output .= '<li>' . esc_html__( 'Start date', 'activities' ) . '</li>';
	$output .= '<li><input type="date" name="start" value="' . esc_attr( explode( " ", $map["start"] )[0] ) . '" id="acts-activity-start" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'End date', 'activities' ) . '</li>';
	$output .= '<li><input type="date" name="end" value="' . esc_attr( explode( " ", $map["end"] )[0] ) . '" id="acts-activity-end" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'Responsible', 'activities' ) . '</li>';

  $output .= '<li>';
  $output .= acts_build_select_items(
    'responsible',
    array(
      'name' => 'responsible',
      'id' => 'acts-activity-responsible',
      'selected' => $map['responsible_id'],
      'disabled' => Activities_Responsible::current_user_restricted_edit() || ( $disabled !== '' )
    )
  );
  if ( Activities_Responsible::current_user_restricted_edit() ) {
    $output .= '<input type="hidden" name="responsible" value="' . esc_attr( $map['responsible_id'] ) . '" />';
  }
  $output .= '</li>';

	$output .= '<li>' . esc_html__( 'Location', 'activities' ) . '</li>';
  $output .= '<li>';
  $output .= acts_build_select_items(
    'location',
    array(
      'name' => 'location',
      'id' => 'acts-activity-location',
      'selected' => $map['location_id'],
      'disabled' => $disabled !== ''
    )
  );
  $output .= '</li>';

	$output .= '</ul></li></ul><ul>';

	$output .= '<li>' . esc_html__( 'Activity Participants', 'activities' ) . ' (<span id="member_count"></span>)' . ' </li>';
  $output .= '<li>';
  $output .= acts_build_select_items(
    'members',
    array(
      'name' => 'member_list[]',
      'id' => 'acts-activity-member-list',
      'selected' => is_string( $map['members'] ) ? explode( ',', $map['members'] ) : $map['members'],
      'multiple' => true,
      'disabled' => $disabled !== ''
    )
  );
  $output .= '</li>';

	$output .= '<li>';
  $button = '';
  switch ($action) {
    case 'create':
      $button = esc_html__( 'Create', 'activities' );
      break;

    case 'edit':
      $button = esc_html__( 'Save', 'activities' );
      break;
  }
	if ( $archive != 'archive' && ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) ) {
		$output .= '<input type="submit" value="' . esc_attr( $button ) . '" name="' . esc_attr( $action ) . '_act" class="button button-primary" />';
	}
	else if ( $archive == 'archive' ){
		$output .= '<a href="' . esc_url( $current_url . '&action=activate&item_id=' . esc_attr( acts_validate_id( $_GET['item_id'] ) ) ) . '" class="button button-primary" >' . esc_html__( 'Activate', 'activities' ) . '</a>';
	}
	$output .= ' <a href="' . esc_url( $current_url ) . '" class="button" >' . esc_html__( 'Cancel', 'activities' ) . '</a>';
	if ( isset( $_GET['item_id'] ) || isset( $map['activity_id'] ) ) {
		$activity_id = acts_validate_id( (isset( $_GET['item_id'] ) ? $_GET['item_id'] : $map['activity_id']) );
		$output .= '<input type="hidden" name="item_id" value="' . esc_attr( $activity_id ) . '" />';
	}
	$output .= '</li></ul>';
	$output .= wp_nonce_field( 'activities_activity', ACTIVITIES_ACTIVITY_NONCE, true, false );
	$output .= '</div>';
  $output .= '</form>';

	return $output;
}
