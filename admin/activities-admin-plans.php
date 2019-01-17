<?php

/**
 * Activities plans page
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Builds the main admin page for plans
 *
 * @return string Admin page for plans
 */
function activities_admin_plans_page() {
  if ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
    wp_die( esc_html__( 'Access Denied', 'activities' ) );
  }

  global $wpdb;

  $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $current_url = remove_query_arg( 'action', $current_url );
  $current_url = remove_query_arg( 'item_id', $current_url );
  $current_url = remove_query_arg( '_wpnonce', $current_url );

  if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'create' ) {
    return acts_plan_management( esc_html__( 'Create New Plan', 'activities' ), 'create' );
  }
  elseif ( isset( $_POST['create_plan'] ) ) {
    if ( !wp_verify_nonce( $_POST[ACTIVITIES_PLAN_NONCE], 'activities_plan' ) ) {
      wp_die( 'Access Denied' );
    }
    if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
      $plan_map = Activities_Admin_Utility::get_plan_post_values();
      if ( $plan_map['name'] === '' ) {
        Activities_Admin::add_error_message( esc_html__( 'The plan must have a name.', 'activities' ) );
        return acts_activity_management( esc_html__( 'Create New Plan', 'activities' ), 'create', $plan_map );
      }
      if ( !Activities_Plan::exists( $plan_map['name'], 'name' ) ) {
        if ( Activities_Plan::insert( $plan_map ) ) {
          Activities_Admin::add_create_success_message( $plan_map['name'] );
        }
        else {
          Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured creating plan: %s', 'activities' ), $act_map['name'] ) );
        }
      }
      else {
        Activities_Admin::add_error_message( sprintf( esc_html__( 'An plan with name: %s already exists.', 'activities' ), $act_map['name'] ) );
        return acts_activity_management( esc_html__( 'Create New Plan', 'activities' ), 'create', $act_map );
      }
    }
    else {
      Activities_Admin::add_error_message( esc_html__( 'You do not have permission to create plans.', 'activities' ) );
    }
  }
  $output = '<h1 id="activities-title">';
	$output .= esc_html__( 'Plans', 'activities' );
  if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES )) {
  	$output .= '<a href="' . esc_url( $current_url ) . '&action=create" class="add page-title-action">' . esc_html__( 'Create new plan', 'activities' ) . '</a>';
  }
	$output .= '</h1>';

  $table = new Activities_Plan_List_Table();
  $output .= $table->display();

  return $output;
}
