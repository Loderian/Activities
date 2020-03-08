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
    $current_url = activities_admin_access_activities( array ( 'action', 'item_id', '_wpnonce' ) );

    if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'create' ) {
        return acts_plan_management( esc_html__( 'Create New Plan', 'activities' ), 'create' );
    } else if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] == 'edit' ) && isset( $_GET['item_id'] ) ) {
        $id = acts_validate_id( $_GET['item_id'] );
        if ( $id ) {
            return acts_plan_management( esc_html__( 'Edit Plan', 'activities' ), 'edit', Activities_Plan::load( $id ) );
        }
    } elseif ( isset( $_POST['create_plan'] ) ) {
        if ( !wp_verify_nonce( $_POST[ ACTIVITIES_PLAN_NONCE ], 'activities_plan' ) ) {
            wp_die( 'Access Denied' );
        }
        $plan_map = Activities_Admin_Utility::get_plan_post_values();
        if ( $plan_map['name'] === '' ) {
            Activities_Admin::add_error_message( esc_html__( 'The plan must have a name.', 'activities' ) );

            return acts_plan_management( esc_html__( 'Create New Plan', 'activities' ), 'create', $plan_map );
        }
        if ( !Activities_Plan::exists( $plan_map['name'], 'name' ) ) {
            if ( Activities_Plan::insert( $plan_map ) ) {
                Activities_Admin::add_create_success_message( $plan_map['name'] );
            } else {
                Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occurred creating plan: %s', 'activities' ), $plan_map['name'] ) );
            }
        } else {
            Activities_Admin::add_error_message( sprintf( esc_html__( 'An plan with name: %s already exists.', 'activities' ), $plan_map['name'] ) );

            return acts_plan_management( esc_html__( 'Create New Plan', 'activities' ), 'create', $plan_map );
        }
    } elseif ( isset( $_POST['edit_plan'] ) && isset( $_POST['item_id'] ) ) {
        if ( !wp_verify_nonce( $_POST[ ACTIVITIES_PLAN_NONCE ], 'activities_plan' ) ) {
            wp_die( 'Access Denied' );
        }
        $plan_map = Activities_Admin_Utility::get_plan_post_values();
        if ( $plan_map['name'] != '' ) {
            $plan = new Activities_Plan( acts_validate_id( $_POST['item_id'] ) );
            if ( $plan->id === '' ) {
                Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occurred updating plan: %s', 'activities' ), $plan_map['name'] ) );
            } elseif ( $plan->name === $plan_map['name'] || !Activities_Plan::exists( $plan_map['name'], 'name' ) ) {
                if ( Activities_Plan::update( $plan_map ) !== false ) {
                    Activities_Admin::add_update_success_message( stripslashes( wp_filter_nohtml_kses( $plan_map['name'] ) ) );
                } else {
                    Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occurred updating plan: %s', 'activities' ), $plan->name ) );
                }
            } else {
                Activities_Admin::add_error_message( sprintf( esc_html__( 'A plan with name %s already exists.', 'activities' ), $plan_map['name'] ) );
                $plan_map['name'] = $plan->name;

                return acts_plan_management( esc_html__( 'Edit Plan', 'activities' ), 'edit', $plan_map );
            }
        } else {
            Activities_Admin::add_name_error_message( esc_html__( 'Plan', 'activities' ) );

            return acts_plan_management( esc_html__( 'Edit Plan', 'activities' ), 'edit', $plan_map );
        }
    } else if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['item_id'] ) ) {
        $plan = new Activities_Plan( acts_validate_id( $_GET['item_id'] ) );
        if ( $plan->id != '' ) {
            return acts_confirm_item_delete_page( esc_html__( 'Plan', 'activities' ), $plan->id, $plan->name, $current_url );
        }
    } else if ( isset( $_POST['confirm_deletion'] ) && isset( $_POST['item_id'] ) && isset( $_POST[ ACTIVITIES_DELETE_ITEM_NONCE ] ) && isset( $_POST['item_name'] ) ) {
        if ( wp_verify_nonce( $_POST[ ACTIVITIES_DELETE_ITEM_NONCE ], 'activities_delete_item' ) ) {
            $id = acts_validate_id( $_POST['item_id'] );
            if ( $id && Activities_Plan::delete( $id ) ) {
                Activities_Admin::add_delete_success_message( sanitize_text_field( $_POST['item_name'] ) );
            }
        }
    } else if ( isset( $_POST['apply_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) ) {
        $action = sanitize_key( $_POST['bulk'] );
        switch ( $action ) {
            case 'delete_p':
                $title = esc_html__( 'Delete Plans', 'activities' );
                break;
        }
        if ( isset( $title ) && is_array( $_POST['selected_activities'] ) ) {
            $names = Activities_Admin_Utility::get_item_names( $_POST['selected_activities'], 'plan' );

            return activities_bulk_action_page( $names['ids'], $action, $title, $names['names'] );
        }
    } else if ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ ACTIVITIES_BULK_NONCE ] ) ) {
        if ( wp_nonce_field( $_POST[ ACTIVITIES_BULK_NONCE ], 'activities_bulk_action' ) ) {
            $plans = explode( ',', sanitize_text_field( $_POST['selected_activities'] ) );
            $bulk  = new Activities_Bulk_Action();
            switch ( sanitize_key( $_POST['bulk'] ) ) {
                case 'delete_p':
                    $bulk->delete_plans( $plans );
                    break;
            }
        }
    }

    $output = '<h1 id="activities-title">';
    $output .= esc_html__( 'Plans', 'activities' );
    if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
        $output .= '<a href="' . esc_url( $current_url ) . '&action=create" class="add page-title-action">' . esc_html__( 'Create new plan', 'activities' ) . '</a>';
    }
    $output .= '</h1>';

    $table  = new Activities_Plan_List_Table();
    $output .= $table->display();

    return $output;
}
