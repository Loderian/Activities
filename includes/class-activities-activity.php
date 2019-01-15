<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Activity class
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Activity {
  /**
   * Contains information about the activity
   *
   * @var array
   */
  private $activity;

  /**
   * Loads activity info based on id
   *
   * @param int $id Activity id
   */
  function __construct( $id ) {
    $this->activity = self::load( $id );
  }

  /**
   * Retrieve property by name, accepted input:
   *    - activity_id | id | ID
   *    - name
   *    - short_desc
   *    - long_desc
   *    - start
   *    - end
   *    - members, returns array of user_ids
   *    - location_id
   *    - responsible_id
   *    - archive
   *    - responsible, returns WP_User object
   *    - location, returns Activities_Location object
   *    - nice_settings, returns nice_settings
   *    - otherwise it searches the activity_meta table
   *
   *
   * @param   string  $name Proprety to get
   * @return  mixed   Data found for $name key, '' if not data was found
   */
  function __get( $name ) {
    if ( !is_array( $this->activity ) ) {
      return '';
    }

    global $wpdb;

    switch ($name) {
      case 'activity_id':
      case 'name':
      case 'short_desc':
      case 'long_desc':
      case 'start':
      case 'end':
      case 'members':
      case 'location_id':
      case 'responsible_id':
      case 'archive':
      case 'categories':
        return $this->activity[$name];
        break;

      case 'id':
      case 'ID':
        return $this->activity['activity_id'];
        break;

      case 'responsible':
        return new WP_User( $this->activity['responsible_id'] );
        break;

      case 'location':
        return new Activities_Location( $this->activity['location_id'] );
        break;

      case 'nice_settings':
        return self::get_nice_settings( $this->activity['activity_id'] );
        break;

      default:
        $result = self::get_meta( $this->activity['activity_id'], $name );
        if ( $result === null ) {
          $result = '';
        }
        return $result;
        break;
    }
  }

  /**
   * Add hooks related to activities
   */
  static function init() {
    add_action( 'activities_delete_location', array( __CLASS__, 'deleted_location' ) );
    add_action( 'deleted_user', array( __CLASS__, 'deleted_user' ) );
    add_action( 'remove_user_from_blog', array( __CLASS__, 'remove_user_from_blog' ), 10, 2 );
  }

  /**
   * Checks if an activiy exists
   *
   * @param   int|string  $act_id Activity identifier
   * @param   string      $check_by Column to compare ('id', 'name')
   * @return  bool        True if the activity exists, false otherwise
   */
  static function exists( $act_id, $check_by = 'id' ) {
    return Activities_Item::exists( $act_id, 'activity', $check_by );
  }

  /**
   * Checks if an activiy is archived
   *
   * @param   int|string  $act_id Activity identifier
   * @param   string      $check_by Column to compare ('id', 'name')
   * @return  bool        True if the activity is archived, false otherwise
   */
  static function is_archived( $act_id, $check_by = 'id' ) {
    global $wpdb;

    $activity_table = Activities::get_table_name( 'activity' );
    $where = Activities_Item::build_where( 'activity', $check_by );
    $archive = $wpdb->get_var( $wpdb->prepare(
      "SELECT archive
      FROM $activity_table
      WHERE $where
      ",
      $act_id
    ));

    return $archive == 1;
  }

  /**
   * Inserts activity data into the database
   *
   * @param   array     $act_map Activity info
   * @return  int|bool  False if it could not be inserted, new activity id otherwise
   */
  static function insert( $act_map ) {
    global $wpdb;

    $act = Activities_Item::insert( 'activity', $act_map );

    if ( $act ) {
      if ( array_key_exists( 'responsible_id', $act_map ) && $act_map['responsible_id'] !== null ) {
        Activities_Responsible::update_user_responsiblity( $act_map['responsible_id'] );
      }

      if ( isset( $act_map['members'] ) && is_array( $act_map['members'] ) ) {
        foreach ($act_map['members'] as $u_id) {
          Activities_User_Activity::insert( $u_id, $act );
        }
      }

      if ( isset( $act_map['categories'] ) && is_array( $act_map['categories'] ) ) {
        Activities_Category::change_category_relations( $act,  $act_map['categories'] );
      }
    }

    return $act;
  }

  /**
   * Updates activity data, requires activity_id
   *
   * @param   array     $act_map Activity info
   * @return  int|bool  False if it could not be updated, 1 otherwise
   */
  static function update( $act_map ) {
    if ( !isset( $act_map['activity_id'] ) || self::is_archived( $act_map['activity_id'] ) ) {
      return false;
    }

    $update = Activities_Item::update( 'activity', $act_map );

    if ( $update ) {
      if ( array_key_exists( 'responsible_id', $act_map ) ) {
        Activities_Responsible::remove_user_responsibility( $act_map['activity_id'] );
        if ( $act_map['responsible_id'] !== null ) {
          Activities_Responsible::update_user_responsiblity( $act_map['responsible_id'] );
        }
      }

      if ( isset( $act_map['members'] ) && is_array( $act_map['members'] ) ) {
        Activities_User_Activity::insert_delete( $act_map['members'], $act_map['activity_id'], 'activity_id' );
      }

      if ( isset( $act_map['categories'] ) && is_array( $act_map['categories'] ) ) {
        Activities_Category::change_category_relations( $act_map['activity_id'],  $act_map['categories'] );
      }
    }

    return $update;
  }

  /**
   * Duplicates an activity
   * Appends a (copy-int) to the end of the name, tries until it findes a unused name
   *
   * @param   int        $activity_id Activity id
   * @return  int|bool   New activity id, false on error
   */
  static function duplicate( $activity_id ) {
    $activity = self::load( $activity_id );

    if ( $activity !== null ) {
      unset( $activity['members'] );
      unset( $activity['activity_id'] );

      $name = $activity['name'];
      $copy = esc_html__( 'Copy', 'activities' );
      for ($i=0; $i < 10000; $i++) {
        $idx = $i == 0 ? '' : "-$i";
        $new_name = "$name ($copy$idx)";
        if ( !self::exists( $new_name, 'name' ) ) {
          $activity['name'] = $new_name;
          break;
        }
      }

      $new_act_id = self::insert( $activity );

      if ( $new_act_id ) {
        $meta = self::get_all_meta( $activity_id, false );
        foreach ($meta as $key => $value) {
          if ( $key == 'attended' ) {
            continue;
          }

          self::update_meta( $new_act_id, $key, $value, false );
        }

        return $new_act_id;
      }
    }

    return false;
  }

  /**
   * Reads activity data from the database
   *
   * @param   int         $activity_id Activity id
   * @return  array|null  Associative array of activity info, or null if error
   */
  static function load( $activity_id ) {
    global $wpdb;

    $activity_table = Activities::get_table_name( 'activity' );
    $activity = $wpdb->get_row( $wpdb->prepare(
        "SELECT *
         FROM $activity_table
         WHERE activity_id = %d
        ",
        $activity_id
      ),
      ARRAY_A
    );

    if ( $activity !== null ) {
      $user_activity = Activities::get_table_name( 'user_activity' );

      $users = $wpdb->get_col( $wpdb->prepare(
        "SELECT user_id
        FROM $user_activity
        WHERE activity_id = %d
        ",
        $activity['activity_id']
      ));

      $activity['members'] = $users;

      $activity['categories'] = Activities_Category::get_act_categories( $activity['activity_id'] );
    }

    return $activity;
  }

  /**
   * Reads activity data from the database
   *
   * @param   string                    $name Activity name
   * @return  Activities_Activity|null  Activity object, or null if error
   */
  static function load_by_name( $name ) {
    global $wpdb;

    $table_name = Activities::get_table_name( 'activity' );
    $act_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT activity_id
      FROM $table_name
      WHERE name = %s
      ",
      $name
    ));

    if ( $act_id === null ) {
      return null;
    }

    return new Activities_Activity( $act_id );
  }

  /**
   * Archives or activates an activity
   *
   * @param   int       $activity_id Activity id
   * @param   string    $reverse 'reverse' to activate activity
   * @return  int|bool  False if error or number of affected rows (0 or 1)
   */
  static function archive( $activity_id, $reverse = '' ) {
    global $wpdb;

    $table_name = Activities::get_table_name( 'activity' );

    $updated = $wpdb->update(
      $table_name,
      array( 'archive' => ($reverse == 'reverse' ? '0' : '1' ) ),
      array( 'activity_id' => $activity_id ),
      array( '%d' ),
      array( '%d' )
    );

    if ( $updated ) {
      if ( $reverse != 'reverse' ) {
        do_action( 'activities_archive_activity', $activity_id );
      }
      else {
        do_action( 'activities_activate_activity', $activity_id );
      }
    }

    return $updated;
  }

  /**
   * Deletes an archived activity
   *
   * @param   int   $activity_id Activity id
   * @param   bool  $override To skip archive check
   * @return  bool  True if the activity was deleted, false otherwise
   */
  static function delete( $activity_id, $override = false ) {
    global $wpdb;

    if ( !$override && !self::is_archived( $activity_id ) ) {
      return false;
    }

    $del = Activities_Item::delete( 'activity', $activity_id );

    if ( $del ) {
      $user_activity = Activities::get_table_name( 'user_activity' );

      $wpdb->delete(
        $user_activity,
        array( 'activity_id' => $activity_id ),
        array( '%d' )
      );

      $activities_meta = Activities::get_table_name( 'activity_meta' );
      $wpdb->delete(
        $activities_meta,
        array( 'activity_id' => $activity_id ),
        array( '%d' )
      );

      do_action( 'activities_delete_activity', $activity_id );
    }

    return $del;
  }

  /**
   * Get all metadata from an activity
   *
   * @param   int     $activity_id Activity id
   * @param   bool    $unserialize False to skip unserializeing
   * @return  array   meta_key => meta_value
   */
  static function get_all_meta( $activity_id, $unserialize = true ) {
    global $wpdb;

    $meta_table = Activities::get_table_name( 'activity_meta' );

    $meta = $wpdb->get_results( $wpdb->prepare(
      "SELECT meta_key, meta_value
      FROM $meta_table
      WHERE activity_id = %d
      ",
      array( $activity_id )
      ),
      ARRAY_A
    );

    $meta_map = array();

    foreach ($meta as $key_value) {
      $val = $key_value['meta_value'];
      if ( $unserialize && is_serialized( $val ) ) {
        $val = @unserialize( $val );
      }

      $meta_map[$key_value['meta_key']] = $val;
    }

    return $meta_map;
  }

  /**
   * Get an activity meta value
   *
   * @param   int             $activity_id Activity id
   * @param   string          $meta_key Meta key
   * @return  string|null     Meta value, null on no value
   */
  static function get_meta( $activity_id, $meta_key ) {
    global $wpdb;

    $meta_table = Activities::get_table_name( 'activity_meta' );

    $val = $wpdb->get_var( $wpdb->prepare(
      "SELECT meta_value
      FROM $meta_table
      WHERE activity_id = %d AND meta_key = %s
      ",
      array( $activity_id, $meta_key )
    ));

    if ( is_serialized( $val ) ) {
      $val = @unserialize( $val );
    }
    return $val;
  }

  /**
   * Saves an activity meta value
   *
   * @param   int       $activity_id Activity id
   * @param   string    $meta_key Meta key
   * @param   mixed     $meta_value Value to store
   * @param   bool      $serialize false to skip serializeing
   * @return  bool      False on error
   */
  static function update_meta( $activity_id, $meta_key, $meta_value, $serialize = true ) {
    global $wpdb;

    $meta_table = Activities::get_table_name( 'activity_meta' );

    if ( $serialize ) {
      $meta_value = maybe_serialize( $meta_value );
    }

    if ( self::get_meta( $activity_id, $meta_key ) === null ) {
      return $wpdb->insert(
        $meta_table,
        array( 'activity_id' => $activity_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value ),
        array( '%d', '%s', '%s' )
      );
    }
    else {
      return $wpdb->update(
        $meta_table,
        array( 'meta_value' => $meta_value ),
        array( 'activity_id' => $activity_id, 'meta_key' => $meta_key ),
        array( '%s' ),
        array( '%d', '%s' )
      ) !== false;
    }
  }

  /**
   * Deletes meta data for activities
   *
   * @param   int         $id Activity id
   * @return  int|bool    Number of rows affected (should be 1), or false on error
   */
  static function delete_meta( $activity_id, $meta_key ) {
    global $wpdb;

    $table_name = Activities::get_table_name( 'activity_meta' );

    return $wpdb->delete(
      $table_name,
      array( 'activity_id' => $activity_id, 'meta_key' => $meta_key ),
      array( '%d', '%s' )
    );
  }

  /**
   * Get an activity meta value
   *
   * @param   int       $activity_id Activity id
   * @param   string    $meta_key Meta key
   * @return  string    Meta value
   */
  static function save_nice_settings( $settings ) {
    global $wpdb;
    if ( !array_key_exists( 'activity_id', $settings ) || is_null( $settings['activity_id'] ) ) {
      return false;
    }

    $table_name = Activities::get_table_name( 'activity_meta' );

    $id = $settings['activity_id'];
    unset( $settings['activity_id'] );

    self::update_meta( $id, 'attended', $settings['attended'] );
    unset( $settings['attended'] );

    $default_settings = Activities_Options::get_option( ACTIVITIES_NICE_SETTINGS_KEY );

    if ( !is_array( $default_settings ) ) {
      $default_settings = unserialize( $default_settings );
    }

    foreach ($default_settings as $key => $value) {
      if ( $settings[$key] == $value ) {
        unset( $settings[$key] );
      }
    }

    if ( count( $settings ) === 0 ) {
      return self::delete_meta( $id, 'nice_settings' );
    }

    return self::update_meta( $id, 'nice_settings', $settings );
  }

  /**
   * Get nice settings for an activity
   * If an activity does not have a setting set, it will use the default set in the options window
   *
   * @param   int      $id Activity id
   * @return  array    Nice Settings
   */
  static function get_nice_settings( $id ) {
    if ( $id <= 0 ) {
      return false;
    }
    global $wpdb;

    $table_name = Activities::get_table_name( 'activity_meta' );

    $settings = self::get_meta( $id, 'nice_settings' );

    if ( $settings === null ) {
      return false;
    }

    $default_settings = Activities_Options::get_option( ACTIVITIES_NICE_SETTINGS_KEY );

    if ( !is_array( $default_settings ) ) {
      $default_settings = unserialize( $default_settings );
    }

    foreach ($default_settings as $key => $value) {
      if ( !array_key_exists( $key, $settings ) ) {
        $settings[$key] = $value;
      }
    }

    $settings['attended'] = self::get_meta( $id, 'attended' );

    return $settings;
  }

  /**
   * Callback for user deletion
   *
   * Removes user from responsible_id fields on activities
   *
   * @param   int   $user_id User id
   */
  static function deleted_user( $user_id ) {
    global $wpdb;

    $wpdb->update(
      Activities::get_table_name( 'activity' ),
      array( 'responsible_id' => null ),
      array( 'responsible_id' => $user_id ),
      array( '%d' ),
      array( '%d' )
    );
  }

  /**
   * Callback for blog user deletion
   *
   * Switches to $blog_id and removes user from responsible_id fields on activities
   *
   * @param   int   $user_id User id
   * @param   int   $blog_id Blog id
   */
  static function remove_user_from_blog( $user_id, $blog_id ) {
    if ( is_multisite() ) {
      switch_to_blog( $blog_id );
    }

    global $wpdb;
    $activity_table = Activities::get_table_name( 'activity' );

    if ( $wpdb->get_var( "SHOW TABLES LIKE '$activity_table'") == $activity_table ) {
      self::deleted_user( $user_id );
    }

    if ( is_multisite() ) {
      restore_current_blog();
    }
  }

  /**
   * Callback for location deletion
   *
   * Removes location from location_id fields on activities
   *
   * @param   int   $loc_id Location id
   */
  static function deleted_location( $loc_id ) {
    global $wpdb;

    $wpdb->update(
      Activities::get_table_name( 'activity' ),
      array( 'location_id' => null ),
      array( 'location_id' => $loc_id ),
      array( '%d' ),
      array( '%d' )
    );
  }

  /**
   * Get all the columns uses to store activities
   *
   * @param   string    $type Column types to get ('string' or 'int')
   * @return  array     Array of column names
   */
  static function get_columns( $type = 'none' ) {
    $strings = array( 'name', 'short_desc', 'long_desc', 'start', 'end' );
    $ints = array( 'responsible_id', 'location_id', 'archive' );

    switch ($type) {
      case 'string':
      case 'strings':
        return $strings;
        break;

      case 'int':
      case 'ints':
        return $ints;
        break;

      case 'none':
      default:
        return array_merge( $strings, $ints );
        break;
    }
  }

  /**
   * Builds where cluase for sql queries
   *
   * @param   string  $check_by Column find activity by
   * @return  string  String to use in where clause in sql
   */
  static function build_where( $check_by = 'id' ) {
    switch ($check_by) {
      case 'name':
        $where = 'name = %s';
        break;

      case 'id':
      default:
        $where = 'activity_id = %d';
        break;
    }

    return $where;
  }
}
Activities_Activity::init();
