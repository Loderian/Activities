<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Ajax class for admin pages
 *
 * @since      1.8.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Admin_Ajax {

    /**
     * Returns member info mapped to 'col1' and 'col2'
     * Expects these data in post:
     *    - item_id: id of selected activity
     *    - custom: custom fields to get for member data
     *    - type: what type of information to display
     */
    public function ajax_get_member_info() {
        if ( !isset( $_POST['item_id'] ) ) {
            wp_send_json_error();
        }

        //Custom col sanitation is done by acts_get_user_info
        $id     = acts_validate_int( $_POST['item_id'] );
        $custom = array();
        if ( isset( $_POST['custom'] ) && is_array( $_POST['custom'] ) ) {
            $custom = $_POST['custom'];
        }
        $info     = array();
        $attended = array();
        if ( $id === 0 ) {
            $user_ids = array( -1, -2, -3, -4, -5 );
        } elseif ( $id > 0 ) {
            $user_ids      = Activities_User_Activity::get_activity_users( $id );
            $attended_meta = Activities_Activity::get_meta( $id, 'attended' );
            if ( $attended_meta !== null ) {
                $attended = $attended_meta;
            }
        }

        foreach ( $user_ids as $uid ) {
            $info[$uid] = acts_get_user_nice_info( $uid, $custom );
            if ( array_key_exists( $uid, $attended ) ) {
                $info[$uid]['acts_attended'] = $attended[$uid];
            } else {
                $info[$uid]['acts_attended'] = array();
            }
        }

        wp_send_json_success( $info );
    }

    /**
     * Get singe user info
     */
    public function ajax_get_user_info() {
        if ( !isset( $_POST['uid'] ) ) {
            wp_send_json_error();
        }

        $id = acts_validate_int( $_POST['uid'] );

        if ( $id ) {
            $data = acts_get_user_nice_info( $id );
            if ( $data ) {
                wp_send_json_success( $data );
            }
        }
        wp_send_json_error();
    }

    /**
     * Ajax callback for saving user using the quick edit
     */
    function ajax_acts_quick_save() {
        if ( !isset( $_POST['uid'] ) ) {
            wp_send_json_error();
        }

        $id = acts_validate_int( $_POST['uid'] );
        if ( !$id ) {
            wp_send_json_error();
        }

        $user_data = array(
            'ID'         => $id,
            'first_name' => stripslashes( sanitize_text_field( $_POST['first_name'] ) ),
            'last_name'  => stripslashes( sanitize_text_field( $_POST['last_name'] ) ),
        );
        $roles     = array();
        if ( isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ) {
            foreach ( $_POST['roles'] as $role => $is_set ) {
                $roles[] = sanitize_text_field( $role );
            }
        }
        if ( count( $roles ) > 0 ) {
            $user_data['role'] = $roles[0];
        } else {
            $user_data['role'] = '';
        }
        $ret_id = wp_update_user( $user_data );
        if ( is_wp_error( $ret_id ) ) {
            wp_send_json_error();
        }
        //Add after saving in with wp_update_user
        $user_data['acts_full_name'] = Activities_Utility::get_user_name( $id, false );
        $user_data['roles']          = $roles;
        if ( count( $roles ) > 0 ) {
            unset( $roles[0] ); //First one is already added
            $user = new WP_User( $id );
            foreach ( $roles as $role ) {
                $user->add_role( $role );
            }
        }

        foreach ( acts_get_woocommerce_nice_keys() as $key => $unused ) {
            if ( isset( $_POST[$key] ) ) {
                $value = stripslashes( sanitize_text_field( $_POST[$key] ) );
                update_user_meta( $id, $key, $value );
                $user_data[$key] = $value;
            }
        }

        if ( isset( $_POST['custom'] ) && is_array( $_POST['custom'] ) ) {
            $types = Activities_Options::get_option( ACTIVITIES_QUICK_EDIT_TYPES_KEY );
            foreach ( $_POST['custom'] as $key => $value ) {
                $key = sanitize_key( $key );

                $type = '';
                if ( isset( $types[$key] ) ) {
                    $type = $types[$key];
                }
                switch ( $type ) {
                    case 'textarea':
                        $value = sanitize_textarea_field( $value );
                        break;

                    case 'country':
                        $value = sanitize_text_field( $value );
                        if ( !array_key_exists( $value, Activities_Utility::get_countries() ) ) {
                            $value = '';
                        }
                        break;

                    case 'input':
                    default:
                        $value = stripslashes( sanitize_text_field( $value ) );
                        break;
                }
                if ( $key != '' ) {
                    update_user_meta( $id, $key, $value );
                    $user_data[$key] = $value;
                }
            }
        }

        wp_send_json_success( $user_data );
    }

    /**
     * Ajax callback for inserting category
     */
    function ajax_insert_cat() {
        $name   = sanitize_text_field( $_POST['name'] );
        $slug   = sanitize_title_with_dashes( $_POST['name'] );
        $parent = acts_validate_int( $_POST['parent'] );

        if ( $name != '' && $slug != '' ) {
            $term = term_exists( $name, Activities_Category::taxonomy );
            if ( empty( $term ) ) {
                $term = Activities_Category::insert(
                    array(
                        'name'   => $name,
                        'slug'   => $slug,
                        'parent' => $parent
                    )
                );

                if ( !is_wp_error( $term ) ) {
                    wp_send_json_success( array(
                        'id'     => $term['term_id'],
                        'name'   => $name,
                        'slug'   => $slug,
                        'parent' => $parent
                    ) );
                } else {
                    wp_send_json_error( $term );
                }
            }
        }

        wp_send_json_error( 'Name error' );
    }

    /**
     * Ajax callback for updating category
     */
    function ajax_update_cat() {
        $id     = acts_validate_int( $_POST['category_id'] );
        $name   = sanitize_text_field( $_POST['category_name'] );
        $parent = acts_validate_int( $_POST['category_parent'] );
        $desc   = sanitize_textarea_field( $_POST['category_description'] );

        if ( $id <= 0 ) {
            wp_send_json_error( 'id error' );
        }
        if ( $name == '' ) {
            wp_send_json_error( 'name error' );
        }
        if ( $id === $parent ) {
            wp_send_json_error( 'parent error' );
        }

        $term = term_exists( $name, Activities_Category::taxonomy );
        if ( empty( $term ) || $term['term_id'] == $id ) {
            $values = array(
                'id'     => $id,
                'name'   => $name,
                'parent' => $parent,
                'desc'   => $desc
            );
            $term   = Activities_Category::update( $values );

            if ( !is_wp_error( $term ) ) {
                wp_send_json_success( $values );
            } else {
                wp_send_json_error( $term );
            }
        }

        wp_send_json_error( 'duplicate error' );
    }

    /**
     * Ajax callback for deleting category
     */
    function ajax_delete_cat() {
        $id = acts_validate_int( $_POST['category_id'] );

        if ( $id > 0 ) {
            $term = get_term( $id, Activities_Category::taxonomy );

            if ( !empty( $term ) && $term->slug === 'uncategorized' ) {
                wp_send_json_error( 'Cant edit uncategorized' );
            }

            $term = Activities_Category::delete( $id );

            if ( $term && !is_wp_error( $term ) ) {
                $categories = Activities_Category::get_categories( 'id=>parent' );

                wp_send_json_success( $categories );
            } else {
                wp_send_json_error( $term );
            }
        }

        wp_send_json_error( 'Invalid id' );
    }

    /**
     * Ajax callback for creating plans on the report page
     */
    function ajax_create_plan() {
        $plan_map = Activities_Admin_Utility::get_plan_post_values();
        if ( $plan_map['plan_id'] > 0 ) {
            $plan = Activities_Plan::load( $plan_map['plan_id'] );
            if ( $plan['name'] === $plan_map['name'] ) {
                $update = Activities_Plan::update( $plan_map );
                if ( $update ) {
                    wp_send_json_success( esc_html__( 'Plan updated', 'activities' ) );
                } else {
                    wp_send_json_error( esc_html__( 'Error', 'activities' ) );
                }
            }
        }
        if ( Activities_Plan::exists( $plan_map['name'], 'name' ) ) {
            wp_send_json_error( sprintf( esc_html__( '%s already exists', 'activities' ), ( '<b>' . $plan_map['name'] . '</b>' ) ) );
        }
        $insert = Activities_Plan::insert( $plan_map );

        if ( $insert ) {
            //TODO Update activity with plan
            wp_send_json_success( esc_html__( 'Plan created', 'activities' ) );
        }

        wp_send_json_error( esc_html__( 'Error', 'activities' ) );
    }

    /**
     * Ajax callback for updated plan session, only on activity report
     */
    function ajax_update_plan_session() {
        $act_id = acts_validate_int( $_POST['item_id'] );
        if ( $act_id > 0 ) {
            $session_number = acts_validate_int( $_POST['session_number'] );
            $session_text = sanitize_textarea_field( $_POST['session_text'] );

            if ( $session_number > 0 ) {
                $report_settings = Activities_Activity::get_meta( $act_id, 'session_map' );
                $report_settings[$session_number] = $session_text;
                Activities_Activity::update_meta( $act_id, 'session_map', $report_settings, true );
                wp_send_json( esc_html__('Updated session on activity', 'activities') );
            } else {
                wp_send_json_error( esc_html__( 'Invalid session number', 'activities' ) );
            }
        } else {
            wp_send_json_error( esc_html__( 'Invalid activity id', 'activities' ) );
        }
    }
}

