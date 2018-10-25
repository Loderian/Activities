<?php

/**
 * Generic builder functions
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
 * Builds page for doing bulk actions
 *
 * @param 	array 	$ids Ids of activities selected
 * @param 	string 	$type The type of bulk action
 * @param 	string 	$header Header for the bulk action page
 * @param 	array 	$names Names of the selected activities
 * @param 	string 	$value Values for input fields
 * @return 	string 	Page for doing bulk actions
 */
function activities_bulk_action_page( $ids, $type, $header, $names, $value = '' ) {
	global $wpdb;

	$current_url = ( isset($_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'item_id', $current_url );

	$output = '<h1>' . $header . '</h1>';

	$output .= Activities_Admin::get_messages();

	$output .= '<div class="activities-box-wrap activities-box-padding">';
	$output .= '<form action="' . esc_url( $current_url ) . '" method="post">';
	if ( $type == 'address' || $type == 'delete_l') {
		$output .= '<h2>' . esc_html__( 'Selected Locations', 'activities' ) . '</h2>';
	}
	else {
		$output .= '<h2>' . esc_html__( 'Selected Activities', 'activities' ) . '</h2>';
	}
	$output .= '<p>' . stripslashes( wp_filter_nohtml_kses( implode( ', ', $names ) ) ). '</p>';

	$output .= '<h2>' . esc_html__( 'Bulk Action', 'activities' ) . '</h2>';
	switch ($type) {
		case 'archive':
			$output .= '<p>' . sprintf( esc_html__( 'Are you sure you want to archive %d activities?', 'activities' ), count( $names ) ) . '</p>';
			break;

		case 'change_location':
			$output .= '<p><b>' . esc_html__( 'Location', 'activities' ) . '</b></p>';
      $output .= acts_build_select_items(
        'location',
        array(
          'name' => 'location',
          'id' => 'acts_bulk_selectize',
          'selected' => $value
        )
      );

			break;

		case 'change_responsible':
			$output .= '<p><b>' . esc_html__( 'Responsible', 'activities' ) . '</b></p>';
      $output .= acts_build_select_items(
        'responsible',
        array(
          'name' => 'responsible',
          'id' => 'acts_bulk_selectize',
          'selected' => $value
        )
      );

			break;

		case 'change_members':
			$output .= '<label for="save_method"><b>' . esc_html__( 'Select a Save Method', 'activities' ) . '</b></label></br>';
			$output .= '<select name="method">';
			$output .= '<option value="null" selected>' . esc_html__( 'Method', 'activities' ) . '</option>';
			$output .= '<option value="replace">' . esc_html__( 'Replace', 'activities' ) . '</option>';
			$output .= '<option value="add">' . esc_html__( 'Add', 'activities' ) . '</option>';
			$output .= '<option value="remove">' . esc_html__( 'Remove', 'activities' ) . '</option>';
			$output .= '</select>';
			$output .= '<p><b>' . esc_html__( 'Participants', 'activities' ) . '</b></p>';
      $output .= acts_build_select_items(
        'members',
        array(
          'name' => 'members[]',
          'id' => 'acts_bulk_selectize',
          'selected' => $value,
          'multiple' => true
        )
      );

			break;

		case 'activate':
			$output .= '<p>' . sprintf( esc_html__( 'Are you sure you want to activate %d activities?', 'activities' ), count( $names ) ) . '</p>';
			break;

		case 'delete_a':
			$output .= '<p>' . sprintf( esc_html__( 'Are you sure you want to delete %d activities?', 'activities' ), count( $names ) ) . '</p>';
			$output .= '<p><b>' . esc_html__( 'Warning: Activities cannot be recovered after deletion.', 'activities' ) . '</b></p>';
			break;

		case 'address':
			$output .= '<p><b>' . esc_html__( 'Address', 'activities' ) . '</b></p>';
			$output .= '<input type="text" name="address" maxlength="255" id="acts-location-address" style="padding: 7px;" /></li>';
			break;

		case 'delete_l';
			$output .= '<p>' . sprintf( esc_html__( 'Are you sure you want to delete %d locations?', 'activities' ), count( $names ) ) . '</p>';
			$output .= '<p><b>' . esc_html__( 'Warning: Locations cannot be recovered after deletion.', 'activities' ) . '</b></p>';
			break;
	}

	$output .= '<input type="hidden" name="selected_activities" value="' . esc_attr( implode( ',', $ids ) ) . '" />';
	$output .= '<input type="hidden" name="bulk" value="' . esc_attr( $type ) . '" />';
	$output .= '<div>';
	$output .= '<input type="submit" value="' . esc_html__( 'Confirm', 'activities' ) . '" name="confirm_bulk" class="button button-primary" />';
	$output .= ' <a href="' . esc_url( $current_url ) . '" class="button">' . esc_html__( 'Cancel', 'activities' ) . '</a>';
	$output .= '</div>';
	$output .= wp_nonce_field( 'activities_bulk_action', ACTIVITIES_BULK_NONCE );
	$output .= '</form>';
	$output .= '</div>';

	return $output;
}

/**
 * Builds page for confirming deletion
 *
 * @param 	string 	$display Name of item type (Activity or Location translated)
 * @param 	int 		$item_id Id of the item to delete
 * @param 	string 	$name Name of the item to delete
 * @param 	string 	$current_url Previous page
 * @return 	string 	Page for confirming deletion
 */
function acts_confirm_item_delete_page( $display, $item_id, $name, $current_url ) {
	$output = '<h1>' . sprintf( esc_html__( 'Delete %s', 'activities' ), $display ) . '</h1>';
	$output .= '<div class="activities-box-wrap activities-box-padding">';
	$output .= '<form action="' . esc_url( $current_url ) . '" method="post">';
	$output .= '<h2>' . sprintf( esc_html__( 'Selected %s', 'activities' ), $display ) . ': </h2>';
	$output .= '<p style="font-size: 1.2em;">' . stripslashes( wp_filter_nohtml_kses( $name ) ) . '</p>';
	$output .= '<p>' . sprintf( esc_html__( 'Are you sure you want to delete this %s?', 'activities' ), lcfirst( $display ) ) . '</p>';
	$output .= '<p><b>' . sprintf( esc_html__('Warning: %s cannot be recovered after deletion.', 'activities' ), $display ) . '</b></p>';
	$output .= '<input type="hidden" name="item_id" value="' . esc_attr( $item_id ) . '" />';
	$output .= '<input type="hidden" name="item_name" value="' . esc_attr( $name ) . '" />';
	$output .= wp_nonce_field( 'activities_delete_item', ACTIVITIES_DELETE_ITEM_NONCE, true, false );
	$output .= '<input type="submit" value="' . esc_html__( 'Confirm', 'activities' ) . '" name="confirm_deletion" class="button button-primary" /> ';
	$output .= '<a href="' . esc_url( $current_url ) . '" class="button">' . esc_html__( 'Cancel', 'activities' ) . '</a>';
	$output .= '</form>';
	$output .= '</div>';

	return $output;
}

/**
 * Bulds basic select input
 *
 * @param array $data Data for values and display for select options
 * @param array $settings Setting for the select input
 *    - string name => The select name
 *    - string id => Select id
 *    - array class => Select classes
 *    - array selected => Values selected
 *    - bool multiple => true for multiple select
 *		- bool no_blank => true to remove the blank choice (forces a selection)
 *    - bool disabled => true to disable
 *
 * @return string Select html
 */
function acts_build_select( $data, $settings ) {
	if ( !is_array( $data ) ) {
		return '';
	}
	$settings = array_merge(
		array(
			'name' => '',
			'id' => '',
			'class' => array(),
			'selected' => array(),
			'multiple' => false,
			'no_blank' => false,
      'disabled' => false
		),
		$settings
	);
  $multiple = $settings['multiple'] ? 'multiple' : '';
  $disabled = $settings['disabled'] ? 'disabled' : '';
	$output = '<select name="' . esc_attr( $settings['name'] ) .
            '" id="' . esc_attr( $settings['id'] ) .
            '" class="' . esc_attr( implode( ' ', $settings['class'] ) ) .
            '" ' . $multiple . '
               ' . $disabled . '>';
	if ( !$settings['no_blank'] ) {
		$output .= '<option default value=""></option>';
	}
	foreach ($data as $key => $name) {
		$selected = '';
		if ( ( is_array( $settings['selected'] ) && in_array( $key, $settings['selected'] ) ) || $key == $settings['selected'] ) {
			$selected = 'selected';
		}
		$output .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . stripslashes( wp_filter_nohtml_kses( $name ) ) . '</option>';
	}
	$output .= '</select>';

	return $output;
}

/**
 * Loads items and builds a select dropdown
 *
 * @param   string  $type Type of item
 * @param   array   $settings Setting for the select input
 *      - string name => The select name
 *      - string id => Select id
 *      - array class => Select classes
 *      - array selected => Values selected
 *      - string multiple => 'multiple' for multiple select
 *		  - no_blank => true to remove the blank choice (forces a selection)
 *
 * @param   bool    $responsible_filter Filter activities by current user id, if they cant view activities the list will be empty
 * @return  string  Select html
 */
function acts_build_select_items( $type, $settings, $responsible_filter = false ) {
  global $wpdb;

  $data = array();
  switch ($type) {
    case 'activity':
    case 'location':
    case 'activity_archive':
    case 'all_activities':
      $data = acts_get_items_map( $type, 'name', $responsible_filter );
      break;

    case 'responsible':
    case 'members':
    case 'member':
      if ( !array_key_exists( 'selected', $settings ) ) {
        $settings['selected'] = array();
      }
      $data = Activities_Admin_Utility::get_users( $type, $settings['selected'] );
      break;
  }

  return acts_build_select( $data, $settings );
}
