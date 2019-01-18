<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Organized bulk actions
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Bulk_Action {
  /**
   * Number of successful changes in a bulk action
   *
   * @var int
   */
  private $succ;

  /**
   * Init 0 successful changes
   */
  function __construct() {
    $this->succ = 0;
  }

  /**
   * Archive one or more activities
   *
   * @param array $acts List of activities ids
   */
  public function archive_activities( $acts ) {
    foreach ($acts as $id) {
      if ( Activities_Activity::archive( $id ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities has been archived' ), $this->succ ) );
  }

  /**
   * Change location for one or more activities
   *
   * @param array   $acts List of activities ids
   * @param int     $loc New location id for activities
   */
  public function change_locations( $acts, $loc ) {
    $loc = acts_validate_id( $loc );
    if ( !$loc ) {
      $loc = null;
    }
    foreach ($acts as $id) {
      if ( Activities_Activity::update( array( 'activity_id' => $id, 'location_id' => $loc  ) ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities had their location changed.', 'activities' ), $this->succ ) );
  }

  /**
   * Change responsible user for one or more activities
   *
   * @param array   $acts List of activities ids
   * @param int     $res New responsible user id user for activities
   */
  public function change_responsible_users( $acts, $res ) {
    $res = acts_validate_id( $res );
    if ( !$res ) {
      $res = null;
    }
    foreach ($acts as $id) {
      if ( Activities_Activity::update( array( 'activity_id' => $id, 'responsible_id' => $res ) ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities had their responsible person changed.', 'activities' ), $this->succ ) );
  }

  /**
   * Change members for one or more activities
   *
   * @param array   $acts List of activities ids
   * @param array   $members List of user ids
   * @param string  $method How to save the members list to the activities
   */
  public function change_members( $acts, $members, $method ) {
    switch ( $method ) {
      case 'replace':
        foreach ($acts as $id) {
          if ( Activities_User_Activity::insert_delete( $members, $id, 'activity_id') ) {
            $this->succ++;
          }
        }
        break;

      case 'add':
        foreach ($acts as $a_id) {
          $changed = false;
          foreach ($members as $u_id) {
            if ( Activities_User_Activity::insert( $u_id, $a_id ) ) {
              $changed = true;
            }
          }
          if ( $changed ) {
            $this->succ++;
          }
        }
        break;

      case 'remove':
        foreach ($acts as $a_id) {
          $changed = false;
          foreach ($members as $u_id) {
            if ( Activities_User_Activity::delete( $u_id, $a_id ) ) {
              $changed = true;
            }
          }
          if ( $changed ) {
            $this->succ++;
          }
        }
        break;
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d activities had their members changed.', 'activities' ), $this->succ ) );
  }

  /**
   * Activates one or more archived activities
   *
   * @param array   $acts List of activities ids
   */
  public function activate_activities( $acts ) {
    foreach ($acts as $id) {
      if ( Activities_Activity::archive( $id, 'reverse' ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d activites has been activated.', 'activities' ), $this->succ ) );
  }

  /**
   * Deletes one or more archived activities
   *
   * @param array $acts List of activities ids
   */
  public function delete_activities( $acts ) {
    foreach ($acts as $id) {
      if ( Activities_Activity::delete( $id ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d activites has been deleted.', 'activities' ), $this->succ ) );
  }

  /**
   * Changes address for one or more locations
   *
   * @param array   $locs List of locations ids
   * @param string  $addr New address
   */
  public function change_address( $locs, $addr ) {
    foreach ($locs as $location_id) {
      if ( Activities_Location::update( array( 'location_id' => $location_id, 'address' => $addr ) ) ){
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d locations had their address changed.', 'activities' ), $this->succ ) );
  }

  /**
   * Deletes one or more locations
   *
   * @param array $locs List of locations ids
   */
  public function delete_locations( $locs ) {
    foreach ($locs as $id) {
      if ( Activities_Location::delete( $id ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d locations has been deleted.', 'activities' ), $this->succ ) );
  }

  /**
   * Deletes one or more plans
   *
   * @param array $locs List of locations ids
   */
  public function delete_plans( $plans ) {
    foreach ($plans as $id) {
      if ( Activities_Plan::delete( $id ) ) {
        $this->succ++;
      }
    }

    Activities_Admin::add_success_message( sprintf( esc_html__( '%d plans has been deleted.', 'activities' ), $this->succ ) );
  }
}
