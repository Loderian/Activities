<?php

/**
 * Validates $id and, loads the activity
 *
 * @param   int         $id Activtiy identifier
 * @return  array|null  Returns an activity or null if not found/valid
 */
function acts_get_act( $id ) {
  if ( acts_validate_id( $id ) ) {
    return Activities_Activity::load( $id );
  }

  return null;
}

/**
 * Validates $id and, loads the location
 *
 * @param   int         $id Location identifier
 * @return  array|null  Returns a location or null if not found/valid
 */
function acts_get_loc( $id ) {
  if ( acts_validate_id( $id ) ) {
    return Activities_Location::load( $id );
  }

  return null;
}

/**
 * Validates an item id
 *
 * @param   int $id Id
 * @return  int $id if validated, otherwise 0
 */
function acts_validate_id( $id ) {
  $id = sanitize_text_field( $id );
  if ( is_numeric( $id ) ) {
    return $id;
  }
  else {
    return 0;
  }
}
