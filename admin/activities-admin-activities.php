<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       mikal.bindu.no
 * @since      1.0.0
 *
 * @package    Activities
 * @subpackage Activities/admin
 */

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Builds the main admin page for activities
 *
 * @return string Admin page for activities or activity
 */
function activities_admin_activities_page() {
  if ( !current_user_can( ACTIVITIES_ACCESS_ACTIVITIES ) ) {
    wp_die( esc_html__( 'Access Denied', 'activities' ) );
  }

  global $wpdb;

  $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $current_url = remove_query_arg( 'action', $current_url );
  $current_url = remove_query_arg( 'item_id', $current_url );

  if ( isset( $_GET['action'] ) && $_GET['action'] == 'create' ) {
    return acts_activity_management( esc_html__( 'Create New Activity', 'activities' ), 'create' );
  }
  elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['item_id'] ) ) {
    $activity = Activities_Activity::load( acts_validate_id( $_GET['item_id'] ) );
    if ( $activity !== null && $activity['archive'] == 0 ) {
      if ( Activities_Admin_Utility::can_access_act( 'edit', $activity['activity_id'] ) ) {
        return acts_activity_management( esc_html__( 'Edit Activity', 'activities' ), 'edit' , $activity );
      }
      else {
        Activities_Admin::add_error_message( esc_html__( 'You do not have permission to edit this activity.', 'activities' ) );
      }
    }
  }
  elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'view' && isset( $_GET['item_id'] ) ) {
    $activity = Activities_Activity::load( acts_validate_id( $_GET['item_id'] ) );
    if ( $activity !== null && $activity['archive'] == 0 ) {
      if ( Activities_Admin_Utility::can_access_act( 'view', $activity['activity_id'] ) ) {
        return acts_activity_nice_management( $activity, $current_url );
      }
      else {
        Activities_Admin::add_error_message( esc_html__( 'You do not have permission to view this activity.', 'activities' ) );
      }
    }
  }
  elseif ( isset( $_POST['create_act'] ) ) {
    if ( !wp_verify_nonce( $_POST[ACTIVITIES_ACTIVITY_NONCE], 'activities_activity' ) ) {
      wp_die( 'Access Denied' );
    }
    if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
      $act_map = Activities_Admin_Utility::get_activity_post_values();
      if ( $act_map['name'] === '' ) {
        Activities_Admin::add_error_message( esc_html__( 'The activity must have a name.', 'activities' ) );
        return acts_activity_management( esc_html__( 'Create New Activity', 'activities' ), 'create', $act_map );
      }
      if ( !Activities_Activity::exists( $act_map['name'], 'name' ) ) {
        if ( Activities_Activity::insert( $act_map ) ) {
          Activities_Admin::add_create_success_message( $act_map['name'] );
        }
        else {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured creating activity: %s', 'activities' ), $act_map['name'] ) );
        }
      }
      else {
        Activities_Admin::add_error_message( sprintf( esc_html__( 'An activity with name: %s already exists.', 'activities' ), $act_map['name'] ) );
        return acts_activity_management( esc_html__( 'Create New Activity', 'activities' ), 'create', $act_map );
      }
    }
    else {
      Activities_Admin::add_error_message( esc_html__( 'You do not have permission to create activities.', 'activities' ) );
    }
  }
  elseif ( isset( $_POST['edit_act'] ) && isset( $_POST['item_id'] ) ) {
    if ( !wp_verify_nonce( $_POST[ACTIVITIES_ACTIVITY_NONCE], 'activities_activity' ) ) {
      wp_die( 'Access Denied' );
    }
    $act = new Activities_Activity( acts_validate_id( $_POST['item_id'] ) );
    $act_map = Activities_Admin_Utility::get_activity_post_values();
    if ( $act_map['name'] === '' ) {
      Activities_Admin::add_error_message( esc_html__( 'The activity must have a name.', 'activities' ) );
      return acts_activity_management( esc_html__( 'Edit Activity', 'activities' ), 'edit', $act_map );
    }
    if ( $act->id === '' ) {
      Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured updating activity: %s ', 'activities' ), $act_map['name'] ) );
    }
    elseif ( Activities_Admin_Utility::can_access_act( 'edit', $act->id ) ) {
      if ( $act->name === $act_map['name'] || !Activities_Activity::exists( $act_map['name'], 'name' ) ) {
        if ( Activities_Activity::update( $act_map ) !== false ) {
          Activities_Admin::add_update_success_message( $act_map['name'] );
        }
        else {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured updating activity: %s ', 'activities' ), $act->name ) );
        }
      }
      else {
        Activities_Admin::add_error_message( sprintf( esc_html__( 'An activity with name: %s already exists.', 'activities' ), $act_map['name'] ) );
        $act_map['name'] = $act->name;
        return acts_activity_management( esc_html__('Edit Activity', 'activities' ), 'edit', $act_map );
      }
    }
    else {
      Activities_Admin::add_error_message( esc_html__( 'You do not have permission to update this activity.', 'activities' ) );
    }
  }
  elseif ( isset( $_POST['apply_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) ) {
    switch ($_POST['bulk']) {
      case 'archive':
        $header = esc_html__( 'Archive Activities', 'activities' );
        break;

      case 'change_location':
        $header = esc_html__( 'Change Location', 'activities' );
        break;

      case 'change_responsible':
        $header = esc_html__( 'Change Responsible User', 'activities' );
        break;

      case 'change_members':
        $header = esc_html__( 'Change Participants', 'activities' );
        break;

      default:
        break;
    }

    if ( isset( $header ) && is_array( $_POST['selected_activities'] ) ) {
      $names = array();
      $ids_filtered = array();
      foreach ($_POST['selected_activities'] as $id) {
        $act = new Activities_Activity( acts_validate_id( $id ) );
        if ( $act->name != '' ) {
          $names[] = esc_html( $act->name );
          $ids_filtered[] = $act->id;
        }
      }

      return activities_bulk_action_page( $ids_filtered, sanitize_text_field( $_POST['bulk'] ), $header, $names );
    }
  }
  elseif ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ACTIVITIES_BULK_NONCE] ) ) {
    if ( wp_nonce_field( $_POST[ACTIVITIES_BULK_NONCE], 'activities_bulk_action' ) ) {
      $acts = explode( ',', sanitize_text_field( $_POST['selected_activities'] ) );
      $bulk = new Activities_Bulk_Action();
      switch ($_POST['bulk']) {
        case 'archive';
          $bulk->archive_activities( $acts );
          break;

        case 'change_location':
          $bulk->change_locations( $acts, sanitize_text_field( $_POST['location'] ) );
          break;

        case 'change_responsible':
          $bulk->change_responsible_users( $acts, sanitize_text_field( $_POST['responsible'] ) );
          break;

        case 'change_members':
          if ( !isset( $_POST['members'] ) ) {
            break;
          }

          $method = sanitize_text_field( $_POST['method'] );
          $members = sanitize_text_field( $_POST['members'] );

          if ( $method === 'null' ) {
            Activities_Admin::add_error_message( esc_html__( 'Select a save method.', 'activities' ) );

            $names = array();
            $ids_filtered = array();
            foreach ($acts as $id) {
              $act = new Activities_Activity( acts_validate_id( $id ) );
              if ( $act->name != '' ) {
                $names[] = esc_html( $act->name );
                $ids_filtered[] = $act->id;
              }
            }

            return activities_bulk_action_page( $acts, sanitize_text_field( $_POST['bulk'] ), esc_html__( 'Change Participants', 'activities' ), $names, $members );
          }

          $bulk->change_members( $acts, explode( ',', $members ), $method );
          break;
      }
    }
  }

	$output = '<h1 id="activities-title">';
	$output .= esc_html__( 'Activities', 'activities' );
  if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES )) {
  	$output .= '<a href="' . esc_url( $current_url ) . '&action=create" class="add page-title-action">' . esc_html__( 'Create new activity', 'activities' ) . '</a>';
  }
	$output .= '</h1>';

  $table_builder = new Activities_List_Table( Activities_Admin_Utility::get_activity_columns(), 'activity' );

  $output .= $table_builder->display();

  return $output;
}
