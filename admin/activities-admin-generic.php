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

	$all_selectize = array();

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
			$output .= '<input type="text" name="location" id="acts-location" value="' . esc_attr( $value ) . '" />';

			$location_table = Activities::get_table_name( 'location' );
			$locations = $wpdb->get_results(
				"SELECT location_id, name
				FROM $location_table
				",
				ARRAY_A
			);

			$all_selectize[] = array(
				'name' => 'acts-location',
				'value' => 'location_id',
				'label' => 'name',
				'search' => array( 'name' ),
				'option_values' => $locations,
				'max_items' => '1'
			);

			break;

		case 'change_responsible':
			$output .= '<p><b>' . esc_html__( 'Responsible', 'activities' ) . '</b></p>';
			$output .= '<input type="text" name="responsible" id="acts-responsible" value="' . esc_attr( $value ) . '" />';

			$users = Activities_Admin_Utility::get_users( 'responsible' );

			$all_selectize[] = array(
				'name' => 'acts-responsible',
				'value' => 'ID',
				'label' => 'display_name',
				'search' => array( 'display_name' ),
				'option_values' => $users,
				'max_items' => '1'
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
			$output .= '<input type="text" name="members" id="acts-members" value="' . esc_attr( $value ) . '" />';

			$users = Activities_Admin_Utility::get_users( 'member' );

			$all_selectize[] = array(
				'name' => 'acts-members',
				'value' => 'ID',
				'label' => 'display_name',
				'search' => array( 'display_name' ),
				'option_values' => $users,
				'max_items' => 'null',
				'extra' => array( 'plugins: ["remove_button"]' )
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

	$output .= activities_build_all_selectize( $all_selectize );

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
 *    - string multiple => 'multiple' for multiple select
 *		- no_blank => true to remove the blank choice (forces a selection)
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
			'multiple' => '',
			'no_blank' => false
		),
		$settings
	);
	$output = '<select name="' . esc_attr( $settings['name'] ) .  '" id="' . esc_attr( $settings['id'] ) . '" class="' . esc_attr( implode( ' ', $settings['class'] ) ) . '" ' . $settings['multiple'] . '>';
	if ( !$settings['no_blank'] ) {
		$output .= '<option default value=""></option>';
	}
	foreach ($data as $key => $name) {
		$selected = '';
		if ( in_array( trim( $key ), $settings['selected'] ) ) {
			$selected = 'selected';
		}
		$output .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . stripslashes( wp_filter_nohtml_kses( $name ) ) . '</option>';
	}
	$output .= '</select>';

	return $output;
}

/**
 * Builds scripts for selectize inputs
 *
 * @param 	array 	$all_selectize All selectize inputs to build
 * @return 	string 	Scrips for seletize input
 */
function activities_build_all_selectize( $all_selectize ) {
  $output = '<script>';

	foreach ($all_selectize as $selectize) {
		$output .= 'jQuery("#' . $selectize['name']. '").selectize({';
		$output .=  'persist: false,';
		$output .=  'maxItems: ' . $selectize['max_items'] . ',';
		//$output .=  'closeAfterSelect: true,';
		$output .=  'valueField: "' . $selectize['value'] . '",';
		$output .=  'labelField: "' . $selectize['label'] . '",';
		$output .=  'searchField: ["' . implode( ',', $selectize['search'] ) . '"],';
		$output .=  'options: [';
		$options = array();
		foreach ($selectize['option_values'] as $opt) {
			$options[] = '{' . $selectize['value'] . ': "' . wp_filter_nohtml_kses( $opt[$selectize['value']] ) . '", ' . $selectize['label'] . ': "' . wp_filter_nohtml_kses( $opt[$selectize['label']] ) . '"}';
		}
		$output .= implode( ',', $options);
		$output .=  '],';
		$output .= 	'create: false';
		if ( isset( $selectize['extra'] ) && !empty( $selectize['extra'] ) ) {
			$output .= ',' . implode( ',', $selectize['extra']);
		}
		$output .='});';
	}

  $output .='</script>';

  return $output;
}
