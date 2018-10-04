<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Echoes page for selecting import
 */
function activities_import_page() {
  if ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
    wp_die( esc_html__( 'Access Denied', 'activities' ) );
  }
  
  if ( isset( $_GET['type'] ) && ( $_GET['type'] === 'activities' || $_GET['type'] == 'members' ) ) {
    activities_import_page_selected( $_GET['type'] );
    return;
  }
  echo '<h1>' . esc_html__( 'Select Importer', 'activities' ) . '</h1>';
  $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

  echo '<table id="acts-import-table" class="activities-box-wrap">';
  echo '<tbody>';
  echo '<tr>';
  echo '<td>' . esc_html__( 'Activities', 'activities' ) . '</br>';
  echo '<a href="' . esc_url( $current_url . '&type=activities' ) . '" >' . esc_html__( 'Run Importer', 'activities' ) . '</a></td>';
  echo '<td>' . esc_html__( 'Import activities from a CSV file.', 'activities' ) . '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>' . esc_html__( 'Participants', 'activities' ) . '</br>';
  echo '<a href="' . esc_url( $current_url . '&type=members' ) . '" >' . esc_html__( 'Run Importer', 'activities' ) . '</a></td>';
  echo '<td>' . esc_html__( 'Import activities and participants from a CSV file.', 'activities' ) . '</td>';
  echo '</tr>';
  echo '</tbody>';
  echo '</table>';
}

/**
 * Builds page for uploading import file, mapping data and importing
 *
 * @param   string  $type Type of import selected
 * @return  string  Page for doing import
 */
function activities_import_page_selected( $type = 'activities' ) {
  if ( $type == 'activities') {
    echo '<h1>' . esc_html__( 'Activities Import', 'activities' ) . '</h1>';
  }
  elseif ( $type == 'members' ) {
    echo '<h1>' . esc_html__( 'Participants Import', 'activities' ) . '</h1>';
  }
  else {
    echo '<h1>' . esc_html__( 'No Importer Selected', 'activities' ) . '</h1>';
  }

  if ( ( isset( $_POST['map_activity_data'] ) || isset( $_POST['map_member_data'] )) && isset( $_FILES['import_file'] ) ) {
    if ( $_FILES['import_file']['error'] != UPLOAD_ERR_OK ) {
      Activities_Admin::add_error_message( activities_file_upload_error_message($_FILES['import_file']['error']));
    }
    else {
      if ( validate_file( $_FILES['import_file']['name'] ) === 0 ) {
        $filepath = wp_upload_dir()['path'] . '/' . $_FILES['import_file']['name'];
        move_uploaded_file( $_FILES['import_file']['tmp_name'], $filepath );
        if ( isset( $_POST['map_activity_data'] ) ) {
          if ( activities_import_activity_mapping( $filepath ) ) {
            return;
          }
        }
        else {
          if ( activities_import_member_mapping( $filepath ) ) {
            return;
          }
        }
      }
      else {
        Activities_Admin::add_error_message( esc_html__( 'Could not validate file.', 'activities' ) );
      }
    }
  }
  else if ( ( isset( $_POST['import_activity_data'] ) || isset( $_POST['import_member_data'] ) ) && isset( $_POST['map'] ) && isset( $_POST['filepath'] ) ) {
    $selected_columns = array();
    $mapping = $_POST['map'];
    foreach ($_POST['map'] as $column => $header ) {
      if ( isset( $selected_columns[$column] ) ) {
        Activities_Admin::add_error_message( esc_html__( 'Two headers cannot be mapped to the same column.', 'activities' ) );
        if ( isset( $_POST['import_activity_data'] ) ) {
          activities_import_activity_mapping( $_POST['filepath'], $_POST['map'] );
        }
        else {
          activities_import_member_mapping( $_POST['filepath'], $_POST['map'] );
        }
        return;
      }
      if ( $header === 'null' ) {
        unset( $mapping[$column] );
      }
      else {
        $selected_columns[$column] = 1;
      }
    }

    if ( isset( $_POST['import_activity_data'] ) ) {
      activities_import_acts( $_POST['filepath'], $mapping, isset( $_POST['archive_activities'] ), isset( $_POST['update_activities'] ) );
    }
    else {
      activities_import_members( $_POST['filepath'], $mapping, isset( $_POST['create_activities'] ), isset( $_POST['archive_activities'] ) );
    }

    return;
  }

  switch ($type) {
    case 'activities':
      $text =
        sprintf( esc_html__( 'To import activities the file has to have one header called %s or %s.', 'activities' ), 'name', 'activity_name' ) . '</br>' .
        esc_html__( 'Other headers can be called whatever you want, you get to map them in the next step.', 'activities' ) . '</br>' .
        sprintf( esc_html__( 'The data is expected to be delimited by a semicolon %s. Order does not matter.', 'activities' ), '(;)' ) . '</br>' .
        esc_html__( 'You can archive imported activities by selecting the option for it during the next step.', 'activities' ) . '</br>' .
        esc_html__( 'Example CSV data', 'activities' ) . ':</br>' .
        "\nname; short; long; loction; responsible; start; end
        name of activity; short description; long description; location name; responsible@email.com; start date; end date
        name of another activity; short; ; another location; responsible2@email.com; ;
        ";
      $button = get_submit_button( esc_html__( 'Next', 'activities' ), 'button-primary', 'map_activity_data' );
      break;

    case 'members':
      $text =
        esc_html__( 'To import participants and activities the file must have exactly two headers.', 'activities' ) . '</br>' .
        esc_html__( 'Headers can be called whatever you want, you get to map them in the next step.', 'activities' ) . '</br>' .
        sprintf( esc_html__( 'The data is expected to be delimited by a semicolon %s. Order does not matter.', 'activities' ), '(;)' ) . '</br>' .
        esc_html__( 'You can have multiple activities and participants in the two columns.', 'activities' ) . '</br>' .
        esc_html__( 'You can create and archive imported activities by selecting the option for it during the next step.', 'activities' ) . '</br>' .
        esc_html__( 'Example CSV data', 'activities' ) . ':</br>' .
        "\nusers; activities
        user@email.com; activity1, activity2, activity3
        user2@email.com, user3@email.com; activity3, activity5
        user3@email.com, user@email.com; activity2
        ";
      $button = get_submit_button( esc_html__( 'Next', 'activities' ), 'button-primary', 'map_member_data' );
      break;

    default;
      $text = esc_html__( 'No importer selected.', 'activities' );
      $button = '';
      break;
  }

  echo Activities_Admin::get_messages();

  echo '<form method="post" enctype="multipart/form-data">';
  echo '<p style="white-space: pre-line;">';
  echo $text;
  echo '</p>';
  echo '<label for="acts-upload-file"><b>' . esc_html__( 'Upload File', 'activities' ) . '</b></label></br>';
  echo '<input type="file" name="import_file" id="acts-upload-file" />';
  echo '<p>';
  echo $button;
  echo '</p>';
  echo '</form>';
}

/**
 * Echoes page for activity import mapping
 *
 * @param string  $filepath Path to the import file
 * @param array   $map Values for mapping fields
 */
function activities_import_activity_mapping( $filepath, $map = array() ) {
  echo Activities_Admin::get_messages();
  $importer = new Activities_CSV_Importer( $filepath, ';' );
  $headers = $importer->get_header();
  $name_header = '';
  foreach ($headers as $header) {
    $c_header = preg_replace(
      '/
        ^
        [\pZ\p{Cc}\x{feff}]+
        |
        [\pZ\p{Cc}\x{feff}]+$
       /ux',
      '',
      $header
    );
    if ( $c_header == 'name' || $c_header == 'activity_name' ) {
      $name_header = $header;
      break;
    }
  }
  if ( $name_header != '' ) {
    echo '<form method="post">';
    echo '<h3>' . esc_html__( 'Select fields mapping', 'activities' ) . '</h3>';
    echo '<table>';
    echo '<tbody>';
    echo '<tr><td><label for="acts_import_col_name">Name: </label></td><td>' . stripslashes( wp_filter_nohtml_kses( $name_header ) );
    echo '<input type="hidden" name="map[name]" value="' . esc_attr( $name_header ) . '" /></td></tr>';
    foreach (Activities_Activity::get_columns() as $col) {
      if ( $col === 'name' || $col == 'archive' ) {
        continue;
      }
      echo '<tr><td><label for="' . esc_attr( 'acts_import_col_' . $col ) . '">' . Activities_Admin_Utility::get_column_display( $col ) . ': </label></td>';
      echo '<td><select id=' . esc_attr( 'acts_import_col_' . $col ) . ' name="' . esc_attr( 'map[' . $col . ']' ) . '">';
      echo '<option value="null" ' . ( !isset( $map[$col] ) ? 'selected' : '') . '>' . esc_html__( 'Don\'t Import', 'activity' ) . '</option>';
      echo '<option value="null">----------</option>';
      foreach ($headers as $header) {
        if ( $header == $name_header ) {
          continue;
        }
        $selected = '';
        if ( isset( $map[$col] ) && $map[$col] == $header ) {
          $selected = 'selected';
        }
        echo '<option value="' . esc_attr( $header ) . '" ' . $selected . '>' . stripslashes( wp_filter_nohtml_kses( $header ) ). '</option>';
      }
      echo '</select></td>';
      echo '</tr>';
    }
    echo '<tr><td><label for="update_activities"><b>' . esc_html__( 'Update Existing Activities', 'activities' ) . '</b></label></td>';
    echo '<td><input type="checkbox" id="update_activities" name="update_activities" /></td></tr>';
    echo '<tr><td><label for="archive_activities"><b>' . esc_html__( 'Archive Imported Activities', 'activities' ) . '</b></label></td>';
    echo '<td><input type="checkbox" id="archive_activities" name="archive_activities" /></td></tr>';
    echo '<tr><td colspan="2"><span class="acts-grey">' . esc_html__( 'Archived activities cannot be changed.', 'activities')  . '</span></td></tr>';
    echo '</tbody>';
    echo '</table>';
    echo '<input type="hidden" value="' . esc_attr( $filepath ) . '" name="filepath" />';
    echo '<p>';
    echo get_submit_button( esc_html__( 'Import', 'activities' ), 'button-primary', 'import_activity_data', false ) . ' ';
    echo get_submit_button( esc_html__( 'Return', 'activities' ), 'button', 'return', false );
    echo '</p>';
    echo '</form>';
    return true;
  }
  else {
    Activities_Admin::add_error_message( sprintf( esc_html__( 'No header called %s or %s found.', 'activities' ). 'name', 'activity_name' ) );
    return false;
  }
}

/**
 * Echoes page for participants import mapping
 *
 * @param string  $filepath Path to the import file
 * @param array   $map Values for mapping fields
 */
function activities_import_member_mapping( $filepath, $map = array() ) {
  $importer = new Activities_CSV_Importer( $filepath, ';' );
  $headers = $importer->get_header();
  if ( count( $headers ) != 2) {
    Activities_Admin::add_error_message( esc_html__( 'Expected exactly two headers.', 'activities' ) );
    return false;
  }
  echo Activities_Admin::get_messages();
  echo '<form method="post">';
  echo '<h3>' . esc_html__( 'Select fields mapping', 'activities' ) . '</h3>';
  echo '<table>';
  echo '<tbody>';
  $options = array(
    'users' => esc_html__( 'Users', 'activities' ),
    'activities' => esc_html__( 'Activities', 'activities' )
  );
  foreach ($options as $col => $display) {
    echo '<tr><td><label for="' . esc_attr( 'acts_import_col_' . $col ) . '">' . esc_html( $display ) . ': </label></td>';
    echo '<td><select id=' . esc_attr( 'acts_import_col_' . $col ) . ' name="' . esc_attr( 'map[' . $col . ']' ) . '">';
    foreach ($headers as $header) {
      $selected = '';
      if ( isset( $map[$col] ) && $map[$col] == $header ) {
        $selected = 'selected';
      }
      echo '<option value="' . esc_attr( $header ) . '" ' . $selected . '>' . stripslashes( wp_filter_nohtml_kses( $header ) ) . '</option>';
    }
    echo '</select></td>';
    echo '</tr>';
  }
  echo '<tr><td><label for="create_activities"><b>' . esc_html__( 'Create Activities That Dosn\'t Exist', 'activities' ) . '</b></label></td>';
  echo '<td><input type="checkbox" id="create_activities" name="create_activities" /></td></tr>';
  echo '<tr><td><label for="archive_activities"><b>' . esc_html__( 'Archive Activities', 'activities' ) . '</b></label></td>';
  echo '<td><input type="checkbox" id="archive_activities" name="archive_activities" /></td></tr>';
  echo '<tr><td colspan="2"><span class="description">' . esc_html__( 'Archived activities cannot be changed.', 'activities')  . '</span></td></tr>';
  echo '</tbody>';
  echo '</table>';
  echo '<input type="hidden" value="' . esc_attr( $filepath ) . '" name="filepath" />';
  echo '<p>';
  echo get_submit_button( esc_html__( 'Import', 'activities' ), 'button-primary', 'import_member_data', false ) . ' ';
  echo get_submit_button( esc_html__( 'Return', 'activities' ), 'button', 'return', false );
  echo '</p>';
  echo '</form>';
  return true;
}

/**
 * Handles import for activities and echoes resutls.
 * Can update and/or archive activities.
 * Already archived activities wont be updated.
 *
 * @param string  $filepath Path to the import file
 * @param array   $mapping Mapping of file headers to activity data
 * @param bool    $archive True to archive activities, false to not
 * @param bool    $update True to update activities, false to not.
 */
function activities_import_acts( $filepath, $mapping, $archive, $update ) {
  $importer = new Activities_CSV_Importer( $filepath, ';' );
  $lines = $importer->get_rows( 10 );
  $added = 0;
  $updated = 0;
  wp_ob_end_flush_all();
  echo '<h1>' . esc_html__( 'Importing activities', 'activities' ) . '</h1>';
  Activities_Admin_Utility::echo_scroll_script();

  echo '<form method="post">';
  echo str_repeat( ' ', 1024*64);
  flush();

  while ( count( $lines ) > 0 ) {
    foreach ($lines as $line) {
      echo '<ul class="acts-progress-row">';
      echo '<li>' . sprintf( '%d', ($added + $updated + 1) ) . '</b></li>';
      echo '<li>';
      $map = array();
      foreach ($mapping as $column => $header) {
        switch ($column) {
          case 'responsible_id':
            if ( $line[$header] != '' ) {
              $user = get_user_by( 'email', trim( $line[$header] ) );
              if ( $user !== false ) {
                $map[$column] = $user->ID;
              }
            }
            break;

          case 'location_id':
            if ( $line[$header] != '' ) {
              $loc = Activities_Location::load_by_name( trim( $line[$header] ) );
              if ( $loc ) {
                $map[$column] = $loc->ID;
              }
            }
            break;

          case 'start':
          case 'end':
            if ( $line[$header] != '' ) {
              $date = date_create_from_format( get_option( 'date_format' ), $line[$header] );
              if ( $date !== false ) {
                $map[$column] = date_format( $date, 'Y-m-d H:i:s' );
              }
            }
            break;

          case 'null':
            break;

          case 'long_desc':
            $map[$column] = trim( sanitize_textarea_field( $line[$header] ) );
            break;

          default:
            $map[$column] = trim( sanitize_text_field( $line[$header] ) );
            break;
        }
      }
      if ( $archive ) {
        $map['archive'] = 1;
      }
      if ( $map['name'] != '' ) {
      $success = Activities_Activity::insert( $map );
        if ( $success ) {
          echo esc_html__( 'Created activity: ', 'activities' ) . $map['name'] . '.';
          $added++;
        }
        else if ( $map['name'] != '' && $update ) {
          $act = Activities_Activity::load_by_name( $map['name'] );
          if ( $act ) {
            $map['activity_id'] = $act->ID;
            $success = Activities_Activity::update( $map );
            if ( $success ) {
              echo esc_html__( 'Updated activity: ', 'activities' ) . $map['name'] . '.';
              $updated++;
            }
          }
        }
        else {
          echo esc_html__( 'Could not create activity', 'activities' );
        }
      }
      else {
        echo esc_html__( 'Invalid name.', 'activities' );
      }

      echo '</li></ul>';
      echo str_repeat( ' ', 1024*64 );
      flush();
    }
    $lines = $importer->get_rows( 10 );
  }

  echo '</br>';
  echo sprintf( esc_html__( 'Created %d activities!' ), $added ) . '</br>';
  if ( $update ) {
    echo sprintf( esc_html__( 'Updated %d activities!' ), $updated ) . '</br>';
  }

  echo get_submit_button( esc_html__( 'Return', 'activities' ), 'button', 'return' );
  echo '</form>';
}

/**
 * Handles import for activities and echoes resutls.
 * Can create and archive activities.
 * Will change participants for archived activities
 *
 * @param string  $filepath Path to the import file
 * @param array   $mapping Mapping of file headers to user_activity data
 * @param bool    $create True to create activities that does not exist, false to not
 * @param bool    $archive True to archive activities, false to not
 */
function activities_import_members( $filepath, $mapping, $create, $archive ) {
  $importer = new Activities_CSV_Importer( $filepath, ';' );
  $lines = $importer->get_rows( 10 );

  $archived = 0;
  $line_count = 0;
  wp_ob_end_flush_all();
  echo '<h1>' . esc_html__( 'Importing participants', 'activities' ) . '</h1>';
  Activities_Admin_Utility::echo_scroll_script();

  echo '<form method="post">';
  echo str_repeat( ' ', 1024*64);
  flush();

  if ( $archive ) {
    $all_acts = array();
  }
  while ( count( $lines ) > 0 ) {
    foreach ($lines as $line) {
      $added = 0;
      $created = 0;
      $acts = array();
      $users = array();
      foreach ($mapping as $column => $header) {
        switch ($column) {
          case 'activities':
            $acts = explode( ',', $line[$header] );
            break;

          case 'users':
            $users = explode( ',', $line[$header] );
            break;
        }
      }

      $a_ids = array();
      foreach ($acts as $name) {
        $name = trim( $name );
        $name = sanitize_text_field( $name );
        $act = Activities_Activity::load_by_name( $name );
        if ( $act ) {
          $a_ids[$act->ID] = $name;
        }
        elseif ( $create ) {
          Activities_Activity::insert( array( 'name' => $name ) );
          $act = Activities_Activity::load_by_name( $name );
          if ( $act ) {
            $a_ids[$act->ID] = $name;
            $created++;
          }
        }
      }

      $u_ids = array();
      foreach ($users as $email) {
        $email = trim( $email );
        $user = get_user_by( 'email', $email );
        if ( $user ) {
          $u_ids[$user->ID] = $user->ID;
        }
      }
      if ( count( $a_ids ) > 0 && count( $u_ids ) > 0 ) {

        foreach ($a_ids as $a => $a_name) {
          if ( $archive && !isset( $all_acts[$a] ) ) {
            $all_acts[$a] = $a_name;
          }
          foreach ($u_ids as $u) {
            if ( Activities_User_Activity::insert( $u, $a, true ) ) {
              $added++;
            }
          }
        }
      }
      $line_count++;
      echo '<ul class="acts-progress-row">';
      echo '<li>' . sprintf( '%d', ($line_count) ) . '</li>';
      if ( $added > 0 || $created > 0 ) {
        if ( $create && $created > 0 ) {
          echo '<li>' . sprintf( esc_html__( 'Created %d activities.', 'activities' ), $created ) . '</li>';
        }
        if ( $added > 0 ) {
          echo '<li>' . sprintf( esc_html__( 'Added %d participants.', 'activities' ), $added ) . '</li>';
        }
      }
      else {
        echo '<li>' . esc_html__( 'No Changes.', 'activities' ) . '</li>';
      }
      echo '</ul>';
      echo str_repeat( ' ', 1024*64 );
      flush();
    }
    $lines = $importer->get_rows( 10 );
  }

  if ( $archive ) {
    foreach ($all_acts as $id => $name) {
      if ( Activities_Activity::archive( $id ) ) {
        $archived++;
      }
    }
  }

  echo '</br>';
  if ( $archive ) {
    echo sprintf( esc_html__( 'Archived %d activities!', 'activities' ), $archived ) . '</br>';
  }

  echo get_submit_button( esc_html__( 'Return', 'activities' ), 'button', 'return' );
  echo '</form>';
}

/**
 * Gets file upload error message
 *
 * @param   string  $code Error code
 * @return  string  Error message
 */
function activities_file_upload_error_message($code){
  switch ($code) {
    case UPLOAD_ERR_INI_SIZE:
      $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
      break;
    case UPLOAD_ERR_FORM_SIZE:
      $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
      break;
    case UPLOAD_ERR_PARTIAL:
      $message = 'The uploaded file was only partially uploaded';
      break;
    case UPLOAD_ERR_NO_FILE:
      $message = 'No file was uploaded';
      break;
    case UPLOAD_ERR_NO_TMP_DIR:
      $message = 'Missing a temporary folder';
      break;
    case UPLOAD_ERR_CANT_WRITE:
      $message = 'Failed to write file to disk';
      break;
    case UPLOAD_ERR_EXTENSION:
      $message = 'File upload stopped by extension';
      break;

    default:
      $message = 'Unknown upload error';
      break;
  }
  return $message;
}
