<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Class for creating pagination for table pages
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Pagination {
  /**
   * Total items for the table
   *
   * @var int
   */
  private $total_items;

  /**
   * Total table pages
   *
   * @var int
   */
  private $total_pages;

  /**
   * Number of items to display per page
   *
   * @var int
   */
  private $items_per_page;

  /**
   * Sets $total_items, $items_per_page and calulates $total_pages
   *
   * @param int $total_items Total items for the table
   * @param int $items_per_page Number of items to display per page
   */
  function __construct( $total_items, $items_per_page ) {
    $this->total_items = $total_items;
    $this->items_per_page = $items_per_page <= 0 ? 1 : $items_per_page;
    $this->total_pages = ceil( $this->total_items/$this->items_per_page );
  }

  /**
   * Retrieve property by name, accepted input:
   *    - total_items
   *    - total_pages
   *    - items_per_page
   *
   * @param   string        $name Proprety to get
   * @return  int|string     Data found for $name key, '' if not data was found
   */
  function __get( $name ) {
    if ( in_array( $name, array( 'total_items', 'total_pages', 'items_per_page' ) ) ) {
      return $this->$name;
    }
    else {
      return '';
    }
  }

  /**
   * Retrieve current page number
   *
   * @return int Page number
   */
  function get_pagenum() {
    if ( isset( $_REQUEST['paged'] ) ) {
      $page = acts_validate_id( $_REQUEST['paged'] );
      if ( $page < 1 ) {
        return 1;
      }
      else if ( $page > $this->total_pages ) {
        return $this->total_pages;
      }
      else {
        return $page;
      }
    }

    return 1;
  }

  /**
   * Check if the current table is paged
   *
   * @return bool True if table is paged, false otherwise
   */
  function check_if_paged() {
    if ( isset( $_REQUEST['paged'] ) ) {
      return $this->total_pages === 1;
    }

    return false;
  }
  /**
   * Builds SQL for paging content
   *
   * @return string LIMIT and OFFSET
   */
  function get_sql() {
    if ( $this->total_items > $this->items_per_page ) {
      $offset = ( $this->get_pagenum() - 1 ) * $this->items_per_page;
      return sprintf( "LIMIT %d OFFSET %d", $this->items_per_page, $offset );
    }
    else {
      return '';
    }
  }

  /**
   * Build $items_per_page input html
   *
   * @param   string  $current_url Current url to return to
   * @param   string  $type Type of items displayed in the table
   * @return  string  html to print
   */
  function get_pagination( $current_url, $type ) {
  	$output = '<div id="activities-pagination-wrap">';
    $output .= '<label for="activities-items-num">' . esc_html__( 'Results Per Page', 'activities' ) . '</label>';
    $output .= '<input type="number" name="items_num" id="activities-items-num" min="1" max="500" value="' . $this->items_per_page . '"/>';
    $output .= '<input type="submit" name="apply_items_num" class="button" value="' . esc_html__( 'Apply', 'activities' ) . '" />';
    $output .= '</div>'; //activities-pagination-wrap

  	return '';
  }

  /**
   * Build page control html
   *
   * @param   string  $current_url Current url to return to
   * @param   string  $type Type of items displayed in the table
   * @return  string  HTML to print
   */
  function get_pagination_control( $current_url, $type ) {
    $current_page = $this->get_pagenum();

    $output = '<div class="tablenav-pages';
    if ( $this->total_items <= $this->items_per_page ) {
      $output .= ' one-page';
    }
    $output .= '">';

    $output .= '<span class="displaying-num">' . esc_html( $this->total_items . ' ' . acts_get_multi_item_translation( $type, $this->total_items ) ) . '</span>';

    $output .= '<span class="pagination-links">';
    if ( $this->total_items > $this->items_per_page ) {
      if ( $current_page > 1 ) {
        $output .= '<a href="' . esc_url( remove_query_arg( 'paged', $current_url ) ) . '" class="first-page">&laquo;</a> ';

        $stepback_url = add_query_arg( 'paged', $current_page - 1, $current_url );
        $output .= '<a href="' . esc_url( $stepback_url ) . '" class="prev-page">&lsaquo;</a> ';
      }
      else {
        $output .= '<span class="tablenav-pages-navspan">&laquo;</span> ';
        $output .= '<span class="tablenav-pages-navspan">&lsaquo;</span> ';
      }

      $output .= '<span class="paging-input"><input type="text" value="' . $current_page . '" name="paged" size="1" class="current-page" /> ' . esc_html__( 'of', 'activities' ) . ' <span class="total-pages">' . $this->total_pages . '</span></span> ';

      if ( $current_page < $this->total_pages) {
        $stepforward_url = add_query_arg( 'paged', $current_page + 1, $current_url );
        $output .= '<a href="' . esc_url( $stepforward_url ) . '" class="next-page">&rsaquo;</a> ';

        $output .= '<a href="' . esc_url( add_query_arg( 'paged', $this->total_pages, $current_url ) ) . '" class="last-page">&raquo;</a>';
      }
      else {
        $output .= '<span class="tablenav-pages-navspan">&rsaquo;</span> ';

        $output .= '<span class="tablenav-pages-navspan">&raquo;</span>';
      }
    }
    $output .= '</span>';
    $output .= '</div>';

    return $output;
  }
}
