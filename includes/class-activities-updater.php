<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Checks version number and updates
 *
 * @since      1.0.5
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Updater {
    /**
     * List of updates
     *
     * @var array
     */
    static $db_updates = array(
        '1.0.1' => array( __CLASS__, 'db_update_1_0_1' ),
        '1.1.0' => array( __CLASS__, 'db_update_1_1_0' ),
        '1.1.1' => array( __CLASS__, 'db_update_1_1_1' )
        //'1.2.0' => array( __CLASS__, 'db_update_1_2_0' ) //TODO Add version when update when new tables are "ready"
    );

    static function init() {
        add_action( 'plugins_loaded', array( __CLASS__, 'update' ) );
    }

    /**
     * Update to the newest version
     */
    static function update() {
        require_once dirname( __FILE__ ) . '/class-activities-installer.php';

        $installed_ver = get_option( 'activities_db_version' );
        if ( version_compare( $installed_ver, ACTIVITIES_DB_VERSION ) >= 0 ) {
            return;
        }

        foreach ( self::$db_updates as $update_ver => $callback ) {
            if ( version_compare( $update_ver, $installed_ver ) > 0 ) {
                if ( call_user_func( $callback ) !== false ) {
                    update_option( 'activities_db_version', $update_ver );
                    $installed_ver = $update_ver;
                } else {
                    //If an update was unsuccessful, try again later
                    return;
                }
            }
        }
    }

    /**
     * Update db to version 1.0.1
     *
     * @return bool Returns true on successful update
     */
    static function db_update_1_0_1() {
        return Activities_Category::add_uncategorized();
    }

    /**
     * Update db to version 1.1.0
     *
     * @return bool Returns true on successful update
     */
    static function db_update_1_1_0() {
        global $wpdb;

        try {
            $acts_table = Activities::get_table_name( 'activity' );
            $wpdb->query( "ALTER TABLE $acts_table MODIFY COLUMN start datetime DEFAULT NULL;" );
            $wpdb->query( "ALTER TABLE $acts_table MODIFY COLUMN end datetime DEFAULT NULL;" );
            $wpdb->query( "ALTER TABLE $acts_table MODIFY COLUMN name VARCHAR(180) NOT NULL;" );
            $wpdb->query( "ALTER TABLE $acts_table ADD plan_id bigint(20) UNSIGNED;" );
            $wpdb->query( "ALTER TABLE $acts_table ADD INDEX activity_plan (plan_id);" );

            $locs_table = Activities::get_table_name( 'location' );
            $wpdb->query( "ALTER TABLE $locs_table DROP INDEX location_name;" );
            $wpdb->query( "ALTER TABLE $locs_table MODIFY COLUMN name varchar(180) NOT NULL UNIQUE;" );

            $installer = new Activities_Installer();
            $installer->install_plans_table();
            $installer->install_plans_session_table();
        } catch ( Exception $e ) {
            return false;
        }

        return true;
    }

    /**
     * Update db to version 1.1.1
     *
     * Some tables where not installed correctly for earlier versions of the plugin
     *
     * @return bool Returns true on successful update
     */
    static function db_update_1_1_1() {
        try {
            $installer = new Activities_Installer();
            $installer->install_activity_table();
            $installer->install_plans_table();
            $installer->install_plans_session_table();
        } catch ( Exception $e ) {
            return false;
        }

        return true;
    }

    /**
     * Update db to version 1.2.0
     *
     * @return bool Returns true on successful update
     */
    static function db_update_1_2_0() {
        try {
            $installer = new Activities_Installer();
            $installer->install_participant_table();
            $installer->install_participant_meta_table();
            $installer->install_participant_activity_table();
        } catch ( Exception $e ) {
            return false;
        }

        return true;
    }
}

Activities_Updater::init();
