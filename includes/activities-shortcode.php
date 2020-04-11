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
    $act = null;
    if ( isset( $attr['name'] ) ) {
        $act = Activities_Activity::load_by_name( sanitize_text_field( $attr['name'] ) );
    } elseif ( isset( $_REQUEST['item_id'] ) ) {
        $id = acts_validate_id( $_REQUEST['item_id'] );
        if ( $id ) {
            $act = new Activities_Activity( $id );
            if ( $act->ID === '' ) {
                $act = null;
            }
        }
    }

    if ( $act ) {
        if ( isset( $attr['data'] ) ) {
            $input = explode( '_', $attr['data'], 2 );
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
                    $get = $attr['data'];
                    break;
            }
            if ( $obj && isset( $get ) ) {
                if ( $obj instanceof WP_User && ( $get === 'name' || $get === '' ) ) {
                    return Activities_Utility::get_user_name( $obj->ID, false );
                } elseif ( $obj instanceof WP_User && $get === 'name_email' ) {
                    return Activities_Utility::get_user_name( $obj->ID, true );
                } elseif ( $obj instanceof Activities_Activity && $get === 'archive' ) {
                    if ( $obj->get ) {
                        return esc_html__( 'Archived', 'activities' );
                    } else {
                        return esc_html__( 'Active', 'activities' );
                    }
                } elseif ( $obj instanceof Activities_Activity && ( $get === 'start' || $get === 'end' ) ) {
                    return Activities_Utility::format_date( $obj->$get );
                } elseif ( $obj instanceof Activities_Activity && $get === 'members' ) {
                    if ( !is_admin() ) {
                        $text = '<span class="acts-member-count-' . esc_attr( $obj->ID ) . '">%d</span>';
                    } else {
                        $text = '%d';
                    }

                    return sprintf( $text, count( $obj->members ) );
                } elseif ( $obj instanceof Activities_Activity && $get === 'button' ) {
                    if ( is_admin() ) {
                        return '';
                    }
                    if ( !is_user_logged_in() ) {
                        return '<i>' . esc_html__( 'You have to login to join.', 'activities' ) . '</i>';
                    }
                    if ( $obj->archive || ( $obj->end != '0000-00-00 00:00:00' && $obj->end !== null && date( 'U' ) > strtotime( $obj->end ) ) ) {
                        return '<i>' . esc_html__( 'You can no longer join this activity.', 'activities' ) . '</i>';
                    }
                    $roles       = wp_get_current_user()->roles;
                    $member_list = Activities_Options::get_option( ACTIVITIES_CAN_BE_MEMBER_KEY );
                    $can_join    = false;
                    foreach ( $roles as $role ) {
                        if ( in_array( $role, $member_list ) ) {
                            $can_join = true;
                            break;
                        }
                    }
                    if ( !$can_join ) {
                        return '<i>' . esc_html__( 'You are not allowed to join this activity.', 'activities' ) . '</i>';
                    }
                    $button_filter = apply_filters(
                        'activities_join_button',
                        array(
                            'allowed'  => true,
                            'response' => ''
                        ),
                        $obj->ID
                    );
                    if ( $button_filter['allowed'] ) {
                        $text = esc_html__( 'Join', 'activities' );
                        if ( Activities_User_Activity::exists( get_current_user_id(), $obj->ID ) ) {
                            $text = esc_html__( 'Leave', 'activities' );
                        }

                        return '<form class="acts-join-form" action="' . admin_url( 'admin-ajax.php' ) . '" method="post">
                  <input type="hidden" name="item_id" value="' . esc_attr( $obj->ID ) . '" />
                  <input type="hidden" name="action" value="acts_join" />
                  <button class="acts-join-button">' . $text . '</button>
                  </form>';
                    } else {
                        return '<i>' . esc_html( $button_filter['response'] ) . '</i>';
                    }
                } elseif ( $obj instanceof Activities_Location && $get === 'country' ) {
                    $code = $obj->$get;
                    if ( $code != '' ) {
                        return Activities_Utility::get_countries()[ $code ];
                    } else {
                        return '';
                    }
                } elseif ( $get === '' ) {
                    return $obj->name;
                }

                if ( is_protected_meta( $get ) ) {
                    return '***';
                }

                return $obj->$get;
            } elseif ( $obj ) {
                if ( $obj instanceof WP_User ) {
                    return Activities_Utility::get_user_name( $obj->ID, false );
                }

                return $obj->name;
            }
        } else {
            return $act->name;
        }
    }

    return '';
}
