<?php

if ( !defined('WPINC') ) {
  die;
}

/**
 * Builds the page for activity nice management
 *
 * @param activity Information about the activity to display
 * @param current_url The current admin area to return to, null to remove settings buttons
 * @return string Management page for nice activity display
 */
function acts_activity_nice_management( $activity, $current_url = null ) {
	global $wpdb;

	wp_enqueue_media();

	if ( isset( $_POST['save_nice_settings'] ) && $current_url != null ) {
    $settings = Activities_Admin_Utility::get_activity_nice_settings();
    if ( isset( $settings['activity_id'] ) ) {
  		Activities_Activity::save_nice_settings( $settings );
  		Activities_Admin::add_success_message( sprintf( esc_html__( 'Report settings updated for %s.', 'activities' ), $activity['name'] ) );
    }
    else {
      Activities_Admin::add_success_message( sprintf( esc_html__( 'An error occured during saving report setting for %s.', 'activities' ), $activity['name'] ) );
    }
	}
	else if ( isset( $_POST['reset_nice_settings'] ) && isset( $_POST['item_id'] ) ) {
    $id = acts_validate_id( $_POST['item_id'] );
    if ( $id ) {
  		Activities_Activity::delete_meta( $id, ACTIVITIES_NICE_SETTINGS_KEY );

  		Activities_Admin::add_success_message( sprintf( esc_html__( 'Report settings has been reset for %s.', 'activities' ), $activity['name'] ) );
    }
    else {
      Activities_Admin::add_success_message( sprintf( esc_html__( 'An error occured during resetting report setting for %s.', 'activities' ), $activity['name'] ) );
    }
	}

	$nice_settings = Activities_Activity::get_nice_settings( $activity['activity_id'] );
	$default = false;
	if ( !$nice_settings ) {
		$default = true;
		$nice_settings = Activities_Options::get_option( ACTIVITIES_NICE_SETTINGS_KEY );
		if ( !is_array( $nice_settings ) ) {
			$nice_settings = unserialize( $nice_settings );
		}
	}

  $output = '';

	if ( $current_url != null ) {
    $output .= '<h1 id="activities-title">' . esc_html__( 'Activity Report Management', 'activities' ) . '</h1>';

		$output .= Activities_Admin::get_messages();
	}

	$output .= '<h2 id="acts-nice-preview-title">' . esc_html__( 'Report', 'activities' ) . ':</h2>';
	$output .= '<div id="acts-nice-preview">';

	$output .= acts_activity_nice_page( $activity, $nice_settings );

	$output .= '</div> ';

	$output .= '<div id="acts-nice-settings" class="activities-box-wrap activities-box-padding">';

  $selectize = array();
  if ( $current_url != null ) {
    $output .= '<div id="acts-nice-quick-wrap">';
    $output .= '<h3>' . esc_html__( 'Activity', 'activities' ) . '</h3>';
    $output .= '<input type="text" id="acts_nice_quick_change" value="' . esc_attr( $activity['activity_id'] ) . '" />';
    $where = 'WHERE archive = %d';
    $values = array( $activity['archive'] );
    if ( $activity['archive'] == 0 && Activities_Responsible::current_user_restricted_view() ) {
      $where .= ' AND responsible_id = %d';
      $values[] = get_current_user_id();
    }
    $table_name = Activities::get_table_name( 'activity' );
    $acts = $wpdb->get_results( $wpdb->prepare(
      "SELECT activity_id, name
      FROM $table_name
      $where
      ",
      $values
    ),
      ARRAY_A
    );
    $extra = array( 'onChange : function() {
        var url = "' . esc_url( $current_url ) . '";
        var id = jQuery("#acts_nice_quick_change").val();
        if ( id != "" ) {
          url += "&action=view&item_id=" + id;
          window.location.assign(url);
        }
      }' );
    $selectize[] = array(
      'name' => 'acts_nice_quick_change',
      'value' => 'activity_id',
      'label' => 'name',
      'search' => array( 'name' ),
      'option_values' => $acts,
      'max_items' => '1',
      'extra' => $extra
    );
    $output .= '</div>';
  }

	$output .= '<h3>' . esc_html__( 'Settings', 'activities' );
	if ( $current_url != null ) {
		$output .= '<i <span class="acts-grey"> (' . ($default ? esc_html__( 'default', 'activities' ) : esc_html__( 'custom', 'activities' ) ) . ')</i>';
	}
	$output .= '</h3>';

	if ( $current_url != null ) {
		$output .= '<form id="acts-nice-settings-form" method="post" enctype="multipart/form-data">';
	}
  else {
    $output .= '<div id="acts-nice-settings-form">';
  }

  $output .= '<b>' . esc_html__( 'Header', 'activities' ) . '</b></br>';
  $output .= '<input type="text" name="header" value="' . esc_attr( stripslashes( $nice_settings['header'] ) ) . '" />';
	$output .= '<div id="acts-nice-logo-setting"><b>' . esc_html__( 'Logo', 'activities' ) . '</b>';
	$output .= '<input id="acts_upload_nice_logo" type="submit" class="button" value="' . esc_html__( 'Select Logo', 'activities' ) . '" /> &emsp;';
	$output .= '<input id="acts_remove_nice_logo" type="submit" class="button" value="' . esc_html__( 'Remove Logo', 'activities' ) . '" /></div>';
	$output .= '<input type="hidden" name="acts_nice_logo_id" id="acts_nice_logo_id" value="' . esc_attr( $nice_settings['logo'] ) . '" />';

	$output .= '<div class="acts-nice-splitter">';
	$output .= '<table>';
	$output .= '<thead>';
	$output .= '<tr><td><b>' . esc_html__( 'Activity Info', 'activities' ) . '</b></td><td></td></tr>';
	$output .= '</thead>';
	$output .= '<tbody>';
	$output .= '<tr><td>' . esc_html__( 'Start', 'activities' ) . '&#8193;</td><td><input type="checkbox" name="start" id="start" ' . ( $nice_settings['start'] ? 'checked' : '') . ' /></td></tr>';
	$output .= '<tr><td>' . esc_html__( 'End', 'activities' ) . '&#8193;</td><td><input type="checkbox" name="end" id="end" ' . ( $nice_settings['end'] ? 'checked' : '') . ' /></td></tr>';
	$output .= '<tr><td>' . esc_html__( 'Short Description', 'activities' ) . '&#8193;</td><td><input type="checkbox" name="short_desc" id="short-desc" ' . ( $nice_settings['short_desc'] ? 'checked' : '') . '/></td></tr>';
	$output .= '<tr><td>' . esc_html__( 'Location Address', 'activities' ) . '&#8193;</td><td><input type="checkbox" name="location" id="location" ' . ( $nice_settings['location'] ? 'checked' : '') . '/></td></tr>';
	$output .= '<tr><td>' . esc_html__( 'Responsible User', 'activities' ) . '&#8193;</td><td><input type="checkbox" name="responsible" id="responsible" ' . ( $nice_settings['responsible'] ? 'checked' : '') . '/></td></tr>';
	$output .= '<tr><td>' . esc_html__( 'Long Description', 'activities' ) . '&#8193;</td><td><input type="checkbox" name="long_desc" id="long-desc" ' . ( $nice_settings['long_desc'] ? 'checked' : '') . '/></td></tr>';
	$output .= '</tbody>';
	$output .= '</table>';
  $output .= '<div><label for="timeslots"><b>' . esc_html__( 'Sessions', 'activities' ) . '</b> <span id="time-slots-max">(max: 50)</span></label></br><input type="number" name="time_slots" id="time-slots" value="' . esc_attr( $nice_settings['time_slots'] ) . '" min="0" max="50" /></div>';
	$output .= '</div>';

	$output .= '<div id="acts-nice-members-setting" class="acts-nice-splitter">';

	$output .= '<h3>' . esc_html__( 'Participant Info', 'activities' ) . ' <div class="acts-nice-loader-wrap"><div class="acts-nice-loader"></div> ';
  $output .= '<input type="submit" id="acts-reload-members" value="' . esc_html__( 'Reload Info', 'activities' ) . '" class="button" /></div></h3>';
	$output .= '<table>';
	$output .= '<thead>';
	$output .= '<tr><td><b>' . esc_html__( 'Prepared Setups', 'activities' ) . '</b></td><td></td></tr>';
	$output .= '</thead>';
	$output .= '<tbody>';
	$output .= '<tr><td>' . esc_html__( 'Wordpress User Info', 'activities' ) . '&#8193;</td><td><input type="radio" id="use_wp_info" name="member_info" value="wp" ' . ($nice_settings['member_info'] === 'wp' ? 'checked' : '') . ' /></td></tr>';
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$output .= '<tr><td>' . esc_html__( 'Woocommerce Billing Info', 'activities' ) . '&#8193;</td><td><input type="radio" id="use_wc_bill_info" name="member_info" value="bill" ' . ($nice_settings['member_info'] === 'bill' ? 'checked' : '') . ' /></td></tr>';
		$output .= '<tr><td>' . esc_html__( 'Woocommerce Shipping Info', 'activities' ) . '&#8193;</td><td><input type="radio" id="use_wc_ship_info" name="member_info" value="ship" ' . ($nice_settings['member_info'] === 'ship' ? 'checked' : '') . ' /></td></tr>';
	}
	$output .= '</tbody>';
	$output .= '</table>';
	$output .= '<ul id="acts-nice-custom">';
	$output .= '<li><b>' . esc_html__( 'Custom Fields', 'activities' )  . '</b> <i class="acts-grey">(advanced)</i> <input type="submit" id="add-custom" value="+" class="button" /></li>';
	$output .= '<li><i class="acts-grey">' . esc_html__( 'Type in usermeta key, then press reload info.', 'activities' )  . '</i></li>';
  $output .= '<li><i class="acts-grey">' . esc_html__( 'Seperate multiple fields in one line by a comma.', 'activities' )  . '</i></li>';
  if ( isset($nice_settings['custom']) ) {
    foreach ($nice_settings['custom'] as $custom) {
  		$output .= '<li><input type="text" name="nice_custom[]" value="' . esc_attr( $custom['name'] ) . '" />';
  		$output .= '<select name="nice_custom_col[]">';
  		$selected_one = $custom['col'] === 1 ? 'selected' : '';
  		$selected_two = $custom['col'] === 2 ? 'selected' : '';
  		$output .= '<option value="1" ' . $selected_one . '>Column 1</option>';
  		$output .= '<option value="2" ' . $selected_two . '>Column 2</option>';
  		$output .= '</select>';
  		$output .= ' <input type="submit" name="delete_custom" value="-" class="delete-custom button" /></li>';
  	}
  }
	$output .= '</ul>';
  $output .= '<ul id="acts-nice-color">';
  $output .= '<li><b>' . esc_html__( 'Color Fields', 'activities' )  . '</b> <i class="acts-grey">(advanced)</i> <input type="submit" id="add-color" value="+" class="button" /></li>';
  $output .= '<li><i class="acts-grey">' . esc_html__( 'Colorize usermeta data for quicker identification.', 'activities' )  . '</i></li>';
  if ( isset($nice_settings['color']) ) {
    foreach ($nice_settings['color'] as $key => $color) {
      $output .= '<li><input type="text" value="' . esc_attr( $color ) . '" name="nice_color[]" />';
      $output .= '<input type="text" name="nice_color_key[]" value="' . esc_attr( $key ) . '" />';
      $output .= ' <input type="submit" name="delete_color" value="-" class="delete-color button" />';
      $output .= '</li>';
    }
  }
  $output .= '</ul>';
	$output .= '</div>';
	if ( $current_url != null ) {
		$output .= '<div id="acts-nice-buttons" class="acts-nice-splitter">';
		$output .= '<input type="submit" name="save_nice_settings" class="button button-primary" value="' . esc_html__( 'Save', 'activities' ) . '" /> ';
		//$output .= '<input type="submit" name="download" class="button" value="Download PDF"/> ';
		$output .= '<a href="javascript:window.print()" class="button">' . esc_html__( 'Print', 'activities' ) . '</a> ';
    $output .= '<input id="folder_print" type="button" class="button" value="' . esc_html__( 'Folder Print', 'activities' ) . '" /> ';
		$output .= '<input type="hidden" value="' . esc_attr( acts_validate_id( $_GET['item_id'] ) ) . '" id="item-id" name="item_id" />';
    $output .= wp_nonce_field( 'activities_nice', ACTIVITIES_ADMIN_NICE_NONCE, true, false );
		$output .= '<a href="' . esc_url( $current_url ) . '" class="button">' . esc_html__( 'Return', 'activities' ) . '</a></br></br>';
		$output .= '<input type="submit" class="button right" name="reset_nice_settings" value="' . esc_html__( 'Reset to default', 'activities' ) . '" />';
		$output .= '</div>';
	}
	else {
		$output .= '<input type="hidden" value="' . esc_attr( $activity['activity_id'] ) . '" id="item-id" name="item_id" />';
	}
	if ( $current_url != null ) {
		$output .= '</form>';
	}
  else {
    $output .= '</div>';
  }
	$output .= '</div>';

  $meta_fields = $wpdb->get_col(
    "SELECT DISTINCT meta_key
    FROM $wpdb->usermeta"
  );

  foreach ($meta_fields as $key => $meta) {
    if ( activities_nice_filter_custom_field( $meta ) ) {
      unset( $meta_fields[$key] );
    }
    else {
      $meta_fields[$key] = '"' . wp_filter_nohtml_kses( $meta ) . '"';
    }
  }
  $custom_wl_display = '<script>';
  $custom_wl_display .= 'var meta_whitelist = new Set([' . implode( ', ', $meta_fields ) . ']);';
  $custom_wl_display .= '</script>';

  $output .= $custom_wl_display;

  $output .= activities_build_all_selectize( $selectize );

	return $output;
}

/**
 * Generate the printable version of and activity
 *
 * @param activity Information about the activity to display
 * @param nice_settings Display settings for the activity
 * @return string Printable page
 */
function acts_activity_nice_page( $activity, $nice_settings ) {
	global $wpdb;

	if ( $activity['location_id'] != -1 ) {
		$location_table = Activities::get_table_name( 'location' );
		$location = $wpdb->get_row( $wpdb->prepare(
				"SELECT *
				FROM $location_table
				WHERE location_id = %d
				",
				$activity['location_id']
			),
			ARRAY_A
		);
	}
	else {
		$location = array( 'address' => 'location address' );
	}

  $timeslots = '';
  for ($time=0; $time < $nice_settings['time_slots']; $time++) {
    $timeslots .= '<input type="checkbox" name="time' . ($time + 1) . '">';
  }

	$output =	'<div id="acts-nice-wrap">';
  $output .= '<div id="acts-nice-header">';
  $output .= esc_html( do_shortcode( stripslashes( $nice_settings['header'] ) ) );
  $output .= '</div>';

	$output .= '<div id="acts-nice-info">';
	$output .= '<img src="' . wp_get_attachment_url( $nice_settings['logo'] ) . '" alt="" id="acts-nice-logo" />';

	$output .= '<b>' . esc_html__( 'Activity Participants List', 'activities' ) . '</b>';
	$output .= '<h1>' . stripslashes( wp_filter_nohtml_kses ( $activity['name'] ) ) . '</h1>';

	$output .= '<div>';
	$output .= '<span id="acts-nice-start" style="display: ' . ($nice_settings['start'] ? 'inline' : 'none' ) . ';">';
	$output .= '<b>' . esc_html__( 'Start', 'activities' ) . ': </b>' . wp_filter_nohtml_kses( Activities_Utility::format_date( $activity['start'] ) ) . '</span> ';
	if ( $nice_settings['start'] ) {
		$output .= '<span id="acts_nice_start_spacing">&emsp;</span>';
	}
	$output .= '<span id="acts-nice-end" style="display: ' . ($nice_settings['end'] ? 'inline' : 'none' ) . ';">';
	$output .= '<b>' . esc_html__( 'End', 'activities' ) . ': </b>' . wp_filter_nohtml_kses( Activities_Utility::format_date( $activity['end'] ) ) . '</span>';
	$output .= '</div>';

	$output .= '<p id="acts-nice-short-desc" style="display: ' . ($nice_settings['short_desc'] ? 'block' : 'none' ) . ';">';
	$output .= stripslashes( wp_filter_nohtml_kses ( $activity['short_desc'] ) ) . '</p>';

	$output .= '<div>';
	if ( !is_null( $location ) ) {
		$output .= '<span id="acts-nice-location" style="display: ' . ($nice_settings['location'] ? 'inline' : 'none' ) . ';">';
    $address = $location['address'];
    if ( $address == '' ) {
      $address = $location['name'];
    }
		$output .= '<b>' . esc_html__( 'Location', 'activities' ) . ': </b>' . stripslashes( wp_filter_nohtml_kses ( $address ) ) . '</span>';
    $output .= '<span id="acts_nice_location_spacing"> &bull; </span>';
	}
	if ( $activity['responsible_id'] != -1 ) {
		$responsible = get_user_by( 'ID', $activity['responsible_id'] );
		if ( $responsible !== false ) {
			$responsible_name = Activities_Utility::get_user_name( $responsible, false );
		}
    else {
      $responsible_name = '--';
    }
	}
	else {
		$responsible_name = 'responsible name';
	}
	if ( isset( $responsible_name) ) {
		$output .= '<span id="acts-nice-responsible" style="display: ' . ($nice_settings['responsible'] ? 'inline' : 'none' ) . ';">';
		$output .= '<b>' . esc_html__( 'Responsible', 'activities' ) . ': </b>' . stripslashes( wp_filter_nohtml_kses ( $responsible_name ) ) . '</span>';
	}
	$output .= '</div>';

	$output .= '<p id="acts-nice-long-desc" style="display: ' . ($nice_settings['long_desc'] ? 'block' : 'none' ) . ';">';
	$output .=  stripslashes( wp_filter_nohtml_kses ( $activity['long_desc'] ) ) . '</p>';

	$output .= '</div>';

	$output .= '<div id="acts-nice-members">';
	$member_c = count( $activity['members'] );
	if ( $member_c > 0 ) {
		$output .= '<div class="acts-nice-members-row">';

		$output .= '<div class="acts-nice-members-head">';
		if ( $member_c == 1 ) {
			$output .= '<b>' . esc_html__( 'Participant', 'activities' ) . '</b>'; ;
		}
		else {
			$output .= '<b>' . esc_html__( 'Participants', 'activities' ) . '</b> (' . $member_c . ')';
		}
		$output .= '</div>';

		$output .= '<div class="acts-nice-members-head"><b>' . esc_html__( 'Additional Info', 'activities' ) . '</b></div>';
		$output .= '<div class="acts-nice-members-head-time"><b>' . esc_html__( 'Sessions', 'activities' ) . '</b></div>';
		$output .= '</div>';

    $coupons_display = array();
    $coupons_selected = Activities_Options::get_option( ACTIVITIES_NICE_WC_COUPONS_KEY );
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && !empty( $coupons_selected ) && $activity['activity_id'] > 0 && $activity['archive'] == 0 ) {
        $coupons_display = Activities_WooCommerce::get_activity_orders( $activity['activity_id'], $activity['members'] );
    }

		foreach (acts_get_member_info( $activity['members'], $nice_settings['member_info'], $nice_settings['custom'], true ) as $id => $user) {
			$output .= '<div class="acts-nice-members-row">';

			$output .= '<div class="acts-nice-members-info"><ul id="col1-id' . esc_attr( $id ) . '"';
      if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        if ( $id > 0 ) {
          $coupon_list = array();
          if ( isset( $coupons_display[$id] ) ) {
            foreach ($coupons_display[$id] as $order_id) {
              $order = wc_get_order($order_id);
              if ( !empty( $order ) ) {
                foreach ($order->get_used_coupons() as $code) {
                  if ( isset( $coupons_selected[$code] ) && !isset( $coupon_list[$code] )  ) {
                    $coupon_list[$code] = $code;
                  }
                }
              }
            }
          }
        }
        else {
          $coupon_list = array_keys( $coupons_selected );
        }
        if ( !empty( $coupon_list ) ) {
          $output .= ' class="acts-nice-compressed-info"';
        }
      }
      $output .= '>';
			$output .= $user['col1'];
      $output .= '</ul>';

      if ( isset( $coupon_list ) && !empty( $coupon_list ) ) {
        $echo_list = '<ul class="acts-nice-coupons">';
        foreach ($coupon_list as $code) {
          $echo_list .= '<li>' . stripslashes( wp_filter_nohtml_kses( ucfirst( $code ) ) ) . '</li>';
        }
        $echo_list .= '</ul>';
        $output .= $echo_list;
      }

			$output .= '</div>';

			$output .= '<div class="acts-nice-members-info"><ul id="col2-id' . esc_attr( $id )  . '">';
			$output .= $user['col2'];
			$output .= '</ul></div>';

			$output .= '<div class="acts-nice-members-time">';
      $output .= $timeslots;
			$output .= '</div>';

			$output .= '</div>';
		}
	}
	else {
		$output .= '<p>' . esc_html__( 'This activity has no participants.', 'activities' ) . '</p>';
	}
	$output .= '</div>';

	$output .= '</div>';

	return $output;
}

/**
 * Gets real names by user ids
 *
 * @param   array   $user_ids Ids of users
 * @return  array   A mapping of user ids to their real names
 */
function acts_get_member_names( $user_ids ) {
  $names = array();
  foreach ($user_ids as $id) {
    if ( $id > 0 ) {
      $user = get_user_by( 'ID', $id );
      if ( $user === false ) {
        continue;
      }
      else {
        $name = Activities_Utility::get_user_name( $user, false );
      }
      $names[$id] = $name;
    }
  }
  return $names;
}

/**
 * Generate the user info to display in activity nice
 *
 * @param   array   $user_ids Ids of users to display
 * @param   string  $type The type of predefined display options
 * @param   array   $custom_fields List of custom fields to display for users, 'none' if there are no custom fields
 * @return  array   A list of user info to display in coloumn 1 ('col1') and column 2 ('col2')
 */
function acts_get_member_info( $user_ids, $type, $custom_fields = 'none', $sort = false ) {
  $member_info = array();
  $sort_members = acts_get_member_names( $user_ids );
  if ( empty( $sort_members ) ) {
    foreach ($user_ids as $id) {
      $col1 = '<li class="acts-nice-member-name"><b>first_name last_name</b></li>';
      $col2 = '<li class="acts-nice-member-name">' . 'user_email' . '</li>';

      switch ($type) {
        case 'wp':
          break;

        case 'bill':
        case 'ship':
          $prefix = $type == 'bill' ? 'billing' : 'shipping';
          $col1 .= '<li>' . $prefix . '_address_1</li>';
          $col1 .= '<li>' . $prefix . '_address_2</li>';
          $col1 .= '<li>' . $prefix . '_postcode ' . $prefix . '_city</li>';
          $col2 .= '<li>billing_phone</li>';
          break;
      }

      if ( $custom_fields !== 'none' ) {
        foreach ($custom_fields as $custom) {
          $str = '<li>';
          foreach (explode( ',', $custom['name'] ) as $c) {
            $c = trim($c);
            if ( activities_nice_filter_custom_field( $c ) ) {
              continue;
            }
            $str .= '<span class="acts-nice-custom-' . esc_attr( $c ) . '">' . stripslashes( wp_filter_nohtml_kses ( $c ) ) . ' ' . '</span>';
          }
          $str .= '</li>';

          if ( $custom['col'] == 1 ) {
            $col1 .= $str;
          }
          else if ( $custom['col'] == 2 ) {
            $col2 .= $str;
          }
        }
      }

      $member_info[$id]['col1'] = $col1;
      $member_info[$id]['col2'] = $col2;
    }
    return $member_info;
  }

  if ( $sort ) {
    if ( !asort( $sort_members, SORT_STRING ) ) {
      $sort_members = acts_get_member_names( $user_ids );
    }
  }

  foreach ($sort_members as $id => $name) {
    $user = get_user_by( 'ID', $id );

    $col1 = '<li class="acts-nice-member-name"><a href="' . get_edit_user_link( $id ) . '" target="_blank" ><b>' . stripslashes( wp_filter_nohtml_kses( $name ) ) . '</b></a></li>';
    $col2 = '<li class="acts-nice-member-name">' . stripslashes( wp_filter_nohtml_kses( $user->get( 'user_email' ) ) ) . '</li>';

    switch ($type) {
      case 'wp':
        break;

      case 'bill':
      case 'ship':
        $prefix = $type == 'bill' ? 'billing' : 'shipping';
        $address1 = $user->__get( $prefix . '_address_1' );
        if ( $address1 != '' ) {
          $col1 .= '<li>' . stripslashes( wp_filter_nohtml_kses( $address1 ) ) . '</li>';
        }
        $address2 = $user->__get( $prefix . '_address_2' );
        if ( $address2 != '' ) {
          $col1 .= '<li>' . stripslashes( wp_filter_nohtml_kses( $address2 ) ) . '</li>';
        }
        $city = $user->__get( $prefix . '_city' );
        $postcode = $user->__get( $prefix . '_postcode' );
        if ( $postcode || $city ) {
          $col1 .= '<li>';
          if ( $postcode) {
            $col1 .= stripslashes( wp_filter_nohtml_kses( $postcode ) ) . ' ';
          }
          if ( $city ) {
            $col1 .= stripslashes( wp_filter_nohtml_kses( $city ) );
          }
          $col1 .= '</li>';
        }
        $phone = $user->__get( 'billing_phone' );
        if ( $phone != '' ) {
          $col2 .= '<li>' . stripslashes( wp_filter_nohtml_kses( $phone ) ) . '</li>';
        }
        break;
    }

    if ( $custom_fields !== 'none' ) {
      foreach ($custom_fields as $custom) {
        $c_values = array();
        foreach (explode( ',', $custom['name'] ) as $c) {
          $c = trim($c);
          if ( activities_nice_filter_custom_field( $c ) ) {
            continue;
          }
          $value = $user->__get( $c );
          if ( $value != '' ) {
            $c_values[] = '<span class="acts-nice-custom-' . esc_attr( $c ) . '">' . stripslashes( wp_filter_nohtml_kses( $value ) ) . '</span>';
          }
        }

        if ( count( $c_values ) === 0 ) {
          continue;
        }

        $str = '<li>' . implode( ' ', $c_values ) . '</li>';

        if ( $custom['col'] == 1 ) {
          $col1 .= $str;
        }
        else if ( $custom['col'] == 2 ) {
          $col2 .= $str;
        }
      }
    }

    $member_info[$id]['col1'] = $col1;
    $member_info[$id]['col2'] = $col2;
  }

  return $member_info;
}

/**
 * Filters custom fields for user_meta in activity nice
 *
 * @param   string  $field Name of field
 * @return  boolean true if the field must be hidden/is protected, false if it can be shown (not protected)
 */
function activities_nice_filter_custom_field( $field ) {
  $filter_data = array(
    'user_pass',
    'user_activation_key',
    'session_tokens'
  );
  if ( in_array( $field, $filter_data ) ) {
    return true;
  }
  else {
    return is_protected_meta( $field );
  }
}
