<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * A collection on functions used in admin context
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Admin_Utility {
  /**
   * Get nice setting from options and post values
   *
   * @return array Nice settings
   */
  static function get_activity_nice_settings() {
    global $wpdb;

    $nice_settings = Activities_Options::get_option( ACTIVITIES_NICE_SETTINGS_KEY );
    if ( !is_array( $nice_settings ) ) {
      $nice_settings = unserialize( $nice_settings );
    }

    if ( ( isset( $_POST['save_options']) || isset( $_POST['save_nice_settings'] ) || isset( $_POST['default_nice_settings'] ) ) && isset( $_POST['item_id'] ) ) {
      //The options page uses its own nonce
      if ( ( isset( $_POST['save_nice_settings'] ) || isset( $_POST['default_nice_settings'] ) ) && isset( $_POST[ACTIVITIES_ADMIN_NICE_NONCE] ) && !wp_verify_nonce( $_POST[ACTIVITIES_ADMIN_NICE_NONCE], 'activities_nice' ) ) {
        die( esc_html__( 'Could not verify activity report data integrity.', 'activities' ) );
      }

      if ( isset( $_POST['time_slots'] ) ) {
        $time_slots = acts_validate_id( $_POST['time_slots'] ); //Time slots uses the same properies as an id
        if ( $time_slots >= 0 ) {
          $nice_settings['time_slots'] = $time_slots;
        }
      }

      $id = acts_validate_id( $_POST['item_id'] );
      if ( $id ) {
        $nice_settings['activity_id'] = $id;

        //Only get attended list if this not an example activity
        $attended = array();
        if ( isset( $_POST['time'] ) && is_array( $_POST['time'] ) && isset( $nice_settings['time_slots'] ) ) {
          foreach ($_POST['time'] as $uid => $times) {
            $uid = acts_validate_id( $uid );
            if ( $uid ) {
              //Stored as a string to make it easier to send to JavaScript and reduce size use when many boxes are checked
              $attended[$uid] = '';
              for ($t=0; $t < $nice_settings['time_slots']; $t++) {
                if ( isset( $times[$t] ) ) {
                  $attended[$uid] .= '1';
                }
                else {
                  $attended[$uid] .= '0';
                }
              }
            }
          }
        }
        $nice_settings['attended'] = $attended;
      }

      if ( isset( $_POST['acts_nice_logo_id'] ) ) {
        $nice_settings['logo'] = acts_validate_id( $_POST['acts_nice_logo_id'] );
      }

      if ( isset( $_POST['header'] ) ) {
        $nice_settings['header'] = sanitize_text_field( $_POST['header'] );
      }

      $setup = sanitize_key( $_POST['member_info'] );
      if ( array_key_exists( $setup, acts_get_nice_setups( $setup ) ) ) {
        $nice_settings['member_info'] = $setup;
      }

      foreach (array('start', 'end', 'short_desc', 'location', 'responsible', 'long_desc') as $a_key) {
        $nice_settings[$a_key] = isset( $_POST[$a_key] );
      }

      $meta_fields = $wpdb->get_col(
        "SELECT DISTINCT meta_key
        FROM $wpdb->usermeta"
      );
      $custom = array();
      if ( isset( $_POST['nice_custom'] ) && is_array( $_POST['nice_custom'] ) ) {
        foreach ($_POST['nice_custom'] as $col => $texts) {
          $col = acts_validate_id( $col );
          if ( $col === 1 || $col === 2 ) {
            foreach ($texts as $text) {
              $name = self::filter_meta_key_input( $meta_fields, $text );
              if ( $text != '' ) {
                $custom[] = array( 'name' => $name, 'col' => $col );
              }
            }
          }
        }
      }
      $nice_settings['custom'] = $custom;

      $colors = array();
      if (
          isset( $_POST['nice_color_key'] ) && isset( $_POST['nice_color'] ) &&
          is_array( $_POST['nice_color_key'] ) && is_array( $_POST['nice_color'] ) &&
          count( $_POST['nice_color_key'] ) == count( $_POST['nice_color'] )
         ) {
        for ($index=0; $index < count( $_POST['nice_color_key'] ); $index++) {
          $name = self::filter_meta_key_input( $meta_fields, $_POST['nice_color_key'][$index] );
          $color = sanitize_hex_color( $_POST['nice_color'][$index] );
          if ( $name !== '' && $color && !isset( $colors[$name] ) ) {
            $colors[$name] = $color;
          }
        }
      }
      $nice_settings['color'] = $colors;
    }

    return $nice_settings;
  }

  /**
   * Gets post values for activity
   *
   * @return array Activity info
   */
  static function get_activity_post_values() {
    $loc_id = acts_validate_id( $_POST['location'] );
    $res_id = acts_validate_id( $_POST['responsible'] );
    $members = array();
    if ( isset( $_POST['member_list'] ) && is_array( $_POST['member_list'] ) ) {
      foreach ($_POST['member_list'] as $id) {
        if ( acts_validate_id( $id ) ) {
          $members[] = $id;
        }
      }
    }
    $act_map = array(
      'name' => substr( sanitize_text_field( $_POST['name'] ), 0, 100 ),
      'short_desc' => substr( sanitize_text_field( $_POST['short_desc'] ), 0, 255  ),
      'long_desc' => substr( sanitize_textarea_field( $_POST['long_desc'] ), 0, 65535 ),
      'start' => self::validate_date( sanitize_text_field( $_POST['start'] ) ),
      'end' => self::validate_date( sanitize_text_field( $_POST['end'] ) ),
      'location_id' => ( $loc_id ? $loc_id : null ),
      'responsible_id' => ( $res_id ? $res_id : null ),
      'members' => $members
    );
    if ( isset( $_POST['item_id'] ) ) {
      $act_map['activity_id'] = acts_validate_id( $_POST['item_id'] );
    }
    $act_map['categories'] = array();
    if ( isset( $_POST['primary_category'] ) ) {
      $primary_cat = acts_validate_id( $_POST['primary_category'] );
      if ( Activities_Category::exists( $primary_cat ) ) {
        $act_map['categories'][] = $primary_cat;
      }
    }

    if ( isset( $_POST['additional_categories'] ) && is_array( $_POST['additional_categories'] ) ) {
      foreach ($_POST['additional_categories'] as $cat_id) {
        $cat_id = acts_validate_id( $cat_id );
        if ( Activities_Category::exists( $cat_id ) && !in_array( $cat_id, $act_map['categories'] ) ) {
          $act_map['categories'][] = $cat_id;
        }
      }
    }
    return $act_map;
  }

  /**
   * Gets post values for location
   *
   * @return array Location info
   */
  static function get_location_post_values() {
    $country = substr( sanitize_text_field( $_POST['country'] ), 0, 2 );
    if ( !array_key_exists( $country, Activities_Utility::get_countries() ) ) {
      $country = '';
    }
    $loc_map = array(
      'name' => substr( sanitize_text_field( $_POST['name'] ), 0, 100 ),
      'address' => substr( sanitize_text_field( $_POST['address'] ), 0, 255 ),
      'description' => substr( sanitize_textarea_field( $_POST['description'] ), 0, 65535 ),
      'city' => substr( sanitize_text_field( $_POST['city'] ), 0, 100 ),
      'postcode' => substr( sanitize_text_field( $_POST['postcode'] ), 0, 12 ),
      'country' => $country
    );

    if ( isset( $_POST['item_id'] ) ) {
      $loc_map['location_id'] = acts_validate_id( $_POST['item_id'] );
    }

    return $loc_map;
  }

  /**
   * Gets columns for activities
   *
   * @param   string  $archive 'Archive' to get columns for archive display
   * @return  array   Columns info
   */
  static function get_activity_columns( $archive = '' ) {
    if ( $archive != 'archive' ) {
      $options = Activities_Options::get_user_option( 'activity', 'show_columns' );
    }
    else {
      $options = Activities_Options::get_user_option( 'activity_archive', 'show_columns' );
    }

    $columns = array(
      'cb' => array(
        'hidden' => false,
        'sortable' => false,
      ),
      'name' => array(
        'hidden' => false,
        'sortable' => true,
      ),
      'short_desc' => array(
        'hidden' => !$options['short_desc'],
        'sortable' => false,
      ),
      'long_desc' => array(
        'hidden' => !$options['long_desc'],
        'sortable' => false,
      ),
      'start' => array(
        'hidden' => !$options['start'],
        'sortable' => true,
      ),
      'end' => array(
        'hidden' => !$options['end'],
        'sortable' => true,
      ),
      'responsible' => array(
        'hidden' => !$options['responsible'],
        'sortable' => true,
      ),
      'location' => array(
        'hidden' => !$options['location'],
        'sortable' => true,
      )
    );

    return $columns;
  }

  /**
   * Checks if a user can access an activity
   *
   * @param   string  $action Action done by a user
   * @param   int     $act_id Activity to check for access
   * @return  bool    If the user can do this action for selected activity
   */
  static function can_access_act( $action, $act_id ) {
    if ( $action == 'view' ) {
      $access = current_user_can( ACTIVITIES_ACCESS_ACTIVITIES );
      if ( Activities_Responsible::current_user_restricted_view() ) {
        $act = new Activities_Activity( $act_id );
        $access = $access && get_current_user_id() == $act->responsible_id;
      }
    }
    elseif ( $action == 'edit' ) {
      $access = current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES );
      if ( !$access && Activities_Responsible::current_user_restricted_edit() ) {
        $act = new Activities_Activity( $act_id );
        $access = get_current_user_id() == $act->responsible_id;
      }
    }

    return $access;
  }

  /**
   * Gets users for responsible or member input/display
   *
   * @param   string  $role Activity role, 'responsible' or 'member'
   * @param   array   $current_value Current users stored, used i case they are filtered by options but still needs to be displayed
   * @return  array   'ID' for user id, 'display_name' for name to display
   */
  static function get_users( $role, $current_value = array() ) {
    global $wpdb, $wp_roles;

    switch ($role) {
      case 'responsible':
        $key = ACTIVITIES_CAN_BE_RESPONSIBLE_KEY;
        break;

      case 'member':
      case 'members':
        $key = ACTIVITIES_CAN_BE_MEMBER_KEY;
        break;
    }

    $users = get_users( array( 'role__in' => Activities_Options::get_option( $key ) ) );

    $user_names = array();

    if ( !is_array( $current_value ) ) {
      $current_value = array( $current_value );
    }

    foreach ( $users as $user ) {
      $user_names[$user->ID] = Activities_Utility::get_user_name( $user );
      if ( count( $current_value ) > 0 ) {
        $key = array_search( $user->ID, $current_value );
        if ( $key !== false ) {
          unset( $current_value[$key] );
        }
      }
    }

    if ( count( $current_value ) > 0 ) {
      foreach ($current_value as $user_id) {
        $user = get_user_by( 'ID', $user_id );
        if ( $user !== false ) {
          $user_names[$user->ID] = Activities_Utility::get_user_name( $user );
        }
      }
    }

    return $user_names;
  }

  /**
   * Filters meta_key inputs from text fields
   *
   * @param   array   $meta_fields
   * @param   string  $input Text input
   * @return  string  Filtered text with only existing meta_keys
   */
  static function filter_meta_key_input( $meta_fields, $input ) {
    $input = sanitize_text_field( $input );

    $input_list = explode( ',', $input );
    foreach ($input_list as $key => $single_input) {
      $single_input = sanitize_key( $single_input );
      if ( activities_nice_filter_custom_field( $single_input ) || !in_array( $single_input, $meta_fields ) ) {
        unset( $input_list[$key] );
      }
      else {
        $input_list[$key] = $single_input;
      }
    }
    $input = implode( ',', $input_list );
    return $input;
  }

  /**
   * Echoes a scroll script for imports and other big data workloads
   */
  static function echo_scroll_script() {
    echo '<script>';
    echo 'var interval = setInterval( function() {
            jQuery("html, body").animate({ scrollTop: jQuery(".acts-progress-row").last().offset().top }, 50);
            if (jQuery("input[type=\'submit\'][name=\'return\']").length) {
              clearInterval(interval);
            }
          }, 100);';
    echo '</script>';
  }

  /**
    * Gets display name for data columns
    *
    * @param 	string $name Name of data column
    * @return string Display name
    */
  static function get_column_display( $name ) {
    switch ($name) {
      case 'name':
        return esc_html__( 'Name', 'activities' );
        break;

      case 'short_desc':
        return esc_html__( 'Short Description', 'activities' );
        break;

      case 'long_desc':
        return esc_html__( 'Long Description', 'activities' );
        break;

      case 'start':
        return esc_html__( 'Start Date', 'activities' );
        break;

      case 'end':
        return esc_html__( 'End Date', 'activities' );
        break;

      case 'responsible':
      case 'responsible_id':
        return esc_html__( 'Responsible', 'activities' );
        break;

      case 'location':
      case 'location_id':
        return esc_html__( 'Location', 'activities' );
        break;

      case 'address':
        return esc_html__( 'Address', 'activities' );
        break;

      case 'description':
        return esc_html__( 'Description', 'activities' );
        break;

      case 'city':
        return esc_html__( 'City', 'activities' );
        break;

      case 'postcode':
        return esc_html__( 'Postcode', 'activities' );
        break;

      case 'country':
        return esc_html__( 'Country', 'activities' );
        break;

      default:
        return 'undefined';
        break;
    }
  }

  /**
   * Validates a date input
   *
   * @param   string  $date Date input
   * @return  string  Date to insert into database
   */
  static function validate_date( $date, $format = 'Y-m-d' ) {
    $d = DateTime::createFromFormat( $format, $date );
    if ( $d && $d->format( $format ) == $date ) {
      return $d->format( 'Y-m-d H:i:s' );
    }
    else {
      return '0000-00-00 00:00:00';
    }
  }

  /**
   * Get names and ids for a list of items, filters broken ids
   *
   * @param   array   $items_ids List of items ids
   * @param   string  $type Type of item
   * @return  array   Nested list of names and ids
   */
  static function get_item_names( $items_ids, $type = 'activity' ) {
    $names = array();
    $ids = array();
    foreach ($items_ids as $id) {
      $id = acts_validate_id( $id );
      if ( !$id ) {
        continue;
      }
      switch ($type) {
        case 'activity':
          $item = new Activities_Activity( $id );
          break;

        case 'location':
          $item = new Activities_Location( $id );
          break;
      }
      if ( isset( $item ) && acts_validate_id( $item->id ) === $id ) {
        $names[] = esc_html( $item->name );
        $ids[] = $id;
      }
    }

    return array( 'names' => $names, 'ids' => $ids );
  }
}
