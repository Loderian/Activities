<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Activities options control class
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Options {
    /**
     * Plugin options key
     */
    const option_name = 'activities_options';

    /**
     * User options key
     */
    const user_option_name = 'activities_user_option';

    /**
     * No creating objects
     */
    private function __construct() {
    }

    /**
     * Initializes plugin options
     */
    static function init() {
        add_option( self::option_name, array(), '', 'no' );
    }

    /**
     * Initializes user options
     *
     * @param $user_id
     */
    static function init_user( $user_id ) {
        add_user_meta( $user_id, self::user_option_name, array(), true );
    }

    /**
     * Get default plugin option
     *
     * @param string $key Option to get
     *
     * @return  mixed   Default option for given key
     */
    static function get_default( $key ) {
        global $wp_roles;
        switch ( $key ) {
            case ACTIVITIES_RESPONSIBLE_KEY:
                return ACTIVITIES_RESPONSIBLE_SAME;
                break;

            case ACTIVITIES_CAN_BE_RESPONSIBLE_KEY:
            case ACTIVITIES_CAN_BE_MEMBER_KEY:
                return array_keys( $wp_roles->get_names() );
                break;

            case ACTIVITIES_NICE_SETTINGS_KEY:
                return array(
                    'header'      => '',
                    'start'       => true,
                    'end'         => true,
                    'short_desc'  => true,
                    'location'    => true,
                    'responsible' => true,
                    'long_desc'   => true,
                    'logo'        => 0,
                    'time_slots'  => 8,
                    'member_info' => 'wp',
                    'custom'      => array(),
                    'color'       => array()
                );
                break;

            case ACTIVITIES_WOOCOMMERCE_CONVERT_KEY:
            case ACTIVITIES_DELETE_DATA_KEY:
                return false;
                break;

            case ACTIVITIES_USER_SEARCH_KEY:
                return true;
                break;

            case ACTIVITIES_NICE_WC_COUPONS_KEY:
                return array();
                break;

            case ACTIVITIES_QUICK_EDIT_TYPES_KEY:
                return array();

            default:
                return null;
                break;
        }
    }

    /**
     * Get default user option
     *
     * @param string $page Page the option belongs to
     * @param string $key Option to get
     *
     * @return  mixed   Default option for given key
     */
    static function get_default_user_option( $page, $key ) {
        switch ( $page ) {
            case 'activity':
            case 'activity_archive':
                switch ( $key ) {
                    case 'items_per_page':
                        return 10;
                        break;

                    case 'filters':
                        return array(
                            'name'        => '',
                            'responsible' => '',
                            'location'    => '',
                            'category'    => 0,
                        );
                        break;

                    case 'show_columns':
                        return array(
                            'short_desc'  => true,
                            'long_desc'   => false,
                            'start'       => false,
                            'end'         => false,
                            'responsible' => true,
                            'location'    => true,
                            'categories'  => false,
                            'plan'        => false
                        );
                        break;
                }
                break;

            case 'location':
                switch ( $key ) {
                    case 'items_per_page':
                        return 10;
                        break;

                    case 'filters':
                        return array(
                            'name'    => '',
                            'address' => ''
                        );
                        break;

                    case 'show_columns':
                        return array(
                            'address'     => true,
                            'postcode'    => false,
                            'city'        => true,
                            'description' => false,
                            'country'     => false
                        );
                        break;
                }
                break;

            case 'plan':
                switch ( $key ) {
                    case 'items_per_page':
                        return 10;
                        break;

                    case 'filters':
                        return array(
                            'name'     => '',
                            'sessions' => ''
                        );
                        break;

                    case 'show_columns':
                        return array(
                            'description' => true,
                            'sessions'    => true
                        );
                        break;
                }
                break;

            case 'export':
                switch ( $key ) {
                    case 'email':
                    case 'phone':
                    case 'name':
                    default:
                        return 'comma';
                        break;
                }

            default:
                return null;
                break;
        }

        return null;
    }

    /**
     * Gets all plugin options, does not get any defaults
     *
     * Initializes options if they don't exist.
     *
     * @return array Stored plugin options
     */
    static function get_all_options() {
        $options = get_option( self::option_name );
        if ( $options === false ) {
            self::init();
            $options = get_option( self::option_name );
        }

        return $options;
    }

    /**
     * Retrieves a single option
     *
     * @param string $key Option to retrieve
     *
     * @return  mixed   Stored option or default if it does not exist
     */
    static function get_option( $key ) {
        $options = self::get_all_options();

        return isset( $options[ $key ] ) ? $options[ $key ] : self::get_default( $key );
    }

    /**
     * Updates a single option
     *
     * If $value is equal to the default for $key then the option is deleted.
     * This minimizes storage to default options, as get_option returns default after deletion.
     *
     * @param string $key Option to update
     * @param mixed $value New value to store
     */
    static function update_option( $key, $value ) {
        if ( $value == self::get_default( $key ) ) {
            self::delete_option( $key );
        } else {
            $options         = self::get_all_options();
            $options[ $key ] = $value;
            update_option( self::option_name, $options );
        }
    }

    /**
     * Deletes a single option
     *
     * @param string $key Option to delete
     */
    static function delete_option( $key ) {
        $options = self::get_all_options();
        if ( isset( $options[ $key ] ) ) {
            unset( $options[ $key ] );
            update_option( self::option_name, $options );
        }
    }

    /**
     * Gets all user options for $user_id, does not get any defaults
     *
     * Initializes user options for $user_id if the options don't exist.
     *
     * @param int $user_id User to retrieve options for
     *
     * @return  array   Stored user options
     */
    static function get_all_user_options( $user_id ) {
        $options = get_user_meta( $user_id, self::user_option_name, true );
        if ( $options == '' ) {
            self::init_user( $user_id );
            $options = get_user_meta( $user_id, self::user_option_name, true );
        }

        return $options;
    }

    /**
     * Gets a specific option for a specific user or current user
     *
     * @param string $page Page to option is related to
     * @param string $option Option to retrieve
     * @param int|null $user_id User to retrieve options for, null for current user
     *
     * @return  mixed|null  Stored user option, null if no value was found
     */
    static function get_user_option( $page, $option, $user_id = null ) {
        if ( $user_id === null ) {
            $user = wp_get_current_user();
            if ( !empty( $user ) ) {
                $user_id = $user->ID;
            }
        }

        $value = null;
        if ( $user_id !== null ) {
            $options = self::get_all_user_options( $user_id );
            $value   = isset( $options[ $page ][ $option ] ) ? $options[ $page ][ $option ] : self::get_default_user_option( $page, $option );
            if ( is_array( $value ) ) {
                $value = wp_parse_args( $value, self::get_default_user_option( $page, $option ) );
            }
        }


        return $value;
    }

    /**
     * Updates a specific option for a specific user or current user
     *
     * If $value is equal to the default user option for $option and $page then the option is deleted.
     * This minimizes storage to default options, as get_user_option returns default after deletion.
     *
     * @param string $page Page to option is related to
     * @param string $option Option to update
     * @param mixed $value New value for $option
     * @param int|null $user_id User to update options for, null for current user
     */
    static function update_user_option( $page, $option, $value, $user_id = null ) {
        if ( $user_id === null ) {
            $user = wp_get_current_user();
            if ( !empty( $user ) ) {
                $user_id = $user->ID;
            }
        }

        if ( $user_id !== null ) {
            if ( $value == self::get_default_user_option( $page, $option ) ) {
                self::delete_user_option( $page, $option, $user_id );
            } else {
                $options                     = self::get_all_user_options( $user_id );
                $options[ $page ][ $option ] = $value;
                update_user_meta( $user_id, self::user_option_name, $options );
            }
        }
    }

    /**
     * Deletes a specific option for a specific user or current user
     *
     * @param string $page Page to option is related to
     * @param string $option Option to delete
     * @param int|null $user_id User to update options for, null for current user
     */
    static function delete_user_option( $page, $option, $user_id = null ) {
        if ( $user_id === null ) {
            $user = wp_get_current_user();
            if ( !empty( $user ) ) {
                $user_id = $user->ID;
            }
        }

        if ( $user_id !== null ) {
            $options = self::get_all_user_options( $user_id );
            if ( isset( $options[ $page ][ $option ] ) ) {
                unset( $options[ $page ][ $option ] );
                update_user_meta( $user_id, self::user_option_name, $options );
            }
        }
    }

    /**
     * Deletes all options stored by this plugin
     */
    static function flush_options() {
        global $wpdb;
        delete_option( self::option_name );
        $wpdb->delete(
            $wpdb->usermeta,
            array( 'meta_key' => self::user_option_name ),
            array( '%s' )
        );
    }
}
