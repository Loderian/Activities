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
 */
function activities_export_page() {
    $current_url = activities_admin_access_activities( array ( 'item_id', 'acts' ) );

    $act_ids = array();
    if ( isset( $_POST['selected_activities'] ) ) {
        $act_ids = $_POST['selected_activities'];
    } elseif ( isset( $_GET['item_id'] ) ) {
        $act_ids = array( $_GET['item_id'] );
    } elseif ( isset( $_GET['acts'] ) ) {
        $act_ids = explode( ',', $_GET['acts'] );
    }
    $act_ids = array_filter( $act_ids, "acts_validate_id");

    $archive = isset( $_GET['archive'] ) && $_GET['archive'] == 1;

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
        if ( sizeof( $act_ids ) == 0 ) {
            Activities_Admin::add_error_message( esc_html__( 'Select one or more activities.', 'activities' ) );
        } elseif ( $user_meta === null ) {
            Activities_Admin::add_error_message( esc_html__( 'Select user data to export.', 'activities' ) );
        } else {
            $export = '';
            $notice = '';
            $users  = '';
            Activities_Options::update_user_option( 'export', $user_meta, $delimiter );
            $user_ids = array();
            foreach ( $act_ids as $act_id ) {
                $act = new Activities_Activity( $act_id );
                $user_ids = array_unique( array_merge( $user_ids, $act->members ), SORT_REGULAR );
            }
            $data = array();
            $user_count = count( $user_ids );
            foreach ( $user_ids as $user_id ) {
                switch ( $user_meta ) {
                    case 'email':
                        $data[ $user_id ] = acts_get_user_email( $user_id );
                        break;

                    case 'phone':
                        $data[ $user_id ] = acts_get_user_phone( $user_id );
                        break;

                    case 'name':
                        $data[ $user_id ] = Activities_Utility::get_user_name( $user_id, false );
                        break;
                }
            }
            $data = array_unique( $data, SORT_REGULAR );
            $export .= '<h3>' . sprintf( esc_html__( 'User data found for %s:', 'activities' ), stripslashes( wp_filter_nohtml_kses( acts_get_export_options()[ $user_meta ] ) ) );
            $export .= ' <span id="acts-export-copied">' . esc_html__( 'Copied!', 'activities' ) . '<span class="dashicons dashicons-yes"></span></span></h3>';
            if ( count( $data ) === 0 ) {
                $export .= '<p>' . esc_html__( 'No data found.', 'activities' ) . '</p>';
            } else {
                $export .= '<div id="acts-export-results" class="acts-box-wrap">';
                foreach ( $data as $key => $value ) {
                    $value = trim( $value );
                    if ( $value === '' ) {
                        unset( $data[ $key ] );
                        $users .= '<a href="' . get_edit_user_link( $key ) . '" >' . esc_html( Activities_Utility::get_user_name( $key ) ) . '</a></br>';
                    } else {
                        $data[ $key ] = esc_html( $value );
                    }
                }
                if ( count( $data ) < $user_count ) {
                    $notice .= '</br><b>' . esc_html__( 'Notice:', 'activities' ) . ' </b>' . sprintf( esc_html__( '%d users missing data', 'activities' ), ( $user_count - count( $data ) ) ) . '</br>';
                }
                switch ( $delimiter ) {
                    case 'semicolon':
                        $delimiter_char = '; ';
                        break;

                    case 'newline':
                        $delimiter_char = "\n";
                        break;

                    case 'comma':
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

    if ( $act_ids && Activities_Responsible::current_user_restricted_view() ) {
        $act = new Activities_Activity( $act_ids );
        if ( $act->responsible_id != get_current_user_id() ) {
            Activities_Admin::add_error_message( esc_html__( 'You are not allowed to export this activity.', 'activities' ) );
            $act_ids = '';
        }
    }

    $title = $archive ? esc_html__( 'Activities Archive Export', 'activities' ) : esc_html__( 'Activities Export', 'activities' );
    echo '<h1>' . $title;
    $button_text = $archive ? esc_html__( 'Active Activities', 'activities' ) : esc_html__( 'Archived Activities', 'activities' );
    echo '<a href="' . esc_url( add_query_arg( array( 'archive' => intval( !$archive ) ), $current_url ) ) . '" class="page-title-action">' . $button_text  . '</a>';
    echo '</h1>';

    echo Activities_Admin::get_messages();

    echo '<form action="' . esc_url( $current_url ) . '" method="post">';
    echo '<h3>' . esc_html__( 'Export Activities Participant Data', 'activities' ) . '</h3>';
    echo '<label for="acts_select_activity" class="acts-export-label">' . esc_html__( 'Select Activities', 'activities' ) . '</label>';
    echo acts_build_select_items(
        (!$archive ? 'activity' : 'activity_archive'),
        array(
            'name'     => 'selected_activities[]',
            'id'       => 'acts_select_activity_export',
            'class'    => array( 'acts-export-select' ),
            'selected' => $act_ids,
            'multiple' => 'multiple',
            'blank'    => false,
        ),
        Activities_Responsible::current_user_restricted_view()
    );

    echo '<label for="acts_select_user_meta" class="acts-export-label">' . esc_html__( 'Select User Data', 'activities' ) . '</label>';
    echo acts_build_select( acts_get_export_options(), array(
        'name'     => 'user_meta',
        'id'       => 'acts_select_user_meta',
        'class'    => array( 'acts-export-select' ),
        'selected' => $user_meta,
        'blank'    => false
    ) );

    if ( $user_meta !== null ) {
        $delimiter = Activities_Options::get_user_option( 'export', $user_meta );
    } elseif ( $delimiter === null ) {
        $delimiter = Activities_Options::get_user_option( 'export', 'email' );
    }
    echo '<label for="acts_select_delimiter" class="acts-export-label">' . esc_html__( 'Select Delimiter', 'activities' ) . '</label>';
    echo acts_build_select( acts_get_export_delimiters(), array(
        'name'     => 'delimiter',
        'id'       => 'acts_select_delimiter',
        'class'    => array( 'acts-export-select' ),
        'selected' => $delimiter,
        'blank'    => false
    ) );
    echo get_submit_button( esc_html__( 'Export', 'activities' ), 'button-primary', 'export_data' );

    echo '</form>';

    if ( isset( $export ) ) {
        echo $export;
    }

    $mapping = array();
    foreach ( acts_get_export_options() as $key => $value ) {
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
 * @param int $user_id Id of the user
 *
 * @return  string  Email
 */
function acts_get_user_email( $user_id ) {
    $user = new WP_User( $user_id );
    if ( $user->billing_email != '' ) {
        $email = $user->billing_email;
    } else {
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
 * @param int $user_id Id of the user
 *
 * @return  string  Phone number or '' if it could not find any
 */
function acts_get_user_phone( $user_id ) {
    global $wpdb;
    $user = new WP_User( $user_id );
    if ( $user->billing_phone != '' ) {
        return $user->billing_phone;
    } else {
        $p_numbers = $wpdb->get_col( $wpdb->prepare(
            "SELECT meta_value
      FROM {$wpdb->usermeta}
      WHERE meta_key LIKE '%phone%' AND user_id = %d
      ",
            $user_id
        ) );
        foreach ( $p_numbers as $p_number ) {
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
        'name'  => esc_html__( 'Name', 'activities' )
    );
}

function acts_get_export_delimiters() {
    return array(
        'comma'     => sprintf( esc_html__( 'Comma (%s)', 'activities' ), ', ' ),
        'semicolon' => sprintf( esc_html__( 'Semicolon (%s)', 'activities' ), '; ' ),
        'newline'   => esc_html__( 'Newline', 'activities' )
    );
}
