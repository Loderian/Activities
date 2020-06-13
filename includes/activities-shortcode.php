<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Activities 'acts' shortcode
 *
 * @param array $attr Shortcode input.
 *    - name => name of activity to get
 *    - data => data to get, prefix with 'loc_' for location or 'res_' for the responsible user
 *
 * @return    string Data, otherwise defaults to name if no data is found/selected. Defaults to '' if no activity is found.
 */
function acts_shortcode( $attr ) {
    $act = getActivity( isset( $attr['name'] ) ? $attr['name'] : null );

    if ( $act ) {
        if ( isset( $attr['data'] ) ) {
            return handleDataField( $act, $attr['data'], $attr );
        } else {
            return $act->name;
        }
    }

    return '';
}

function handleDataField( Activities_Activity $act, string $data, array $shorcode_input ) {
    $input = explode( '_', $data, 2 );
    $get   = null;
    if ( isset( $input[1] ) ) {
        $get = $input[1];
    }
    switch ( $input[0] ) {
        case 'location':
        case 'loc':
            $obj = $act->location;
            break;

        case 'responsible':
        case 'res':
            $obj = $act->responsible;
            break;

        default:
            $obj = $act;
            $get = $data;
            break;
    }
    $get = $get !== null ? $get : 'name';
    if ( $obj && isset( $get ) ) {
        if ( $obj instanceof WP_User ) {
            return handleUser( $obj, $get );
        } elseif ( $obj instanceof Activities_Activity ) {
            return handleActivity( $obj, $get, $shorcode_input );
        } elseif ( $obj instanceof Activities_Location ) {
            return handleLocation( $obj, $get );
        }
    }

    return '';
}

function handleUser( WP_User $user, string $get ) {
    switch ( $get ) {
        case 'name':
        case '':
            return Activities_Utility::get_user_name( $user->ID, false );

        case 'name_email':
            return Activities_Utility::get_user_name( $user->ID, true );

        default:
            if ( is_protected_meta( $get ) ) {
                return '***';
            }

            return $user->$get;
    }
}

function handleActivity( Activities_Activity $act, string $get, array $shortcode_data ) {
    switch ( $get ) {
        case 'archive':
            if ( $act->$get ) {
                return esc_html__( 'Archived', 'activities' );
            } else {
                return esc_html__( 'Active', 'activities' );
            }

        case 'start':
        case 'end':
            return Activities_Utility::format_date( $act->$get );

        case 'members':
            if ( !is_admin() ) {
                $text = '<span class="acts-member-count-' . esc_attr( $act->ID ) . '">%d</span>';
            } else {
                $text = '%d';
            }

            return sprintf( $text, count( $act->members ) );

        case 'join_button':
        case 'join_link':
        case 'join_image':
            return handleActivityJoin( $act, $get, $shortcode_data );

        case 'status_image':
        case 'status_text':
            return handleActivityStatus( $act, $get, $shortcode_data );

        default:
            if ( is_protected_meta( $get ) ) {
                return '***';
            }

            return $act->$get;
    }
}

function handleActivityStatus( Activities_Activity $act, string $get, array $shortcode_data ) {
    if ( is_admin() ) {
        return '';
    }

    $participating = false;
    if ( is_user_logged_in() ) {
        $current_user  = wp_get_current_user();
        $participating = array_search( $current_user->ID, $act->members ) !== false;
    }

    $default_joined_text = sprintf( __( "Participating in %s", "activities" ), $act->name );
    $default_not_joined_text = sprintf( __( "Not participating in %s", "activities" ), $act->name );

    switch ( $get ) {
        case 'status_image':
            $image = $participating ? $shortcode_data['joined'] : $shortcode_data['not_joined'];
            $alt   = $participating ? $default_joined_text : $default_not_joined_text;

            return '<img class="acts-status-image"
                         src="' . esc_attr( esc_url( $image ) ) . '"
                         alt="' . esc_attr( $alt ) . '" />';

        default:
        case 'status_text':
            $joined_text = isset( $shortcode_data['joined'] ) ? $shortcode_data['joined'] : $default_joined_text;
            $not_joined_text = isset( $shortcode_data['not_joined'] ) ? $shortcode_data['not_joined'] : $default_not_joined_text;
            return '<p class="acts-status-text">' . esc_html( $participating ? $joined_text : $not_joined_text ) . '</p>';
    }
}

function handleActivityJoin( Activities_Activity $act, string $get, array $shortcode_data ) {
    if ( is_admin() ) {
        return '';
    }
    if ( !is_user_logged_in() ) {
        return '<i>' . esc_html__( 'You have to login to join.', 'activities' ) . '</i>';
    }
    if ( $act->archive || ( $act->end != '0000-00-00 00:00:00' && $act->end !== null && date( 'U' ) > strtotime( $act->end ) ) ) {
        return '<i>' . esc_html__( 'You can no longer join this activity.', 'activities' ) . '</i>';
    }
    $current_user = wp_get_current_user();
    $roles        = $current_user->roles;
    $member_list  = Activities_Options::get_option( ACTIVITIES_CAN_BE_MEMBER_KEY );
    $can_join     = false;
    foreach ( $roles as $role ) {
        if ( in_array( $role, $member_list ) ) {
            $can_join = true;
            break;
        }
    }
    if ( !$can_join ) {
        return '<i>' . esc_html__( 'You are not allowed to join this activity.', 'activities' ) . '</i>';
    }

    $participant_limit = acts_validate_int( $act->participants_limit );
    if ( array_search( $current_user->ID, $act->members ) === false
         && $participant_limit !== 0
         && count( $act->members ) >= $participant_limit ) {
        return '<i>' . esc_html__( 'This activity is full.', 'activities' ) . '</i>';
    }

    $default_join_text  = sprintf( __( 'Join %s', 'activities' ), $act->name );
    $default_leave_text = sprintf( __( 'Leave %s', 'activities' ), $act->name );
    $join_text          = isset( $shortcode_data['join'] ) ? $shortcode_data['join'] : $default_join_text;
    $leave_text         = isset( $shortcode_data['leave'] ) ? $shortcode_data['leave'] : $default_leave_text;
    $button_filter      = apply_filters(
        'activities_' . $get,
        array(
            'allowed'            => true,
            'cant_join_response' => '',
            'join'               => $join_text,
            'leave'              => $leave_text
        ),
        $act->ID
    );
    if ( $button_filter['allowed'] ) {
        if ( Activities_User_Activity::exists( get_current_user_id(), $act->ID ) ) {
            $text     = $button_filter['leave'];
            $alt_text = $default_leave_text;
        } else {
            $text     = $button_filter['join'];
            $alt_text = $default_join_text;
        }

        switch ( $get ) {
            case 'join_link':
                $join_clickable =
                    '<a class="acts-join-link acts-join" 
                        href="#"
                        acts_join_text="' . esc_attr( $join_text ) . '" 
                        acts_leave_text="' . esc_attr( $leave_text ) . '" 
                        value="' . esc_attr( $act->ID ) . '">' .
                    esc_html( $text ) .
                    '</a>';
                break;

            case 'join_image':
                $join_clickable =
                    '<a class="acts-join-image acts-join" 
                        href="#"
                        acts_join_text="' . esc_attr( $join_text ) . '" 
                        acts_leave_text="' . esc_attr( $leave_text ) . '"
                        acts_alt_join_text="' . esc_attr( $default_join_text ) . '" 
                        acts_alt_leave_text="' . esc_attr( $default_leave_text ) . '"
                        value="' . esc_attr( $act->ID ) . '">
                       <img src="' . esc_attr( esc_url( $text ) ) . '"     
                            alt="' . esc_attr( $alt_text ) . '"/>
                    </a>';
                break;

            case 'join_button':
            default:
                $join_clickable =
                    '<button class="acts-join-button acts-join"
                             acts_join_text="' . esc_attr( $join_text ) . '" 
                             acts_leave_text="' . esc_attr( $leave_text ) . '"
                             value="' . esc_attr( $act->ID ) . '">' .
                    esc_html( $text ) .
                    '</button>';
                break;
        }

        return '<form class="acts-join-form" action="' . admin_url( 'admin-ajax.php' ) . '" method="post">
                    <input type="hidden" name="item_id" value="' . esc_attr( $act->ID ) . '" />
                    <input type="hidden" name="action" value="acts_join" />' .
               $join_clickable .
               '</form>';
    } else {
        return '<i>' . esc_html( $button_filter['response'] ) . '</i>';
    }
}

function handleLocation( Activities_Location $loc, string $get ) {
    $value = $loc->$get;
    switch ( $loc ) {
        case 'country';
            if ( $value != '' ) {
                return Activities_Utility::get_countries()[$value];
            } else {
                return '';
            }

        default:
            return $loc->$get;
    }
}

function getActivity( $name = null ) {
    $act = null;
    if ( $name ) {
        $act = Activities_Activity::load_by_name( sanitize_text_field( $name ) );
    } elseif ( isset( $_REQUEST['item_id'] ) ) {
        $id = acts_validate_int( $_REQUEST['item_id'] );
        if ( $id ) {
            $act = new Activities_Activity( $id );
            if ( $act->ID === '' ) {
                $act = null;
            }
        }
    }

    return $act;
}