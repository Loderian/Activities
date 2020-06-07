<?php

/**
 * Activity report quick edits
 *
 * @since      1.1.6
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
    die;
}

function activities_user_quick_edit( $current_url, $nice_settings ) {
    if ( $current_url != null ) {
        global $wp_roles;
        add_thickbox();

        $output = '<div id="acts-quick-user-edit" style="display: none">';
        $output .= '<form action="' . admin_url( 'admin-ajax.php' ) . '" class="acts-quick-edit-box acts-form" method="post">';

        //User info
        $output .= '<div>';
        $output .= '<div class="acts-quick-edit-type" type="user">';
        $output .= acts_nice_quick_inputs( array(
            'first_name' => esc_html__( 'First Name', 'activities' ),
            'last_name'  => esc_html__( 'Last Name', 'activities' )
        ), esc_html__( 'User', 'activities' ) );

        $output .= '<div class="acts-quick-edit-group">';
        $output .= '<span class="acts-quick-img-wrap">';
        $output .= '<img src="" id="acts-user-avatar" alt="' . esc_attr__( 'User avatar', 'activities' ) . '" />';
        $output .= '<div class="acts-nice-loader"></div>';
        $output .= '</span>';
        $output .= '</div>';

        $roles = $wp_roles->get_names();
        $output .= '<div class="acts-quick-edit-group"><table>';
        $output .= '<b>' . esc_html__( 'User Roles', 'activities' ) . '</b>';
        foreach ( $roles as $r_key => $r_name ) {
            $output .= '<tr class="acts-quick-edit-roles">';
            $output .= '<td>' . translate_user_role( $r_name ) . '</td>';

            $output .= '<td><input type="checkbox" user_role="' . $r_key . '" 
                                   name="' . esc_attr( 'roles[' . $r_key . ']' ) . '" /></td>';
            $output .= '</tr>';
        }
        $output .= '</table></div>';

        $output .= '</div></div>';

        //WooCommerce
        if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $output .= '<div><b class="acts-quick-edit-header" type="woocommerce">WooCommerce</b>';
            $output .= '<div class="acts-quick-edit-type">';
            $output .= acts_nice_quick_inputs( acts_get_woocommerce_nice_keys( 'bill' ), esc_html__( 'Billing', 'activities' ) );
            $output .= acts_nice_quick_inputs( acts_get_woocommerce_nice_keys( 'ship' ), esc_html__( 'Shipping', 'activities' ) );
            $output .= '</div></div>';
        }

        //Custom fields
        $non_custom = array_merge(
            array(
                'first_name',
                'last_name',
                'user_email'
            ),
            array_keys( acts_get_woocommerce_nice_keys() )
        );
        $custom_map = array();
        foreach ( $nice_settings['custom'] as $custom ) {
            $keys = explode( ',', $custom['name'] );
            foreach ( $keys as $key ) {
                $key = sanitize_key( $key );
                if ( !in_array( $key, $non_custom ) && !array_key_exists( $key, $custom_map ) ) {
                    $custom_map[ $key ] = acts_nice_key_display( $key );
                }
            }
        }

        $hidden       = '';
        $custom_input = '';
        if ( empty( $custom_map ) ) {
            $hidden = 'display: none;';
        } else {
            if ( count( $custom_map ) == 1 ) {
                $map1 = $custom_map;
                $map2 = array();
            } else {
                list( $map1, $map2 ) = array_chunk( $custom_map, ceil( count( $custom_map ) / 2 ), true );
            }
            $types        = Activities_Options::get_option( ACTIVITIES_QUICK_EDIT_TYPES_KEY );
            $custom_input = acts_nice_quick_inputs( $map1, '', 'custom', $types ) . acts_nice_quick_inputs( $map2, '', 'custom', $types );
        }
        $output .= '<div style="' . $hidden . '"><b class="acts-quick-edit-header">' . esc_html__( 'Custom Fields', 'activities' ) . '</b>';
        $output .= '<div class="acts-quick-edit-type" type="custom">';
        $output .= $custom_input;
        $output .= '</div></div>';

        $output .= '<input type="hidden" name="uid" />';
        $output .= '<input type="hidden" name="action" value="acts_quick_save" />';
        $output .= '<p>';
        $output .= get_submit_button( esc_html__( 'Save', 'activities' ), 'button-primary', 'acts_save_quick', false );

        $output .= '<a id="acts-nice-user-link" href="' . self_admin_url( 'user-edit.php' ) . '" target="_blank" class="button right">' . esc_html__( 'Open user page', 'activities' ) . '</a>';
        $output .= '</p>';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    } else {
        return '';
    }
}

/**
 * Build input for quick editing
 *
 * @param array $input_list List of inputs
 * @param string $header Optional header for list
 * @param string $list_name Add list syntax to input name
 * @param array $input_types Map input keys to input types
 *
 * @return  string  Html
 */
function acts_nice_quick_inputs( $input_list, $header = '', $list_name = '', $input_types = array() ) {
    $output = '<div class="acts-quick-edit-group"><ul>';
    if ( $header != '' ) {
        $output .= '<li><b class="acts-quick-edit-header">' . $header . '</b></li>';
    }
    foreach ( $input_list as $key => $display ) {
        $name = '%s';
        if ( $list_name != '' ) {
            $name = $list_name . '[%s]';
        }
        $output .= '<li><label for="acts-quick-' . esc_attr( $key ) . '">' . $display . '</label></li>';
        $output .= '<li>';
        $type   = '';
        if ( isset( $input_types[ $key ] ) ) {
            $type = $input_types[ $key ];
        }
        $id          = 'id="acts-quick-' . esc_attr( $key ) . '" ';
        $placeholder = 'placeholder="' . esc_attr( $display ) . '" ';
        $in_name     = 'name="' . esc_attr( sprintf( $name, $key ) ) . '" ';

        switch ( $type ) {
            case 'textarea':
                $output .= '<textarea ' . $id . $placeholder . $in_name . '></textarea>';
                break;

            case 'country':
                $output .= acts_build_select(
                    Activities_Utility::get_countries(),
                    array(
                        'name'      => sprintf( $name, $key ),
                        'id'        => 'acts-quick-' . esc_attr( $key ),
                        'blank'     => __( 'No Country', 'activities' ),
                        'blank_val' => ''
                    )
                );
                $output .= '<script>jQuery("#' . 'acts-quick-' . esc_attr( $key ) . '").selectize({});</script>';
                break;

            case 'text':
            default:
                $output .= '<input type="text" ' . $id . $placeholder . $in_name . ' />';
                break;
        }
        $output .= '</li>';
    }
    $output .= '</ul></div> ';

    return $output;
}