<?php

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

  if ( isset( $_GET['action'] ) && $_GET['action'] == 'create' ) {
    return acts_location_management( esc_html__( 'Create New Location', 'activities' ), 'create' );
  }
  else if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['item_id'] ) ) {
    return acts_location_management( esc_html__( 'Edit Location', 'activities' ), 'edit', Activities_Location::load( $_GET['item_id'] ) );
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
        $loc = new Activities_Location( $_POST['item_id'] );
        if ( $loc->name === $loc_map['name'] || !Activities_Location::exists( $loc_map['name'], 'name' ) ) {
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
      $loc = new Activities_Location( $_GET['item_id'] );
      if ( $loc->id != '' ) {
        return acts_confirm_item_delete_page( esc_html__( 'Location', 'activities' ), $loc->id, $loc->name, $current_url );
      }
    }
    else if ( isset( $_POST['confirm_deletion'] ) && isset( $_POST['item_id'] ) && isset( $_POST[ACTIVITIES_DELETE_ITEM_NONCE] ) && isset( $_POST['item_name'] ) ) {
      if ( wp_verify_nonce( $_POST[ACTIVITIES_DELETE_ITEM_NONCE], 'activities_delete_item' ) ) {
        if ( Activities_Location::delete( $_POST['item_id'] ) ) {
          Activities_Admin::add_delete_success_message( $_POST['item_name'] );
        }
      }
    }
    else if ( isset( $_POST['apply_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) ) {
      switch ($_POST['bulk']) {
        case 'address':
          $title = esc_html__( 'Change Address', 'activities' );
          break;

        case 'delete_l':
          $title = esc_html__( 'Delete Locations', 'activities' );
          break;

        default:
          break;
      }
      if ( isset( $title ) ) {
        $names = array();

        $table_name = Activities::get_table_name( 'location' );

        foreach ($_POST['selected_activities'] as $id) {
          $loc = new Activities_Location( $id );
          if ( $loc->name != '' ) {
            $names[] = $loc->name;
          }
        }

        return activities_bulk_action_page( $_POST['selected_activities'], $_POST['bulk'], $title, $names );
      }
    }
    else if ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ACTIVITIES_BULK_NONCE] ) ) {
      if ( wp_nonce_field( $_POST[ACTIVITIES_BULK_NONCE], 'activities_bulk_action' ) ) {
        $succ = 0;
        $locations = explode( ',', $_POST['selected_activities'] );
        switch ($_POST['bulk']) {
          case 'address':
            $addr = sanitize_text_field( $_POST['address'] );
            foreach ($locations as $location_id) {
              if ( Activities_Location::update( array( 'location_id' => $location_id, 'address' => $addr ) ) ){
                $succ++;
              }
            }

            Activities_Admin::add_success_message( sprintf( esc_html__( '%d locations had their address changed.', 'activities' ), $succ ) );
            break;

          case 'delete_l':
            foreach ($locations as $id) {
              if ( Activities_Location::delete( $id ) ) {
                $succ++;
              }
            }

            Activities_Admin::add_success_message( sprintf( esc_html__( '%d locations has been deleted.', 'activities' ), $succ ) );
            break;

          default:
            break;
        }
      }
    }

	  $output = '<h1 id="activities-title">';
    $output .= esc_html__( 'Locations', 'activities' );
    $output .= '<a href="' . esc_url( $current_url . '&action=create' ) . '" class="add page-title-action" >' . esc_html__( 'Create new location', 'activities' ) . '</a>';
    $output .= '</h1>';

    $options = Activities_Options::get_user_option( 'location', 'show_columns' );

    $columns = array(
      'cb' => array(
        'hidden' => false,
        'sortable' => false
      ),
      'name' => array(
        'hidden' => false,
        'sortable' => true
      ),
      'address' => array(
        'hidden' => !$options['address'],
        'sortable' => true
      ),
      'description' => array(
        'hidden' => !$options['description'],
        'sortable' => false
      ),
      'city' => array(
        'hidden' => !$options['city'],
        'sortable' => true
      ),
      'postcode' => array(
        'hidden' => !$options['postcode'],
        'sortable' => true
      ),
      'country' => array(
        'hidden' => !$options['country'],
        'sortable' => true
      )
    );

    $table_builder = new Activities_List_Table( $columns, 'location' );

    $output .= $table_builder->display();

    return $output;
  }
}
