<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Echoes activities options page and handles saving options
 */
function activities_admin_options_page() {
  if ( !current_user_can( ACTIVITIES_ADMINISTER_OPTIONS ) ) {
    wp_die( esc_html__( 'Access Denied', 'activities' ) );
  }

  global $wpdb, $wp_roles;

  $tab = 'general';
  $all_tabs = acts_get_options_tabs();
  if ( isset( $_GET['tab'] ) ) {
    $tab = sanitize_key( $_GET['tab'] );
    if ( !array_key_exists( $tab, $all_tabs ) ) {
      $tab = 'general';
    }
  }

  $permissions = array(
    ACTIVITIES_ACCESS_ACTIVITIES => esc_html__( 'View Activities', 'activities' ),
    ACTIVITIES_ADMINISTER_ACTIVITIES => esc_html__( 'Administer Activities', 'activities' ),
    ACTIVITIES_ADMINISTER_OPTIONS => esc_html__( 'Administer Activities Options', 'activities' ),
  );
  $activity_roles = array(
    ACTIVITIES_CAN_BE_MEMBER_KEY => esc_html__( 'Can be Member', 'activities' ),
    ACTIVITIES_CAN_BE_RESPONSIBLE_KEY => esc_html__( 'Can be Responsible', 'activities' )
  );
  $roles = $wp_roles->get_names();

  if ( isset( $_POST['save_options']) ) {
    if ( activities_options_verify() ) {
      switch ($tab) {
        case 'general':
          foreach (array_keys( $permissions ) as $p_key) {
            foreach (array_keys( $roles ) as $r_key) {
              if ( $r_key === 'administrator' ) {
                continue;
              }
              if ( isset( $_POST[$p_key] ) && !is_array( $_POST[$p_key] ) ) {
                continue;
              }
              if ( isset( $_POST[$p_key][$r_key] ) ) {
                $wp_roles->get_role( $r_key )->add_cap( $p_key );
                if ( $p_key === ACTIVITIES_ADMINISTER_ACTIVITIES && !isset( $_POST[ACTIVITIES_ACCESS_ACTIVITIES][$r_key] ) ) {
                  $wp_roles->get_role( $r_key )->add_cap( ACTIVITIES_ACCESS_ACTIVITIES );
                }
              }
              else {
                $wp_roles->get_role( $r_key )->remove_cap( $p_key );
              }
            }
          }

          $res_per = sanitize_key( $_POST['responsible_permission'] );
          if ( array_key_exists( $res_per, acts_get_responsible_options() ) ) {
            Activities_Options::update_option(
              ACTIVITIES_RESPONSIBLE_KEY,
              $res_per
            );
          }

          foreach ( array_keys( $activity_roles ) as $ar_key ) {
            if ( !is_array( $_POST[$ar_key] ) ) {
              continue;
            }
            $roles_list = array();
            foreach ( array_keys( $roles ) as $r_key ) {
              if ( isset( $_POST[$ar_key][$r_key] ) ) {
                $roles_list[] = $r_key;
              }
            }
            Activities_Options::update_option(
              $ar_key,
              $roles_list
            );
          }

          Activities_Responsible::update_all_users_responsiblity();

          Activities_Options::update_option( ACTIVITIES_DELETE_DATA_KEY, isset( $_POST['delete_data'] ) );

          Activities_Options::update_option( ACTIVITIES_USER_SEARCH_KEY, isset( $_POST['bus'] ) );

          break;

        case 'nice':
          $ns = Activities_Admin_Utility::get_activity_nice_settings();
          unset( $ns['activity_id'] );
          Activities_Options::update_option( ACTIVITIES_NICE_SETTINGS_KEY, $ns );
          break;

        case 'woocommerce':
          Activities_Options::update_option(
            ACTIVITIES_WOOCOMMERCE_CONVERT_KEY,
            isset( $_POST[ACTIVITIES_WOOCOMMERCE_CONVERT_KEY] )
          );
          $coupons_display = array();
          if ( isset( $_POST[ACTIVITIES_NICE_WC_COUPONS_KEY] ) && is_array( $_POST[ACTIVITIES_NICE_WC_COUPONS_KEY] ) ) {
            foreach ($_POST[ACTIVITIES_NICE_WC_COUPONS_KEY] as $coupon => $value) {
              $coupons_display[$coupon] = true;
            }
          }
          Activities_Options::update_option(
            ACTIVITIES_NICE_WC_COUPONS_KEY,
            $coupons_display
          );
          break;
      }

      Activities_Admin::add_success_message( esc_html__( 'Options has been updated.', 'activities' ) );
    }
  }
  elseif ( isset( $_POST['convert_guests'] ) ) {
    Activities_WooCommerce::create_users_from_past_orders();
    return;
  }
  elseif ( isset( $_POST['delete_guests'] ) ) {
    Activities_WooCommerce::flush_created_users();
    return;
  }

  $current_url = ( isset($_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

  global $wpdb;

  echo '<div class="activities-title">';
  echo '<h1>' . esc_html__( 'Activities Options', 'activities' ) . '</h1>';
  echo '</div>';

  echo Activities_Admin::get_messages();

  echo '<nav class="nav-tab-wrapper">';
  foreach ($all_tabs as $tab_key => $tab_display) {
    echo '<a href="' . esc_url( add_query_arg( 'tab', $tab_key, $current_url ) ) . '" class="nav-tab ' . ( $tab === $tab_key ? 'nav-tab-active' : '' ) . '">' . $tab_display . '</a>';
  }
  echo '</nav>';

  echo '<form action="' . esc_url( $current_url ) . '" method="post">';

  switch ($tab) {
    case 'general':
      activities_options_general();
      break;

    case 'nice':
      activities_options_nice();
      break;

    case 'woocommerce':
      activities_options_woocommerce();
      break;

    default:
      activities_options_general();
      break;
  }

  echo '</br>';

  echo wp_nonce_field( 'activities_options', ACTIVITIES_ADMIN_OPTIONS_NONCE, true, false );
  echo '<input type="submit" name="save_options" value="' . esc_html__( 'Save', 'activities' ) . '" class="button button-primary" />';
  echo '</form>';
}

/**
 * Echoes activities options general tab page
 */
function activities_options_general() {
  global $wp_roles;
  $roles = $wp_roles->get_names();

  $permissions = array(
    ACTIVITIES_ACCESS_ACTIVITIES => esc_html__( 'View Activities', 'activities' ),
    ACTIVITIES_ADMINISTER_ACTIVITIES => esc_html__( 'Administer Activities', 'activities' ),
    ACTIVITIES_ADMINISTER_OPTIONS => esc_html__( 'Administer Activities Options', 'activities' )
  );
  $activity_roles = array(
    ACTIVITIES_CAN_BE_MEMBER_KEY => esc_html__( 'Can be Member', 'activities' ),
    ACTIVITIES_CAN_BE_RESPONSIBLE_KEY => esc_html__( 'Can be Responsible', 'activities' )
  );

  echo '<div id="activities-options">';

  echo '<div>';
  echo '<h2>' . esc_html__( 'Permissions', 'activities' ) . '</h2>';
  echo '<table class="activities-table">';

  echo '<thead><tr>';
  echo '<td>' . esc_html__( 'Roles', 'activities' ) . '</td>';
  foreach ($permissions as $display) {
    echo '<td class="activities-table-d">' . esc_html( $display ) . '</td>';
  }
  echo '</tr></thead>';

  echo '<tbody>';
  foreach ($roles as $r_key => $r_name) {
    echo '<tr>';
    echo '<td>' . translate_user_role( $r_name ) . '</td>';
    foreach (array_keys( $permissions ) as $p_key) {
      $checked = 'unchecked';
      if ( $r_key === 'administrator' || $wp_roles->get_role($r_key)->has_cap($p_key) ) {
        $checked = 'checked';
      }
      echo '<td class="activities-table-d"><input type="checkbox" name="' . esc_attr( $p_key . '[' . $r_key . ']' ) . '" ' . $checked . ' ' . ($r_key === 'administrator' ? 'disabled' : '') . ' /></td>';
    }
    echo '</tr>';
  }
  echo '</tbody>';

  echo '</table>';


  echo '<h3>' . esc_html__( 'Responsible Users', 'activities' ) . '</h3>';
  echo '<label for="responsible_permission">' . esc_html__( 'Allow responsible users to:', 'activities' ) . '</label></br>';
  echo '<select name="responsible_permission">';
  $res_per = Activities_Options::get_option( ACTIVITIES_RESPONSIBLE_KEY );
  foreach (acts_get_responsible_options() as $option_key => $option_display) {
    echo '<option value="' . $option_key . '" ' . ($res_per === $option_key ? 'selected' : '') . '>' . $option_display . '</option>';
  }
  echo '</select>';
  echo '<p class="acts-grey">' . esc_html__( "This will be in addtion to role permissions.\nAn admin will always be able to view and edit their assigned activities,\nwhatever the responsible permission setting is set to.", 'activities' ) . '</p>';
  echo '</div>';

  $act_roles_options = array(
    ACTIVITIES_CAN_BE_MEMBER_KEY => Activities_Options::get_option( ACTIVITIES_CAN_BE_MEMBER_KEY ),
    ACTIVITIES_CAN_BE_RESPONSIBLE_KEY => Activities_Options::get_option( ACTIVITIES_CAN_BE_RESPONSIBLE_KEY )
  );

  echo '<div>';
  echo '<h2>' . esc_html__( 'Activity Roles', 'activities' ) . '</h2>';
  echo '<table class="activities-table">';

  echo '<thead><tr>';
  echo '<td>' . esc_html__( 'Roles', 'activities' ) . '</td>';
  foreach ($activity_roles as $display) {
    echo '<td class="activities-table-d">' . esc_html( $display ) . '</td>';
  }
  echo '</tr></thead>';

  echo '<tbody>';
  foreach ($roles as $r_key => $r_name) {
    echo '<tr>';
    echo '<td>' . translate_user_role( $r_name ) . '</td>';
    foreach (array_keys( $activity_roles ) as $ar_key) {
      $checked = 'unchecked';
      if ( in_array( $r_key, $act_roles_options[$ar_key] ) ) {
        $checked = 'checked';
      }
      echo '<td class="activities-table-d"><input type="checkbox" name="' . esc_attr( $ar_key . '[' . $r_key . ']' ) . '" ' . $checked . ' /></td>';
    }
    echo '</tr>';
  }
  echo '</tbody>';

  echo '</table>';
  echo '</div>';

  echo '<div>';
  echo '<h2>' . esc_html__( 'Activities Deletion', 'activities' ) . '</h2>';
  echo '<label for="activities_delete_cb"><b>' . esc_html__( 'Delete plugin data on uninstall', 'activities' ) . '</b></label> ';
  echo '<input type="checkbox" id="activities_delete_cb" name="delete_data" ' . ( Activities_Options::get_option( ACTIVITIES_DELETE_DATA_KEY ) ? 'checked' : '' ) . ' /></br>';
  echo '<p class="acts-grey">' . esc_html__( 'Makes the plugin delete all off it\'s data on uninstall, only the current blog.', 'activities' );
  if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    echo '</br>' . sprintf( esc_html__( 'With the exception of user created from %s guest customers.', 'activities' ), 'WooCommerce' ) . '</br>' . sprintf( esc_html__( 'You can delete those with the button in the %s tab.', 'activities' ), 'WooCommerce' );
  }
  echo '</p>';
  echo '</div>';

  echo '<div>';
  echo '<h2>' . esc_html__( 'Better user search', 'activities' ) . '</h2>';
  echo '<label for="acts_bus"><b>' . esc_html__( 'Improve user search', 'activities' ) . '</b></label> ';
  echo '<input type="checkbox" id="acts_bus" name="bus" ' . ( Activities_Options::get_option( ACTIVITIES_USER_SEARCH_KEY ) ? 'checked' : '' ) . ' /></br>';
  echo '<p class="acts-grey">' . esc_html__( "Allows you to search users by their first name and last name.\nMight make searching slower.", 'activities' );
  echo '</p>';
  echo '</div>';

  echo '</div>'; //activities-options
}

/**
 * Echoes activities options activity nice (report) tab page
 */
function activities_options_nice() {
  echo '<div>';
  echo '<h2>' . esc_html__( 'Activity Report Default Settings', 'activities' ) . '</h2>';
  $test_activity = array(
    'activity_id' => -1,
    'name' => esc_html__( 'Activity name', 'activities' ),
    'start' => '1970-01-01',
    'end' => date( 'Y-m-d' ),
    'short_desc' => esc_html__( '(short description)
    This is a example activity', 'activities' ),
    'long_desc' => esc_html__( '(long description)
    If a user does not some data, like an address, it will not be shown.
    In this general example, all data is shown where it would be with real users.', 'activities' ),
    'responsible_id' => -1,
    'location_id' => -1,
    'members' => array( -1, -2, -3, -4, -5 )
  );
  echo acts_activity_nice_management( $test_activity );

  echo '</div>'; //activity nice settings
}

/**
 * Echoes activities options WooCommerce tab page
 */
function activities_options_woocommerce() {
  echo '<div id="activities-options">';

  echo '<div>';
  echo '<h2>' . esc_html__( 'WooCommerce Guest Customers', 'activities' ) . '</h2>';
  $checked = Activities_Options::get_option( ACTIVITIES_WOOCOMMERCE_CONVERT_KEY ) ? 'checked' : '';
  echo '<b>' . esc_html__( 'Enable Guest Coversion', 'activities' ) . '</b> <input type="checkbox" name="' . ACTIVITIES_WOOCOMMERCE_CONVERT_KEY . '" ' . $checked . ' /></br>';
  echo '<i class="acts-grey">' . esc_html__( "Enables automatic conversion of guest customers to users.\nThis allows guests to be added to activities.", 'activities' ) . '</i></br></br>';
  echo '</div>';

  $coupons = get_posts(
    array(
      'post_type' => 'shop_coupon',
      'numberposts' => -1
    )
  );

  $coupons_display = Activities_Options::get_option( ACTIVITIES_NICE_WC_COUPONS_KEY );

  echo '<div>';
  echo '<h2>' . esc_html__( 'Coupons on activities report', 'activities' ) . '</h2>';
  echo '<table class="activities-table">';
  echo '<thead>';
  echo '<tr>';
  echo '<td>' . esc_html__( 'Coupons', 'activities' ) . '</td><td>' . esc_html__( 'Display on activity report', 'activities' ) . '</td>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';
  foreach ($coupons as $coupon) {
    echo '<tr>';
    $checked = isset( $coupons_display[$coupon->post_title] ) ? 'checked' : '';
    echo '<td>' . stripslashes( wp_filter_nohtml_kses( ucfirst( $coupon->post_title ) ) ) . '</td><td class="activities-table-d"><input type="checkbox" name="' . esc_attr( ACTIVITIES_NICE_WC_COUPONS_KEY . '[' . $coupon->post_title ) . ']" ' . $checked . ' /></td>';
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '</div>';

  echo '<div>';
  echo '<h2>' . esc_html__( 'Actions', 'activities' ) . '</h2>';
  echo '<input type="submit" id="activities-convert-guests" name="convert_guests" class="button" value="' . esc_html__( 'Convert Guests Customers', 'activities' ) . '" /> ';
  echo '<div class="acts-nice-loader" style="display: none"></div></br>';
  echo '<p id="activities-options-convert-text"></p>';
  echo '<input type="submit" id="activities-delete-guests" name="delete_guests" class="button" value="' . esc_html__( 'Delete All Converted Users', 'activities' ) . '" /> ';
  echo '<div class="acts-nice-loader" style="display: none"></div></br>';
  echo '<p id="activities-options-delete-text"></p>';
  echo '</div>';

  echo '</div>'; //activities-options
}

/**
 * Verifies options nonce
 *
 * @return bool True if its verified, false if not
 */
function activities_options_verify() {
  if ( isset( $_POST[ACTIVITIES_ADMIN_OPTIONS_NONCE] ) ) {
    return wp_verify_nonce( $_POST[ACTIVITIES_ADMIN_OPTIONS_NONCE], 'activities_options' );
  }
  else {
    Activities_Admin::add_error_message( esc_html__( 'Could not verify options.', 'activities' ) );
    return false;
  }
}

/**
 * Gets all the tabs for the options page
 *
 * @return array
 */
function acts_get_options_tabs() {
  $tabs = array(
    'general' => esc_html__( 'General', 'activities' ),
    'nice' => esc_html__( 'Activity Report', 'activities' )
  );

  if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    $tabs['woocommerce'] = 'WooCommerce';
  }

  return $tabs;
}

/**
 * Get all responsible options
 *
 * @return array
 */
function acts_get_responsible_options() {
  return array(
    ACTIVITIES_RESPONSIBLE_SAME => esc_html__( 'Same as their role permissions', 'activities' ),
    ACTIVITIES_RESPONSIBLE_VIEW => esc_html__( 'View assigned activities', 'activities' ),
    ACTIVITIES_RESPONSIBLE_EDIT => esc_html__( 'View and edit assigned activities', 'activities' )
  );
}
