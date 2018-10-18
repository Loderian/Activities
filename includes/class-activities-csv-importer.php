<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Handles reading of CSV files
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_CSV_Importer {
  /**
   * Filepointer, false on error
   *
   * @var resource|bool
   */
  private $fp;

  /**
   * CSV Header Information
   *
   * @var array
   */
  private $header;

  /**
   * CSV dilimiter
   *
   * @var string
   */
  private $dilimiter;

  /**
   * Filepointer based on $file_name, sets $dilimiter and reads header of the CSV file.
   * Sets auto_detect_line_endings to true
   *
   * @param   string  $file_name Name of the file to open
   * @param   string  $dilimiter Char to split CSV lines
   */
  function __construct( $file_name, $dilimiter ) {
    ini_set( 'auto_detect_line_endings', true );
    $this->fp = fopen( $file_name, 'r' );
    $this->dilimiter = $dilimiter;

    $this->header = fgetcsv( $this->fp, 0, $this->dilimiter );
  }

  /**
   * Closes the filepointer
   * Sets auto_detect_line_endings to false
   */
  function __destruct() {
    if ( $this->fp ) {
      fclose( $this->fp );
    }
    ini_set( 'auto_detect_line_endings', false );
  }

  /**
   * Get CSV header
   *
   * @param array Array of strings containing header names
   */
  public function get_header() {
    return $this->header;
  }

  /**
   * Get rows from the CSV file
   *
   * @param   int     $max_lines Max amount of lines to get, 0 for infinite
   * @return  array   Array $max_lines rows, with header => data mapping
   */
  function get_rows( $max_lines = 0 ) {
    $data = array();

    if ( $max_lines > 0 ) {
      $line_count = 0;
    }
    else {
      $line_count = -1;
    }

    while ( $line_count < $max_lines ) {
      $row = fgetcsv( $this->fp, 0, $this->dilimiter );
      if ( $row === false ) {
        break;
      }

      $new_row = array();
      foreach ($this->header as $i => $heading_i) {
        $new_row[$heading_i] = $row[$i];
      }
      $data[] = $new_row;

      if ($max_lines > 0) {
        $line_count++;
      }
    }

    return $data;
  }
}
