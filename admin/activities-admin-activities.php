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
    $activity = Activities_Activity::load( $_GET['item_id'] );
    if ( $activity !== null && $activity['archive'] == 0 ) {
      if ( Activities_Admin_Utility::can_access_act( 'edit', $_GET['item_id'] ) ) {
        return acts_activity_management( esc_html__( 'Edit Activity', 'activities' ), 'edit' , $activity );
      }
      else {
        Activities_Admin::add_error_message( esc_html__( 'You do not have permission to edit this activity.', 'activities' ) );
      }
    }
  }
  elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'view' && isset( $_GET['item_id'] ) ) {
    $activity = Activities_Activity::load( $_GET['item_id'] );
    if ( $activity !== null && $activity['archive'] == 0 ) {
      if ( Activities_Admin_Utility::can_access_act( 'view', $_GET['item_id'] ) ) {
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
        return acts_activity_management( esc_html__( 'Create New Activity', 'activities' ), esc_html__( 'Create', 'activities' ), $act_map );
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
    $act = new Activities_Activity( $_POST['item_id'] );
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
        $title = esc_html__( 'Archive Activities', 'activities' );
        break;

      case 'change_location':
        $title = esc_html__( 'Change Location', 'activities' );
        break;

      case 'change_responsible':
        $title = esc_html__( 'Change Responsible', 'activities' );
        break;

      case 'change_members':
        $title = esc_html__( 'Change Members', 'activities' );
        break;

      default:
        break;
    }

    if ( isset( $title ) ) {
      $activity_table = Activities::get_table_name( 'activity' );

      $names = array();

      foreach ($_POST['selected_activities'] as $id) {
        $act = new Activities_Activity( $id );
        if ( $act->name != '' ) {
          $names[] = $act->name;
        }
      }

      return activities_bulk_action_page( $_POST['selected_activities'], $_POST['bulk'], $title, $names );
    }
  }
  elseif ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ACTIVITIES_BULK_NONCE] ) ) {
    if ( wp_nonce_field( $_POST[ACTIVITIES_BULK_NONCE], 'activities_bulk_action' ) ) {
      $succ = 0;
      $activities = explode( ',', $_POST['selected_activities'] );
      switch ($_POST['bulk']) {
        case 'archive';
          foreach ($activities as $id) {
            if ( Activities_Activity::archive( $id ) ) {
              $succ++;
            }
          }

          Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities has been archived' ), $succ ) );
          break;

        case 'change_location':
          if( !isset( $_POST['location'] ) ) {
            break;
          }
          foreach ($activities as $id) {
            if ( Activities_Activity::update( array( 'activity_id' => $id, 'location_id' => ( is_numeric( $_POST['location'] ) ?  $_POST['location'] : null ) ) ) ) {
              $succ++;
            }
          }

          Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities had their location changed.', 'activities' ), $succ ) );
          break;

        case 'change_responsible':
          if ( !isset( $_POST['responsible'] ) ){
            break;
          }

          foreach ($activities as $id) {
            if ( Activities_Activity::update( array( 'activity_id' => $id, 'responsible_id' => ( is_numeric( $_POST['responsible'] ) ?  $_POST['responsible'] : null ) ) ) ) {
              $succ++;
            }
          }

          Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities had their responsible person changed.', 'activities' ), $succ ) );
          break;

        case 'change_members':
          if ( !isset( $_POST['members'] ) ) {
            break;
          }

          $table_name = Activities::get_table_name( 'user_activity' );

          switch ( $_POST['save_method']) {
            case 'replace':
              foreach ($activities as $id) {
                if ( Activities_User_Activity::insert_delete( $_POST['members'], $id, 'activity_id') ) {
                  $succ++;
                }
              }
              break;

            case 'add':
              if ( $_POST['members'] == '' ) {
                break;
              }
              foreach ($activities as $a_id) {
                $changed = false;
                foreach (explode( ',', $_POST['members'] ) as $u_id) {
                  if ( Activities_User_Activity::insert( $u_id, $a_id ) ) {
                    $changed = true;
                  }
                }
                if ( $changed ) {
                  $succ++;
                }
              }
              break;

            case 'remove':
              if ( $_POST['members'] == '' ) {
                break;
              }
              foreach ($activities as $a_id) {
                $changed = false;
                foreach (explode( ',', $_POST['members'] ) as $u_id) {
                  if ( Activities_User_Activity::delete( $u_id, $a_id ) ) {
                    $changed = true;
                  }
                }
                if ( $changed ) {
                  $succ++;
                }
              }

              break;

            default:
              Activities_Admin::add_error_message( esc_html__( 'Select a save method.', 'activities' ) );

              $activity_table = Activities::get_table_name( 'activity' );
              $names = array();
              foreach ($activities as $id) {
                $act = new Activities_Activity( $id );
                if ( $act->name != '' ) {
                  $names[] = $act->name;
                }
              }

              return activities_bulk_action_page( $activities, $_POST['bulk'], 'Change Memebers', $names, $_POST['members'] );
              break;
          }

          Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities had their members changed.', 'activities' ), $succ ) );
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
