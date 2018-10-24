<?php

/**
 * Activities location management
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
 * Builds page for editing a location
 *
 * @param 	string 	$title Page title
 * @param 	string 	$action Display and name for primary button
 * @param 	array 	$map Location values
 * @return 	string 	Page for doing bulk actions
 */
function acts_location_management( $title, $action, $map = null ) {
	$current_url = ( isset($_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'location_id', $current_url );

	if ( is_null( $map ) ) {
		$map = array(
			'name' => '',
			'address' => '',
			'description' => '',
			'city' => '',
			'postcode' => '',
			'country' => ''
		);
	}

	$output = '<h1 id="activities-title">' . $title . '</h1>';

	$output .= Activities_Admin::get_messages();

	$output .= '<div id="acts-location-create-wrap" class="activities-box-wrap activities-box-padding">';
	$output .= '<h3>' . esc_html__( 'Location Info', 'activities' ) . '</h3>';
	$output .= '<form action="' . esc_url( $current_url ) . '" method="post">';
	$output .= '<ul id="acts-activity-form-columns">';
	$output .= '<li id="acts-activity-left-column"><ul>';
	$output .= '<li>' . esc_html__( 'Name', 'activities' ) . ' <span class="acts-req-mark">*</span></li>';
	$output .= '<li><input type="text" name="name" maxlength="100" value="' . esc_attr( stripslashes( $map['name'] ) ) . '" id="acts-location-name" /></li>';
	$output .= '<li>' . esc_html__( 'Address', 'activities' ) . '</li>';
	$output .= '<li><input type="text" name="address" maxlength="255" value="' . esc_attr( stripslashes( $map['address'] ) ) . '" id="acts-location-address" /></li>';
	$output .= '<li>' . esc_html__( 'Postcode', 'activities' ) . '</li>';
	$output .= '<li><input type="text" name="postcode" maxlength="12" value="' . esc_attr( stripslashes( $map['postcode'] ) ) . '" id="acts-location-postcode"/></li>';
	$output .= '<li>' . esc_html__( 'City', 'activities' ) . '</li>';
	$output .= '<li><input type="text" name="city" maxlength="100" value="' . esc_attr( stripslashes( $map['city'] ) ) . '" id="acts-location-city"/></li>';
	$output .= '<li>' . esc_html__( 'Country', 'activities' ) . '</li>';
	$output .= '<li>';
	$output .= acts_build_select(
		Activities_Utility::get_countries(),
		array(
			'name' => 'country',
			'id' => 'acts-location-country',
			'selected' => array( $map['country'] )
		)
	);
	$output .= '</li>';
	$output .= '</ul></li>';
	$output .= '<li id="acts-activity-right-column"><ul>';
	$output .= '<li>' . esc_html__( 'Description', 'activities' ) . '</li>';
	$output .= '<li><textarea name="description" maxlength="65536" id="acts-location-desc">' . stripslashes( wp_filter_nohtml_kses( $map['description'] ) ) . '</textarea></li>';
	$output .= '</ul></li></ul>';
	switch ($action) {
    case 'create':
      $button = esc_html__( 'Create', 'activities' );
      break;

    case 'edit':
      $button = esc_html__( 'Save', 'activities' );
      break;
  }
	$output .= '<input type="submit" value="' . $button . '" name="' . esc_attr( $action ) . '_loc" class="button button-primary" />';
	$output .= ' <a href="' . esc_url( $current_url ) . '" class="button">' . esc_html__( 'Cancel', 'activities' ) . '</a></li>';
	if ( isset( $_GET['item_id'] ) || isset( $map['location_id'] ) ) {
		$location_id = acts_validate_id( (isset( $_GET['item_id'] ) ? $_GET['item_id'] : $map['location_id']) );
		$output .= '<input type="hidden" name="item_id" value="' . esc_attr( $location_id ) .'" />';
	}
	$output .= wp_nonce_field( 'activities_location', ACTIVITIES_LOCATION_NONCE, true, false );
	$output .= '</form></div>';

	return $output;
}
