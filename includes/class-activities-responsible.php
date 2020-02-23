<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Responsible control class
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Responsible {
    /**
     * Adds action related to responsible users
     */
    static function init() {
        add_action( 'activities_archive_activity', array( __CLASS__, 'remove_user_responsibility' ) );
        add_action( 'activities_activate_activity', array( __CLASS__, 'restore_user_responsibility' ) );
    }

    /**
     * Sets responsibility restriction on a user
     *
     * @param int $user_id User to set restriction on
     * @param string $key Restriction key
     */
    static function set_user_responsibility( $user_id, $key ) {
        $current             = self::get_user_responsibility( $user_id, $key );
        $blog_id             = get_current_blog_id();
        $current[ $blog_id ] = 1;
        update_user_meta( $user_id, $key, $current );
    }

    /**
     * Deletes responsibility restriction on a user
     *
     * @param int $user_id User to delete restriction for
     * @param string $key Restriction key
     */
    static function delete_user_responsibility( $user_id, $key ) {
        $current = self::get_user_responsibility( $user_id, $key );
        $blog_id = get_current_blog_id();
        if ( isset( $current[ $blog_id ] ) ) {
            unset( $current[ $blog_id ] );
            update_user_meta( $user_id, $key, $current );
        }
    }

    /**
     * Retrieve responsibility restriction thats on a user
     *
     * @param int $user_id User to retrieve data for
     * @param string $key Restriction key
     *
     * @return  array   Array mapping for which site the user has restriction, site_id => 1
     */
    static function get_user_responsibility( $user_id, $key ) {
        $current = get_user_meta( $user_id, $key, true );
        if ( $current == '' ) {
            $current = array();
        }

        return $current;
    }

    /**
     * Checks if a user has responsibility restricted permissions
     *
     * @param int $user_id User to retrieve data for
     * @param string $key Restriction key
     * @param int $blog_id Blog to check on, defaults to current blog
     *
     * @return  bool    True if restricted, false otherwise
     */
    static function user_restricted( $user_id, $key, $blog_id = -1 ) {
        $current = self::get_user_responsibility( $user_id, $key );
        if ( $blog_id === -1 ) {
            $blog_id = get_current_blog_id();
        }
        if ( isset( $current[ $blog_id ] ) ) {
            return $current[ $blog_id ] == 1;
        } else {
            return false;
        }
    }

    /**
     * Checks if a user has restricted view permission
     *
     * @param int $user_id User to retrieve data for
     *
     * @return  bool    True if restricted, false otherwise
     */
    static function user_restricted_view( $user_id ) {
        return self::user_restricted( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
    }

    /**
     * Checks if a user has restricted edit permission
     *
     * @param int $user_id User to retrieve data for
     *
     * @return  bool    True if restricted, false otherwise
     */
    static function user_restricted_edit( $user_id ) {
        return self::user_restricted( $user_id, ACTIVITIES_RESPONSIBLE_EDIT );
    }

    /**
     * Checks if current user has restricted view permission
     *
     * @return  bool  True if restricted, false otherwise
     */
    static function current_user_restricted_view() {
        return self::user_restricted_view( get_current_user_id() );
    }

    /**
     * Checks if current user has restricted edit permission
     *
     * @return  bool  True if restricted, false otherwise
     */
    static function current_user_restricted_edit() {
        return self::user_restricted_edit( get_current_user_id() );
    }

    /**
     * Adds user access to responsible users depending on activities settings and wp roles
     * This should not add the ACTIVITIES_ADMINISTER_ACTIVITIES capability as it grants more permissions than it should to the responsible user,
     * and should only be granted by role permissions.
     *
     * @param int $user_id
     */
    static function update_user_responsiblity( $user_id ) {
        $user = new WP_User( $user_id );

        switch ( Activities_Options::get_option( ACTIVITIES_RESPONSIBLE_KEY ) ) {
            case ACTIVITIES_RESPONSIBLE_SAME:
                $user->remove_cap( ACTIVITIES_ACCESS_ACTIVITIES );
                self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
                self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_EDIT );
                break;

            case ACTIVITIES_RESPONSIBLE_VIEW:
                $role_access = self::get_user_role_override( $user );
                if ( !$role_access[ ACTIVITIES_ACCESS_ACTIVITIES ] ) {
                    $user->add_cap( ACTIVITIES_ACCESS_ACTIVITIES );
                    self::set_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
                } else {
                    $user->remove_cap( ACTIVITIES_ACCESS_ACTIVITIES );
                    self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
                }
                delete_user_meta( $user_id, ACTIVITIES_RESPONSIBLE_EDIT );
                break;

            case ACTIVITIES_RESPONSIBLE_EDIT:
                $role_access = self::get_user_role_override( $user );
                if ( !$role_access[ ACTIVITIES_ACCESS_ACTIVITIES ] ) {
                    $user->add_cap( ACTIVITIES_ACCESS_ACTIVITIES );
                    self::set_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
                } else {
                    $user->remove_cap( ACTIVITIES_ACCESS_ACTIVITIES );
                    self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
                }
                if ( !$role_access[ ACTIVITIES_ADMINISTER_ACTIVITIES ] ) {
                    self::set_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_EDIT );
                } else {
                    self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_EDIT );
                }
                break;
        }
    }


    /**
     * Removes all responsibility from a user if they no longer has any responsibility over activities
     *
     * @param int $activity_id Activity getting a new responsible user
     */
    static function remove_user_responsibility( $activity_id ) {
        global $wpdb;

        $table_name = Activities::get_table_name( 'activity' );

        $rep_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT responsible_id
      FROM $table_name
      WHERE activity_id = %d
      ",
            $activity_id
        ) );

        if ( $rep_id === null ) {
            return;
        }

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(responsible_id)
      FROM $table_name
      WHERE responsible_id = %d
      AND archive = 0
      ",
            $rep_id
        ) );

        if ( $count <= 1 ) {
            $prev_user = new WP_User( $rep_id );
            $prev_user->remove_cap( ACTIVITIES_ACCESS_ACTIVITIES );
            self::delete_user_responsibility( $rep_id, ACTIVITIES_RESPONSIBLE_VIEW );
            self::delete_user_responsibility( $rep_id, ACTIVITIES_RESPONSIBLE_EDIT );
        }
    }

    /**
     * Restores all responsibility from a user if they have more than 1 activity they are responsible for
     *
     * @param int $activity_id Activity getting a new responsible user
     */
    static function restore_user_responsibility( $activity_id ) {
        global $wpdb;

        $table_name = Activities::get_table_name( 'activity' );

        $rep_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT responsible_id
      FROM $table_name
      WHERE activity_id = %d
      ",
            $activity_id
        ) );

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(responsible_id)
      FROM $table_name
      WHERE responsible_id = %d
      AND archive = 0
      ",
            $rep_id
        ) );

        if ( $count >= 1 ) {
            self::update_user_responsiblity( $rep_id );
        }
    }

    /**
     * Updates all users responsibilities
     */
    static function update_all_users_responsiblity() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'activity' );

        $users = $wpdb->get_results(
            "SELECT DISTINCT responsible_id
      FROM $table_name
      WHERE archive = 0
      "
        );

        foreach ( $users as $id ) {
            if ( $id->responsible_id === null ) {
                continue;
            }
            self::update_user_responsiblity( $id->responsible_id );
        }
    }

    /**
     * Removes all users responsibilities
     */
    static function remove_all_users_responsiblity() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'activity' );

        $users = $wpdb->get_col(
            "SELECT DISTINCT responsible_id
      FROM $table_name
      WHERE archive = 0
      "
        );

        foreach ( $users as $user_id ) {
            $user = new WP_User( $user_id );
            $user->remove_cap( ACTIVITIES_ACCESS_ACTIVITIES );
            self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_VIEW );
            self::delete_user_responsibility( $user_id, ACTIVITIES_RESPONSIBLE_EDIT );
        }
    }

    /**
     * Checks if the user role overrides responsibility access
     *
     * @param WP_User $user
     *
     * @return   array   ACTIVITIES_ACCESS_ACTIVITIES and ACTIVITIES_ADMINISTER_ACTIVITIES to true if role has the capability, otherwise false
     */
    static function get_user_role_override( $user ) {
        global $wp_roles;

        $access = array(
            ACTIVITIES_ACCESS_ACTIVITIES     => false,
            ACTIVITIES_ADMINISTER_ACTIVITIES => false
        );
        foreach ( $user->roles as $role ) {
            $caps = $wp_roles->get_role( $role )->capabilities;
            if ( isset( $caps[ ACTIVITIES_ACCESS_ACTIVITIES ] ) && !$access[ ACTIVITIES_ACCESS_ACTIVITIES ] ) {
                $access[ ACTIVITIES_ACCESS_ACTIVITIES ] = $caps[ ACTIVITIES_ACCESS_ACTIVITIES ];
            }
            if ( isset( $caps[ ACTIVITIES_ADMINISTER_ACTIVITIES ] ) && !$access[ ACTIVITIES_ADMINISTER_ACTIVITIES ] ) {
                $access[ ACTIVITIES_ADMINISTER_ACTIVITIES ] = $caps[ ACTIVITIES_ADMINISTER_ACTIVITIES ];
            }
            if ( $access[ ACTIVITIES_ACCESS_ACTIVITIES ] && $access[ ACTIVITIES_ADMINISTER_ACTIVITIES ] ) {
                break;
            }
        }

        return $access;
    }
}

Activities_Responsible::init();
