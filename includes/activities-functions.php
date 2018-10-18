<?php

/**
 * Functions
 *
 * @since      1.0.1
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Sanitizes and validates an item id
 *
 * @param   int $id Id
 * @return  int $id if validated, otherwise 0
 */
function acts_validate_id( $id ) {
  $id = sanitize_key( $id );
  if ( is_numeric( $id ) ) {
    $id = intval( $id );
    if ( $id < 0 ) {
      return 0;
    }
    else {
      return $id;
    }
  }
  else {
    return 0;
  }
}
