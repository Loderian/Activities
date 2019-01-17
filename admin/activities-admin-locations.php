<?php

/**
 * Locations page
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
 * Builds the location pages
 *
 * @return string Admin page for locations/location
 */
function activities_admin_locations_page() {
  if ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
    wp_die( esc_html__( 'Access Denied', 'activities' ) );
  }

  global $wpdb;

  $current_url = ( isset($_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $current_url = remove_query_arg( 'action', $current_url );
  $current_url = remove_query_arg( 'item_id', $current_url );

  if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'create' ) {
    return acts_location_management( esc_html__( 'Create New Location', 'activities' ), 'create' );
  }
  else if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] == 'edit' ) && isset( $_GET['item_id'] ) ) {
    $id = acts_validate_id( $_GET['item_id'] );
    if ( $id ) {
      return acts_location_management( esc_html__( 'Edit Location', 'activities' ), 'edit', Activities_Location::load( $id ) );
    }
  }
  else {
    if ( isset( $_POST['create_loc'] ) ) {
      if ( !wp_verify_nonce( $_POST[ACTIVITIES_LOCATION_NONCE], 'activities_location' ) ) {
        wp_die( 'Access Denied' );
      }
      $loc_map = Activities_Admin_Utility::get_location_post_values();
      if ( $loc_map['name'] != '' ) {
        if ( Activities_Location::exists( $loc_map['name'], 'name' ) ) {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'A location with name %s already exists.' , 'activities' ), $loc_map['name'] ) );

          return acts_location_management( esc_html__( 'Create New Location', 'activities' ), 'create', $loc_map );
        }

        if ( Activities_Location::insert( $loc_map ) ) {
          Activities_Admin::add_create_success_message( stripslashes( wp_filter_nohtml_kses( $loc_map['name'] ) ) );
        }
        else {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured creating location: %s', 'activities' ), $loc_map['name'] ) );
        }
      }
      else {
        Activities_Admin::add_name_error_message( esc_html__( 'Location', 'activities' ) );

        return acts_location_management( esc_html__( 'Create New Location', 'activities' ), 'create', $loc_map );
      }
    }
    else if ( isset( $_POST['edit_loc'] ) && isset( $_POST['item_id'] ) ) {
      if ( !wp_verify_nonce( $_POST[ACTIVITIES_LOCATION_NONCE], 'activities_location' ) ) {
        wp_die( 'Access Denied' );
      }
      $loc_map = Activities_Admin_Utility::get_location_post_values();
      if ( $loc_map['name'] != '' ) {
        $loc = new Activities_Location( acts_validate_id( $_POST['item_id'] ) );
        if ( $loc->id === '' ) {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured updating location: %s', 'activities' ), $loc_map['name'] ) );
        }
        elseif ( $loc->name === $loc_map['name'] || !Activities_Location::exists( $loc_map['name'], 'name' ) ) {
          if ( Activities_Location::update( $loc_map ) !== false ) {
            Activities_Admin::add_update_success_message( stripslashes( wp_filter_nohtml_kses( $loc_map['name'] ) ) );
          }
          else {
            Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured updating location: %s', 'activities'), $loc->name ) );
          }
        }
        else {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'A location with name %s already exists.', 'activities' ), $loc_map['name'] ) );
          $loc_map['name'] = $loc->name;
          return acts_location_management( esc_html__( 'Edit Location', 'activities' ), 'edit', $loc_map );
        }
      }
      else {
        Activities_Admin::add_name_error_message( esc_html__( 'Location', 'activities' ) );

        return acts_location_management( esc_html__( 'Edit Location', 'activities' ), 'edit', $loc_map );
      }
    }
    else if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['item_id'] ) ) {
      $loc = new Activities_Location( acts_validate_id( $_GET['item_id'] ) );
      if ( $loc->id != '' ) {
        return acts_confirm_item_delete_page( esc_html__( 'Location', 'activities' ), $loc->id, $loc->name, $current_url );
      }
    }
    else if ( isset( $_POST['confirm_deletion'] ) && isset( $_POST['item_id'] ) && isset( $_POST[ACTIVITIES_DELETE_ITEM_NONCE] ) && isset( $_POST['item_name'] ) ) {
      if ( wp_verify_nonce( $_POST[ACTIVITIES_DELETE_ITEM_NONCE], 'activities_delete_item' ) ) {
        $id = acts_validate_id( $_POST['item_id'] );
        if ( $id && Activities_Location::delete( $id ) ) {
          Activities_Admin::add_delete_success_message( sanitize_text_field( $_POST['item_name'] ) );
        }
      }
    }
    else if ( isset( $_POST['apply_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) ) {
      $action = sanitize_key( $_POST['bulk'] );
      switch ($action) {
        case 'address':
          $title = esc_html__( 'Change Address', 'activities' );
          break;

        case 'delete_l':
          $title = esc_html__( 'Delete Locations', 'activities' );
          break;
      }
      if ( isset( $title ) && is_array( $_POST['selected_activities'] ) ) {
        $names = Activities_Admin_Utility::get_names( $_POST['selected_activities'] );

        return activities_bulk_action_page( $names['ids'] , $action, $title, $names['names'] );
      }
    }
    else if ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ACTIVITIES_BULK_NONCE] ) ) {
      if ( wp_nonce_field( $_POST[ACTIVITIES_BULK_NONCE], 'activities_bulk_action' ) ) {
        $locs = explode( ',', sanitize_text_field( $_POST['selected_activities'] ) );
        $bulk = new Activities_Bulk_Action();
        switch (sanitize_key( $_POST['bulk'] )) {
          case 'address':
            $bulk->change_address( $locs, sanitize_text_field( $_POST['address'] ) );
            break;

          case 'delete_l':
            $bulk->delete_locations( $locs );
            break;
        }
      }
    }

	  $output = '<h1 id="activities-title">';
    $output .= esc_html__( 'Locations', 'activities' );
    $output .= '<a href="' . esc_url( $current_url . '&action=create' ) . '" class="add page-title-action" >' . esc_html__( 'Create new location', 'activities' ) . '</a>';
    $output .= '</h1>';

    $table_builder = new Activities_Location_List_Table();

    $output .= $table_builder->display();

    return $output;
  }
}
