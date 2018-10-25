<?php

/**
 * Activities export page
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
 * Builds export page for activities
 *
 * @return string Export page
 */
function activities_export_page() {
  if ( !current_user_can( ACTIVITIES_ACCESS_ACTIVITIES ) ) {
    wp_die( esc_html__( 'Access Denied', 'activities' ) );
  }

  global $wpdb;

  $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $current_url = remove_query_arg( 'item_id', $current_url );

  $act_id = 0;
  if ( isset( $_POST['selected_activity'] ) ) {
    $act_id = $_POST['selected_activity'];
  }
  elseif ( isset( $_GET['item_id'] ) ) {
    $act_id = $_GET['item_id'];
  }
  $act_id = acts_validate_id( $act_id );
  if ( !$act_id ) {
    $act_id = '';
  }

  $user_meta = null;
  if ( isset( $_POST['user_meta'] ) ) {
    $user_meta = sanitize_key( $_POST['user_meta'] );
    if ( !array_key_exists( $user_meta, acts_get_export_options() ) ) {
      $user_meta = null;
    }
  }

  $delimiter = null;
  if ( isset( $_POST['delimiter'] ) ) {
    $delimiter = sanitize_key( $_POST['delimiter'] );
    if ( !array_key_exists( $delimiter, acts_get_export_delimiters() ) ) {
      $delimiter = null;
    }
  }

  if ( isset( $_POST['export_data'] ) ) {
    if ( $act_id === '' ) {
      Activities_Admin::add_error_message( esc_html__( 'Select an activity.', 'activities' ) );
    }
    elseif ( $user_meta === null ) {
      Activities_Admin::add_error_message( esc_html__( 'Select user data to export.', 'activities' ) );
    }
    else {
      $export = '';
      $notice = '';
      $users = '';
      Activities_Options::update_user_option( 'export', $user_meta, $delimiter );
      $act = new Activities_Activity( $act_id );
      $user_ids = $act->members;
      $data = array();
      $user_count = count( $user_ids );
      foreach ($user_ids as $user_id) {
        switch ($user_meta) {
          case 'email':
            $data[$user_id] = acts_get_user_email( $user_id );
            break;

          case 'phone':
            $data[$user_id] = acts_get_user_phone( $user_id );
            break;

          case 'name':
            $data[$user_id] = Activities_Utility::get_user_name( $user_id, false );
            break;
        }
      }
      $export .= '<h3>' . sprintf( esc_html__( 'User data found for %s:', 'activities' ), stripslashes( wp_filter_nohtml_kses( acts_get_export_options()[$user_meta] ) ) );
      $export .= ' <span id="acts-export-copied">' . esc_html__( 'Copied!', 'activities' ) . '<span class="dashicons dashicons-yes"></span></span></h3>';
      if ( count( $data ) === 0 ) {
        $export .= '<p>' . esc_html__( 'No data found.', 'activities' ) . '</p>';
      }
      else {
        $export .= '<div id="acts-export-results" class="activities-box-wrap">';
        foreach ($data as $key => $value) {
          $value = trim($value);
          if ( $value === '' ) {
            unset( $data[$key] );
            $users .= '<a href="' . get_edit_user_link( $key ) . '" >' . esc_html( Activities_Utility::get_user_name( $key ) ) . '</a></br>';
          }
          else {
            $data[$key] = esc_html( $value );
          }
        }
        if ( count( $data ) < $user_count ) {
          $notice .= '</br><b>' . esc_html__( 'Notice:', 'activities' ) . ' </b>' . sprintf( esc_html__( '%d users missing data', 'activities' ), ($user_count - count( $data )) ). '</br>';
        }
        switch ($delimiter) {
          case 'comma':
            $delimiter_char = ', ';
            break;

          case 'semicolon':
            $delimiter_char = '; ';
            break;

          case 'newline':
            $delimiter_char = "\n";
            break;

          default:
            $delimiter_char = ', ';
            break;
        }

        $export .= implode( $delimiter_char, $data );
        $export .= '</div>';
      }
      $export .= $notice;
      $export .= $users;
    }
  }

  if ( $act_id && Activities_Responsible::current_user_restricted_view() ) {
    $act = new Activities_Activity( $act_id );
    if ( $act->responsible_id != get_current_user_id() ) {
      Activities_Admin::add_error_message( esc_html__( 'You are not allowed to export this activity.', 'activities' ) );
      $act_id = '';
    }
  }

  echo '<h1>' . esc_html__( 'Activities Export', 'activities' ) . '</h1>';

  echo Activities_Admin::get_messages();

  echo '<form action="' . esc_url( $current_url ) . '" method="post">';
  echo '<h3>' . esc_html__( 'Export Activity Participant Data', 'activities' ) . '</h3>';
  echo '<label for="acts_select_activity" class="acts-export-label">' . esc_html__( 'Select Activity', 'activities' ) . '</label>';
  echo acts_build_select_items(
    'all_activities',
    array(
      'name' => 'selected_activity',
      'id' => 'acts_select_activity_export',
      'class' => array( 'acts-export-select' ),
      'selected' => $act_id,
      'no_blank' => true,
    ),
    Activities_Responsible::current_user_restricted_view()
  );

  echo '<label for="acts_select_user_meta" class="acts-export-label">' . esc_html__( 'Select User Data', 'activities' ) . '</label>';
  echo acts_build_select( acts_get_export_options(), array(
    'name' => 'user_meta',
    'id' => 'acts_select_user_meta',
    'class' => array( 'acts-export-select' ),
    'selected' => $user_meta,
    'no_blank' => true
  ));

  if ( $user_meta !== null ) {
    $delimiter = Activities_Options::get_user_option( 'export', $user_meta );
  }
  elseif ( $delimiter === null ) {
    $delimiter = Activities_Options::get_user_option( 'export', 'email' );
  }
  echo '<label for="acts_select_delimiter" class="acts-export-label">' . esc_html__( 'Select Delimiter', 'activities' ) . '</label>';
  echo acts_build_select( acts_get_export_delimiters(), array(
    'name' => 'delimiter',
    'id' => 'acts_select_delimiter',
    'class' => array( 'acts-export-select' ),
    'selected' => $delimiter,
    'no_blank' => true
  ));
  echo get_submit_button( esc_html__( 'Export', 'activities' ), 'button-primary', 'export_data' );

  echo '</form>';

  if ( isset( $export ) ) {
    echo $export;
  }

  $mapping = array();
  foreach (acts_get_export_options() as $key => $value) {
    $mapping[] = $key . ': "' . wp_filter_nohtml_kses( Activities_Options::get_user_option( 'export', $key ) ) . '"';
  }

  $js_map = 'var defaults = {' . implode( ', ', $mapping ) . '};';

  echo '<script>';
  echo 'var $d_select = jQuery("#acts_select_delimiter").selectize({});';
  echo 'var d_selectize = $d_select[0].selectize;';
  echo 'jQuery("#acts_select_user_meta").selectize({
          onChange: function(value) {'
            . $js_map .
            'd_selectize.setValue(defaults[value], false);
          }
        });';
  echo '</script>';
}

/**
 * Get user email
 *
 * Tries first to get billing_email, if it does not exist it gets the user_email
 *
 * @param   int     $user_id Id of the user
 * @return  string  Email
 */
function acts_get_user_email( $user_id ) {
  $user = new WP_User( $user_id );
  if ( $user->billing_email != '' ) {
    $email = $user->billing_email;
  }
  else {
    $email = $user->user_email;
  }
  return $email;
}

/**
 * Get user phone number
 *
 * Tries first to get billing_phone.
 * If it does not exist, it searches for phone in the user_meta table
 *
 * @param   int     $user_id Id of the user
 * @return  string  Phone number or '' if it could not find any
 */
function acts_get_user_phone( $user_id ) {
  global $wpdb;
  $user = new WP_User( $user_id );
  if ( $user->billing_phone != '' ) {
    return $user->billing_phone;
  }
  else {
    $p_numbers = $wpdb->get_col( $wpdb->prepare(
      "SELECT meta_value
      FROM {$wpdb->usermeta}
      WHERE meta_key LIKE '%phone%' AND user_id = %d
      ",
      $user_id
    ));
    foreach ($p_numbers as $p_number) {
      if ( $p_number != '' ) {
        return $p_number;
      }
    }
  }

  return '';
}

function acts_get_export_options() {
  return array(
    'email' => esc_html__( 'Email', 'activities' ),
    'phone' => esc_html__( 'Phone', 'activities' ),
    'name' => esc_html__( 'Name', 'activities' )
  );
}

function acts_get_export_delimiters() {
  return array(
    'comma' => sprintf( esc_html__( 'Comma (%s)', 'activities' ), ', ' ),
    'semicolon' => sprintf( esc_html__( 'Semicolon (%s)', 'activities' ), '; ' ),
    'newline' => esc_html__( 'Newline', 'activities' )
  );
}
