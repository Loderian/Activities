<?php

/**
 * Activities main page
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
 * Builds the main admin page for activities
 *
 * @return string Admin page for activities or activity
 */
function activities_admin_activities_page() {
    if ( !current_user_can( ACTIVITIES_ACCESS_ACTIVITIES ) ) {
        wp_die( esc_html__( 'Access Denied', 'activities' ) );
    }

    $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $current_url = remove_query_arg( 'action', $current_url );
    $current_url = remove_query_arg( 'item_id', $current_url );
    $current_url = remove_query_arg( '_wpnonce', $current_url );

    if ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'create' ) {
        return acts_activity_management( esc_html__( 'Create New Activity', 'activities' ), 'create' );
    } elseif ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'edit' && isset( $_GET['item_id'] ) ) {
        $activity = Activities_Activity::load( acts_validate_id( $_GET['item_id'] ) );
        if ( $activity !== null && $activity['archive'] == 0 ) {
            if ( Activities_Admin_Utility::can_access_act( 'edit', $activity['activity_id'] ) ) {
                return acts_activity_management( esc_html__( 'Edit Activity', 'activities' ), 'edit', $activity );
            } else {
                Activities_Admin::add_error_message( esc_html__( 'You do not have permission to edit this activity.', 'activities' ) );
            }
        }
    } elseif ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'view' && isset( $_GET['item_id'] ) ) {
        $activity = Activities_Activity::load( acts_validate_id( $_GET['item_id'] ) );
        if ( $activity !== null && $activity['archive'] == 0 ) {
            if ( Activities_Admin_Utility::can_access_act( 'view', $activity['activity_id'] ) ) {
                return acts_activity_nice_management( $activity, $current_url );
            } else {
                Activities_Admin::add_error_message( esc_html__( 'You do not have permission to view this activity.', 'activities' ) );
            }
        }
    } elseif ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) == 'duplicate' && isset( $_GET['item_id'] ) ) {
        $act_id = acts_validate_id( $_GET['item_id'] );
        if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) && $act_id && wp_verify_nonce( $_GET['_wpnonce'], 'duplicate_act_' . $act_id ) ) {
            $new_act_id = Activities_Activity::duplicate( $act_id );

            if ( $new_act_id ) {
                wp_safe_redirect( $current_url . '&action=edit&item_id=' . $new_act_id );
                exit;
            }

            Activities_Admin::add_error_message( esc_html__( 'An error occured during duplication of activity.', 'activities' ) );
        } else {
            Activities_Admin::add_error_message( esc_html__( 'You do not have permission to duplicate activities.', 'activities' ) );
        }
    } elseif ( isset( $_POST['create_act'] ) ) {
        if ( !wp_verify_nonce( $_POST[ ACTIVITIES_ACTIVITY_NONCE ], 'activities_activity' ) ) {
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
                } else {
                    Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured creating activity: %s', 'activities' ), $act_map['name'] ) );
                }
            } else {
                Activities_Admin::add_error_message( sprintf( esc_html__( 'An activity with name: %s already exists.', 'activities' ), $act_map['name'] ) );

                return acts_activity_management( esc_html__( 'Create New Activity', 'activities' ), 'create', $act_map );
            }
        } else {
            Activities_Admin::add_error_message( esc_html__( 'You do not have permission to create activities.', 'activities' ) );
        }
    } elseif ( isset( $_POST['edit_act'] ) && isset( $_POST['item_id'] ) ) {
        if ( !wp_verify_nonce( $_POST[ ACTIVITIES_ACTIVITY_NONCE ], 'activities_activity' ) ) {
            wp_die( 'Access Denied' );
        }
        $act     = new Activities_Activity( acts_validate_id( $_POST['item_id'] ) );
        $act_map = Activities_Admin_Utility::get_activity_post_values();
        if ( $act_map['name'] === '' ) {
            Activities_Admin::add_error_message( esc_html__( 'The activity must have a name.', 'activities' ) );

            return acts_activity_management( esc_html__( 'Edit Activity', 'activities' ), 'edit', $act_map );
        }
        if ( $act->id === '' ) {
            Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured updating activity: %s ', 'activities' ), $act_map['name'] ) );
        } elseif ( Activities_Admin_Utility::can_access_act( 'edit', $act->id ) ) {
            if ( $act->name === $act_map['name'] || !Activities_Activity::exists( $act_map['name'], 'name' ) ) {
                if ( Activities_Activity::update( $act_map ) !== false ) {
                    Activities_Admin::add_update_success_message( $act_map['name'] );
                } else {
                    Activities_Admin::add_error_message( sprintf( esc_html__( 'An error occured updating activity: %s ', 'activities' ), $act->name ) );
                }
            } else {
                Activities_Admin::add_error_message( sprintf( esc_html__( 'An activity with name: %s already exists.', 'activities' ), $act_map['name'] ) );
                $act_map['name'] = $act->name;

                return acts_activity_management( esc_html__( 'Edit Activity', 'activities' ), 'edit', $act_map );
            }
        } else {
            Activities_Admin::add_error_message( esc_html__( 'You do not have permission to update this activity.', 'activities' ) );
        }
    } elseif ( isset( $_POST['apply_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) ) {
        $action = sanitize_key( $_POST['bulk'] );
        switch ( $action ) {
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
        }

        if ( isset( $header ) && is_array( $_POST['selected_activities'] ) ) {
            $names = Activities_Admin_Utility::get_item_names( $_POST['selected_activities'] );

            return activities_bulk_action_page( $names['ids'], $action, $header, $names['names'] );
        }
    } elseif ( isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && isset( $_POST['selected_activities'] ) && isset( $_POST[ ACTIVITIES_BULK_NONCE ] ) ) {
        if ( wp_nonce_field( $_POST[ ACTIVITIES_BULK_NONCE ], 'activities_bulk_action' ) ) {
            $acts = explode( ',', sanitize_text_field( $_POST['selected_activities'] ) );
            $bulk = new Activities_Bulk_Action();
            switch ( sanitize_key( $_POST['bulk'] ) ) {
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
                    $method  = sanitize_text_field( $_POST['method'] );
                    $members = array();
                    if ( isset( $_POST['members'] ) && is_array( $_POST['members'] ) ) {
                        foreach ( $_POST['members'] as $id ) {
                            if ( acts_validate_id( $id ) ) {
                                $members[] = $id;
                            }
                        }
                    }

                    if ( $method === 'null' ) {
                        Activities_Admin::add_error_message( esc_html__( 'Select a save method.', 'activities' ) );

                        $names = Activities_Admin_Utility::get_item_names( $acts );

                        return activities_bulk_action_page( $names['ids'], sanitize_key( $_POST['bulk'] ), esc_html__( 'Change Participants', 'activities' ), $names['names'], $members );
                    }

                    $bulk->change_members( $acts, $members, $method );
                    break;
            }
        }
    }

    $output = '<h1 id="activities-title">';
    $output .= esc_html__( 'Activities', 'activities' );
    if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
        $output .= '<a href="' . esc_url( $current_url ) . '&action=create" class="add page-title-action">' . esc_html__( 'Create new activity', 'activities' ) . '</a>';
    }
    $output .= '</h1>';

    $table_builder = new Activities_Activity_List_Table();

    $output .= $table_builder->display();

    return $output;
}
