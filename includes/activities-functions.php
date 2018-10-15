<?php

/**
 * Sanitizes and validates an item id
 *
 * @param   int $id Id
 * @return  int $id if validated, otherwise 0
 */
function acts_validate_id( $id ) {
  $id = sanitize_key( $id );
  if ( is_numeric( $id ) ) {
    return intval( $id );
  }
  else {
    return 0;
  }
}
