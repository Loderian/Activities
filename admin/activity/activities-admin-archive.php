<?php

/**
 * Activities archive page
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
 * Builds the archive view for activities
 *
 * @return string Admin page for archived activities/activity
 */
function activities_admin_archive_page() {
    if ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
        wp_die( esc_html__( 'Access Denied', 'activities' ) );
    }

    $current_url = ( isset( $_SERVER['HTTPS'] ) ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $current_url = remove_query_arg( 'action', $current_url );
    $current_url = remove_query_arg( 'item_id', $current_url );
    $current_url = remove_query_arg( ACTIVITIES_ARCHIVE_NONCE_GET, $current_url );

    if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'edit' && isset( $_GET['item_id'] ) ) {
        $activity = Activities_Activity::load( acts_validate_int( $_GET['item_id'] ) );
        if ( $activity !== null && $activity['archive'] == 1 ) {
            return acts_activity_management( esc_html__( 'Archived Activity', 'activities' ), 'activate', $activity, 'archive' );
        }
    } else {
        if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'view' && isset( $_GET['item_id'] ) ) {
            $activity = Activities_Activity::load( acts_validate_int( $_GET['item_id'] ) );
            if ( $activity !== null && $activity['archive'] == 1 ) {
                return acts_activity_nice_management( $activity, $current_url );
            }
        } else if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'activate' && isset( $_GET['item_id'] ) && isset( $_GET[ ACTIVITIES_ARCHIVE_NONCE_GET ] ) ) {
            if ( wp_verify_nonce( $_GET[ ACTIVITIES_ARCHIVE_NONCE_GET ], 'activities_activate_activity' ) ) {
                $id = acts_validate_int( $_GET['item_id'] );
                if ( Activities_Activity::archive( $id, 'reverse' ) ) {
                    $act = new Activities_Activity( $id );
                    Activities_Admin::add_success_message( sprintf( esc_html__( '%s has been activated.', 'activities' ), $act->name ) );
                }
            }
        } else if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'delete' && isset( $_GET['item_id'] ) ) {
            $act = new Activities_Activity( acts_validate_int( $_GET['item_id'] ) );
            if ( $act->name != '' ) {
                return acts_confirm_item_delete_page( esc_html__( 'Activity', 'activities' ), $act->id, $act->name, $current_url );
            }
        } else if ( isset( $_POST['confirm_deletion'] ) && isset( $_POST['item_id'] ) && isset( $_POST[ ACTIVITIES_DELETE_ITEM_NONCE ] ) && isset( $_POST['item_name'] ) ) {
            if ( wp_verify_nonce( $_POST[ ACTIVITIES_DELETE_ITEM_NONCE ], 'activities_delete_item' ) ) {
                if ( Activities_Activity::delete( acts_validate_int( $_POST['item_id'] ) ) ) {
                    Activities_Admin::add_delete_success_message( sanitize_text_field( $_POST['item_name'] ) );
                }
            }
        } else if ( isset( $_POST['apply_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) ) {
            $action = sanitize_key( $_POST['bulk'] );
            switch ( $action ) {
                case 'activate':
                    $title = esc_html__( 'Activate Activities', 'activities' );
                    break;

                case 'delete_a':
                    $title = esc_html__( 'Delete Activities', 'activities' );
                    break;

                case 'export_user_data':
                    $url = add_query_arg( array(
                      'page' => 'activities-admin-export',
                      'acts' => implode( ',', $_POST['selected_activities'] ),
                      'archive' => 1
                    ), $current_url );
                    if ( wp_safe_redirect( $url ) ) {
                        exit;
                    }
            }
            if ( isset( $title ) && is_array( $_POST['selected_activities'] ) ) {
                $names = Activities_Admin_Utility::get_item_names( $_POST['selected_activities'] );

                return activities_bulk_action_page( $names['ids'], $action, $title, $names['names'] );
            }
        } else if ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ ACTIVITIES_BULK_NONCE ] ) ) {
            if ( wp_nonce_field( $_POST[ ACTIVITIES_BULK_NONCE ], 'activities_bulk_action' ) ) {
                $acts = explode( ',', sanitize_text_field( $_POST['selected_activities'] ) );
                $bulk = new Activities_Bulk_Action();
                switch ( sanitize_key( $_POST['bulk'] ) ) {
                    case 'activate':
                        $bulk->activate_activities( $acts );
                        break;

                    case 'delete_a':
                        $bulk->delete_activities( $acts );
                        break;
                }
            }
        }
    }

    $output = '<h1 id="activities-title">';
    $output .= esc_html__( 'Activities Archive', 'activities' ) . '</h1>';

    $table_builder = new Activities_Activity_List_Table( true );

    $output .= $table_builder->display();

    return $output;
}
