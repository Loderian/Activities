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

  $categories = get_terms( array(
    'taxonomy' => Activities_Category::taxonomy,
    'hide_empty' => false,
  ));

  $parent_select = '<select name="category_parent">';
  $parent_select .= '<option value="">-- ' . esc_html__( 'Category Parent', 'activities' ) . ' --</option>';
  foreach ($categories as $term) {
    $parent_select .= '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
  }
  $parent_select .= '</select>';

	$disabled = '';
	if ( $archive == 'archive' || ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) && !Activities_Responsible::current_user_restricted_edit() ) ) {
		$disabled = 'disabled';
	}
	$output = '<h2 id="activities-title">' . $title . '</h2>';

	$output .= Activities_Admin::get_messages();

  add_thickbox();

  $output .= '<div id="acts-category-edit" style="display: none;">';
  $output .= '<form action="' . admin_url( 'admin-ajax.php' ) . '" class="acts-category-edit acts-form" method="post">';
  $output .= '<h3>' . esc_html__( 'Category', 'activities' ) . '</h3>';
  $output .= '<ul>';
  $output .= '<li><label>' . esc_html__( 'Name', 'activities' ) . '</br>';
  $output .= '<input type="text" name="category_name" placeholder="' . esc_attr__( 'Category Name', 'activities' ) . '" /></label></li>';
  $output .= '<li><label>' . esc_html__( 'Category Parent', 'activities' ) . '</br>' . $parent_select . '</label></li>';
  $output .= '<li><label>' . esc_html__( 'Description', 'activities' ) . '</br>';
  $output .= '<textarea name="category_description"></textarea></label></li>';
  $output .= '</ul>';
  $output .= '<p>';
  $output .= get_submit_button( __( 'Save', 'activities' ), 'button-primary', 'save_category', false );
  $output .= get_submit_button( __( 'Delete', 'activities' ), 'acts-delete-button button right', 'delete_category', false );
  $output .= '</p>';
  $output .= '</form>';
  $output .= '</div>';

	$output .= '<form action="' . esc_url( $current_url ) . '" method="post" class="acts-form acts-create-form">';
	$output .= '<div class="acts-create-wrap acts-box-wrap acts-box-padding">';
	$output .= '<h3>' . esc_html__( 'Activity Info', 'activities' ) . '</h3>';
	$output .= '<div class="acts-form-columns">';
	$output .= '<ul class="acts-single-column">';
	$output .= '<li>' . esc_html__( 'Name', 'activities' ) . '<span class="acts-req-mark"> *</span></li>';
	$output .= '<li><input type="text" name="name" maxlength="100" value="' . esc_attr( stripslashes( $map['name'] ) )  . '" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'Short Description', 'activities' ) . '</li>';
	$output .= '<li><input type="text" name="short_desc" maxlength="255" value="' . esc_attr( stripslashes( $map['short_desc'] ) )  . '" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'Long Description', 'activities' ) . '</li>';
	$output .= '<li><textarea name="long_desc" maxlength="65535" id="acts-activity-ldesc" ' . $disabled . ' >' . stripslashes( wp_filter_nohtml_kses ( $map['long_desc'] ) ) . '</textarea>';
  $output .= '</li></ul>';

	$output .= '<ul class="acts-single-column">';
	$output .= '<li>' . esc_html__( 'Start date', 'activities' ) . '</li>';
	$output .= '<li><input type="date" name="start" value="' . esc_attr( explode( " ", $map["start"] )[0] ) . '" ' . $disabled . ' /></li>';
	$output .= '<li>' . esc_html__( 'End date', 'activities' ) . '</li>';
	$output .= '<li><input type="date" name="end" value="' . esc_attr( explode( " ", $map["end"] )[0] ) . '" ' . $disabled . ' /></li>';
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

	$output .= '</ul></div>'; //acts-activity-form-columns

  $output .= '<div>';
	$output .= '<p>' . esc_html__( 'Activity Participants', 'activities' ) . ' (<span id="member_count"></span>)' . ' </p>';
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
	if ( $archive != 'archive' && ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) || Activities_Responsible::current_user_restricted_edit() ) ) {
		$output .= get_submit_button( $button, 'button-primary', ($action . '_act'), false );
	}
	else if ( $archive == 'archive' ){
		$output .= '<a href="' . esc_url( $current_url . '&action=activate&item_id=' . esc_attr( acts_validate_id( $_GET['item_id'] ) ) ) . '" class="button button-primary" >' . esc_html__( 'Activate', 'activities' ) . '</a>';
	}
	$output .= ' <a href="' . esc_url( $current_url ) . '" class="button" >' . esc_html__( 'Cancel', 'activities' ) . '</a>';
	if ( isset( $_GET['item_id'] ) || isset( $map['activity_id'] ) ) {
		$activity_id = acts_validate_id( (isset( $_GET['item_id'] ) ? $_GET['item_id'] : $map['activity_id']) );
		$output .= '<input type="hidden" name="item_id" value="' . esc_attr( $activity_id ) . '" />';
	}
  $output .= '</p>';
	$output .= '</div>';
	$output .= wp_nonce_field( 'activities_activity', ACTIVITIES_ACTIVITY_NONCE, true, false );
	$output .= '</div>'; //acts-create-wrap

  $output .= '<div class="acts-create-extra-wrap">';
  $output .= '<div class="acts-categories acts-create-extra acts-box-wrap acts-box-padding">';
  $output .= '<h3>' . esc_html__( 'Categories', 'activities') . ' ' . get_submit_button( '+', 'button', 'show_category_form', false ) . '</h3>';
  $output .= '<ul id="category_form" style="display: none;">';
  $output .= '<li><input type="text" name="category_name" placeholder="' . esc_attr__( 'Category Name', 'activities' ) . '" /><li>';
  $output .= '<li>';
  $output .= $parent_select;
  $output .= '</li>';
  $output .= '<li>' . get_submit_button( esc_html__( 'Create Category', 'activities' ), 'button', 'create_category', false ) . '</li>';
  $output .= '<li><hr/></li>';
  $output .= '</ul>';

  $output .= '<table class="activities-table">';
  $output .= '<thead>';
  $output .= '<tr>';
  $output .= '<td></td>';
  $output .= '<td>' . esc_html__( 'Primary', 'activities' ) . '</td>';
  $output .= '<td>' . esc_html__( 'Additional', 'activities' ) . '</td>';
  $output .= '</tr>';
  $output .= '<tbody>';
  foreach ($categories as $term) {
    $output .= '<tr>';
    $output .= '<td class="acts-category-name"><a href="" category="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '<span class="dashicons"></span></a></td>';
    $output .= '<td><input type="radio" name="primary_category" value="' . esc_attr( $term->slug ) . '" /></td>';
    $output .= '<td><input type="checkbox" name="additional_categories[' . esc_attr( $term->slug ) . ']" /></td>';
    $output .= '</tr>';
  }
  $output .= '</tbody>';
  $output .= '</thead>';

  $output .= '</table>';
  $output .= '</div>';

  $output .= '</div>';

  $output .= '</form>';

	return $output;
}
