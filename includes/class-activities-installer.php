<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Installs everything the plugin needs to run
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Installer {

    /**
     * Setup before installing
     */
    public function __construct() {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    }

    /**
     * Installs all required tables for this plugin
     */
    public function install_all_default_tables() {
        $installed_ver = get_option( 'activities_db_version' );
        if ( !$installed_ver ) {
            $installed_ver = '0.0.0';
        }
        if ( version_compare( $installed_ver, '1.0.0' ) < 0 ) {
            $this->install_location_table();
            $this->install_activity_table();
            $this->install_user_activity_table();
            $this->install_activity_meta_table();
            $this->install_plans_table();
            $this->install_plans_session_table();

            //Version work in progress
            if ( version_compare( ACTIVITIES_DB_VERSION, '1.2.0' ) >= 0 ) {
                $this->install_participant_table();
                $this->install_participant_meta_table();
                $this->install_participant_activity_table();
            }

            update_option( 'activities_db_version', ACTIVITIES_DB_VERSION );
        }
    }

    /**
     * Adds default capabilities to page admin
     */
    public function add_capabilities() {
        global $wp_roles;

        $wp_roles->add_cap( 'administrator', ACTIVITIES_ACCESS_ACTIVITIES );
        $wp_roles->add_cap( 'administrator', ACTIVITIES_ADMINISTER_ACTIVITIES );
        $wp_roles->add_cap( 'administrator', ACTIVITIES_ADMINISTER_OPTIONS );
    }

    /**
     * Installs activity table
     */
    public function install_activity_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'activity' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_activity = "
            CREATE TABLE $table_name (
            activity_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(180) NOT NULL UNIQUE,
            short_desc tinytext DEFAULT '' NOT NULL,
            long_desc text DEFAULT '' NOT NULL,
            location_id bigint(20) UNSIGNED,
            start datetime DEFAULT NULL,
            end datetime DEFAULT NULL,
            responsible_id bigint(20) UNSIGNED,
            archive boolean DEFAULT 0 NOT NULL,
            plan_id bigint(20) UNSIGNED,
            PRIMARY KEY  (activity_id),
            KEY activity_res (responsible_id),
            KEY activity_loc (location_id),
            KEY activity_arc (archive),
            KEY activity_plan (plan_id)) 
            $charset_collate;";

        dbDelta( $sql_activity );
    }

    /**
     * Installs user activity table (members)
     */
    public function install_user_activity_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'user_activity' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_user_activity = "
            CREATE TABLE $table_name (
            user_id bigint(20) UNSIGNED NOT NULL,
            activity_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (user_id,activity_id)) 
            $charset_collate;";

        dbDelta( $sql_user_activity );
    }

    /**
     * Installs location table
     */
    public function install_location_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'location' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_location = "
            CREATE TABLE $table_name (
            location_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(180) NOT NULL UNIQUE,
            address varchar(255) DEFAULT '' NOT NULL,
            postcode varchar(12) DEFAULT '' NOT NULL,
            city varchar(100) DEFAULT '' NOT NULL,
            description text DEFAULT '' NOT NULL,
            country varchar(2) DEFAULT '' NOT NULL,
            PRIMARY KEY  (location_id),
            KEY location_add (address)) 
            $charset_collate;";

        dbDelta( $sql_location );
    }

    /**
     * Installs activity meta table
     */
    public function install_activity_meta_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'activity_meta' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_activity_meta = "
            CREATE TABLE $table_name (
            ameta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            activity_id bigint(20) UNSIGNED NOT NULL,
            meta_key varchar(255) DEFAULT NULL,
            meta_value longtext DEFAULT NULL,
            PRIMARY KEY  (ameta_id),
            KEY activity_id (activity_id),
            KEY meta_key (meta_key)) 
            $charset_collate;";

        dbDelta( $sql_activity_meta );
    }

    /**
     * Installs plans table
     */
    public function install_plans_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'plan' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_plan = "
            CREATE TABLE $table_name (
            plan_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(180) NOT NULL UNIQUE,
            description text DEFAULT '' NOT NULL,
            sessions smallint(5) NOT NULL,
            PRIMARY KEY  (plan_id)) 
            $charset_collate;";

        dbDelta( $sql_plan );
    }

    /**
     * Installs plans table
     */
    public function install_plans_session_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'plan_session' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_plan_slot = "
            CREATE TABLE $table_name (
            plan_id bigint(20) UNSIGNED NOT NULL,
            session_id smallint(5) NOT NULL,
            text text DEFAULT '' NOT NULL,
            PRIMARY KEY  (plan_id,session_id)) 
            $charset_collate;";

        dbDelta( $sql_plan_slot );
    }

    /**
     * Installs participant table
     */
    public function install_participant_table() {
        global $wpdb;

        $table_name = Activities::get_table_name('participant' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_user = "
            CREATE TABLE $table_name (
            user_id bigint(20) UNSIGNED NOT NULL,
            first_name varchar(50) DEFAULT '',
            last_name varchar(50) DEFAULT '',
            email varchar(50) DEFAULT '',
            wp_user bigint(20) DEFAULT NULL,
            PRIMARY KEY  (user_id))
            $charset_collate;";

        dbDelta( $sql_user );
    }

    /**
     * Installs activity meta table
     */
    public function install_participant_meta_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'participant_meta' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_participant_meta = "
            CREATE TABLE $table_name (
            umeta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            meta_key varchar(255) DEFAULT NULL,
            meta_value longtext DEFAULT NULL,
            PRIMARY KEY  (umeta_id),
            KEY activity_id (user_id),
            KEY meta_key (meta_key)) 
            $charset_collate;";

        dbDelta( $sql_participant_meta );
    }

    /**
     * Installs participant activity table (custom members)
     */
    public function install_participant_activity_table() {
        global $wpdb;

        $table_name = Activities::get_table_name( 'participant_activity' );

        $charset_collate = $wpdb->get_charset_collate();

        $sql_participant_activity = "
            CREATE TABLE $table_name (
            user_id bigint(20) UNSIGNED NOT NULL,
            activity_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (user_id,activity_id)) 
            $charset_collate;";

        dbDelta( $sql_participant_activity );
    }
}
