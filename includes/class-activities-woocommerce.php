<?php

if ( !defined( 'WPINC' ) ) {
  die;
}
/**
 * WooCommerce relation class
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_WooCommerce {
  /**
   * Key for identifying user who where made by guest convertion
   */
  const guest_key = '_activities_guest';

  /**
   * Key for finding activities added to products
   */
  const selected_acts_key = '_selected_activities';

  /**
   * Adds actions if WooCommerce is an activated plugin
   */
  static function init() {
    if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
      return;
    }
    add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'product_tab' ) );
    add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'product_tab_panel' ) );
    add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'variation_tab_panel' ), 10, 3 );
    add_action( 'save_post', array( __CLASS__, 'product_tab_save' ) );
    add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'product_variation_save' ), 10, 2 );
    add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'order_complete' ) );
    add_action( 'woocommerce_update_new_customer_past_order', array( __CLASS__, 'resolve_past_order' ), 10, 2 );
    add_action( 'activities_archive_activity', array( __CLASS__, 'remove_selected_activities' ) );
    add_filter( 'woocommerce_prevent_admin_access', array( __CLASS__, 'prevent_access_to_admin' ) );
    add_filter( 'manage_product_posts_columns', array( __CLASS__, 'product_column' ) );
    add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'display_product_column' ), 10, 2 );
  }

  /**
   * Callback for adding tabs to products
   *
   * @param   array   $tabs Current tabs
   * @return  array   Tabs with activities added
   */
  static function product_tab( $tabs ) {
    $tabs['activities'] = array(
      'label'  => esc_html__( 'Activities', 'activities' ),
  		'target' => 'activities_woocommerce_tab'
    );

    return $tabs;
  }

  /**
   * Callback for adding acts to product column
   *
   * @param   array $post_columns Existing columns
   * @return  array
   */
  static function product_column( $post_columns ) {
    $post_columns['activities_activities'] = __( 'Activities', 'activities' );
    return $post_columns;
  }
  /**
   * Echo select activity field
   *
   * @param int $id Post Id
   * @param int $index Index for varaible products
   */
  static function get_activity_select( $id, $index = false ) {
    if ( $index === false ) {
      $name = self::selected_acts_key . '[]';
      $elem_id = 'acts_select_activities';
      $class = 'long';
      $hidden_name = 'acts_save';
    }
    else {
      $name = 'multi' . self::selected_acts_key . '[' . $index . '][]';
      $elem_id = 'acts_select_activities_' . $index;
      $class = 'short';
      $hidden_name = 'acts_save_' . $index;
    }

    woocommerce_wp_select(
      array(
        'name' => $name,
        'id' => $elem_id,
        'options' => acts_get_items_map( 'activity' ),
        'value' => get_post_meta( $id, self::selected_acts_key ),
        'label' => esc_html__( 'Select Activities', 'activities' ),
        'class' => $class,
        'placeholder' => esc_html__( 'Select Activities', 'activities' ),
        'desc_tip' => false,
        'description' => esc_html__( 'Users who orders this product will be added to the selected activities.', 'activities' ),
        'custom_attributes' => array( 'multiple' => 'multiple' )
      )
    );

    //Required to prevent qucik saving from deleting acts on products
    echo '<input type="hidden" value="' . esc_attr( $id ) . '" name="' . $hidden_name . '" />';

    echo '
    <script>
      jQuery("document").ready( function() {
        jQuery("#' . $elem_id . '").selectize({
          closeAfterSelect: true,
          plugins: ["remove_button"]
        });
      });
    </script>';
  }

  /**
   * Callback for adding tab content
   */
  static function product_tab_panel() {
    global $wpdb, $thepostid;

  	echo '<div id="activities_woocommerce_tab" class="panel woocommerce_options_panel">';
    self::get_activity_select( $thepostid );

    woocommerce_wp_checkbox(
      array(
        'name' => 'handle_past_orders',
        'id' => 'acts_past_orders',
        'label' => esc_html__( 'Handle past orders', 'activities' ),
        'description' => esc_html__( 'Add users to selected activities who have already bought the product', 'activities' ),
      )
    );

    $start_of_year = date( 'Y' );
    $start_of_year .= '-01-01';

    echo '<p class="form-field acts_past_orders_date_field">';
    echo '<label for="acts_past_orders_date">' . esc_html__( 'Handle orders from', 'activities') . '</label>';
    echo '<input id="acts_past_orders_date" type="date" name="handle_past_orders_from" value="' . $start_of_year . '" />';
    echo '<span class="description">' . esc_html__( 'Only select orders from this date and onwards', 'activities' ) . '</span>';
    echo '</p>';
  	echo '</div>';
  }

  /**
   * Callback for added options to product variations
   */
  static function variation_tab_panel( $index, $variation_data, $variation ) {

    echo '<div class="options_group form-row form-row-full">';

    self::get_activity_select( $variation->ID, $index );

    echo '</div>';
  }

  /**
   * Callback for displaying product column
   *
   * @param string $column_name
   * @param int    $post_id
   */
  static function display_product_column( $column_name, $post_id ) {
    if ( $column_name == 'activities_activities' ) {
      $acts = get_post_meta( $post_id, self::selected_acts_key );

      $get_name = function( $id ) {
        $act = Activities_Activity::load( $id );
        if ( $act ) {
          return $act['name'];
        }
      };

      echo implode( ', ', array_map( $get_name, $acts ) );
    }
  }

  /**
   * Save activities to product
   *
   * @param int   $product_id Product id
   * @param array $selected_acts Selected activities
   */
  static function product_save( $product_id, $selected_acts ) {
    $existing = get_post_meta( $product_id, self::selected_acts_key );
    if ( !is_array( $existing ) ) {
      $existing = array( $existing );
    }

    if ( is_array( $selected_acts ) ) {
      foreach ($selected_acts as $a_id) {
        $a_id = acts_validate_id( $a_id );
        if ( $a_id ) {
          $key = array_search( $a_id, $existing );
          if ( $key === false && Activities_Activity::exists( $a_id ) ) {
            add_post_meta( $product_id, self::selected_acts_key, $a_id );
          }
          else {
            unset( $existing[$key] );
          }
        }
      }
    }

    foreach ($existing as $a_id) {
      delete_post_meta( $product_id, self::selected_acts_key, $a_id );
    }
  }

  /**
   * Callback for saving a post
   *
   * Adds and deletes activities from the post meta data.
   * Finds all orders containing the saved product and adds users to saved activities
   *
   * @param   int   $post_id Id of saved post
   */
  static function product_tab_save( $post_id ) {
    $product = wc_get_product( $post_id );
    if ( empty( $product )  ) {
      return;
    }

    $acts = array();
    if ( isset( $_POST[self::selected_acts_key] ) && is_array( $_POST[self::selected_acts_key] ) ) {
      $acts = $_POST[self::selected_acts_key] ;
    }

    if ( isset( $_POST['acts_save'] ) && acts_validate_id( $_POST['acts_save'] ) == $post_id ) {
      self::product_save( $post_id, $acts );
    }

    if ( isset( $_POST['handle_past_orders'] ) ) {
      $from_date = sanitize_text_field( $_POST['handle_past_orders_from'] );
      foreach (self::get_orders_by_product_ids( array( $post_id ), $from_date ) as $order_id) {
        self::order_complete( $order_id );
      }
      if ( $product->is_type( 'variable' ) ) {
        foreach (self::get_orders_by_product_ids( $product->get_children(), $from_date ) as $v_order_id) {
          self::order_complete( $v_order_id );
        }
      }
    }
  }

  /**
   *  Callback for saving product variations
   */
  static function product_variation_save( $variation_id, $index ) {
    $acts = array();
    if ( isset( $_POST['multi' . self::selected_acts_key][$index] ) && is_array( $_POST['multi' . self::selected_acts_key][$index] ) ) {
      $acts = $_POST['multi' . self::selected_acts_key][$index];
    }

    if ( isset( $_POST['acts_save_' . $index] ) && acts_validate_id( $_POST['acts_save_' . $index] ) == $variation_id ) {
      self::product_save( $variation_id, $acts );
    }
  }

  /**
   * Gets all orders by product ids
   *
   * @param   array   $product_ids Array of product ids
   * @param   string  $from_date Only get order after this date
   * @param   string  $variation Whether this is a product variation or not
   * @return  array   Array of order ids
   */
  static function get_orders_by_product_ids( $product_ids, $from_date = '' ) {
    global $wpdb;

    if ( empty( $product_ids ) ) {
      return array();
    }

    $prod_ids = array();
    $var_ids = array();
    foreach ($product_ids as $prod_id) {
      $product = wc_get_product( $prod_id );
      if ( $product->is_type( 'variation' ) ) {
        $var_ids[] = sprintf( '\'%d\'', $prod_id );
      }
      else {
        $prod_ids[] = sprintf( '\'%d\'', $prod_id );
      }
    }
    $prod_ids = implode( ', ', $prod_ids );
    $var_ids = implode( ', ', $var_ids );

    $date = Activities_Admin_Utility::validate_date( $from_date . ' 00:00:00', 'Y-m-d H:i:s', false );
    $date_filter = '';
    if ( $date ) {
      $date_filter = sprintf( 'AND posts.post_date >= \'%s\'', $date );
    }

    $all_orders = array();
    foreach (array( '_product_id' => $prod_ids, '_variation_id' => $var_ids) as $key => $ids) {
      if ( empty( $ids ) ) {
        continue;
      }
      $all_orders = array_merge(
        $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status = 'wc-completed'
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '$key'
        AND order_item_meta.meta_value IN ($ids)
        $date_filter"
      ),
        $all_orders
      );
    }

    return $all_orders;
  }

  /**
   * Callback for a order set to completed
   *
   * Converts guest customers to user and adds relation between user and activities saved to products ordered.
   *
   * @param   int   $order_id Id of order
   */
  static function order_complete( $order_id ) {
    global $wpdb;

    $order = wc_get_order( $order_id );

    $user = $order->get_user();
    if ( $user ) {
      $activities = self::get_order_activities( $order );
      foreach ($activities as $activity_id) {
        Activities_User_Activity::insert( $user->ID, $activity_id );
      }
    }
    elseif ( Activities_Options::get_option( ACTIVITIES_WOOCOMMERCE_CONVERT_KEY ) ) {
      $email = $order->get_billing_email();

      $user_id = self::handle_guest_customer( $email, $order );
    }
  }

  /**
   * Callback for a user being added too a past order
   *
   * Adds relation between user and activities saved to products ordered.
   *
   * @param   int           $order_id Id of order
   * @param   WP_User|int   $customer WP_User object or user_id
   */
  static function resolve_past_order( $order_id, $customer ) {
    $order = wc_get_order( $order_id );
    $activities = self::get_order_activities( $order );

    if ( $customer instanceof WP_User ) {
      $user_id = $customer->ID;
    }
    else if ( is_numeric( $customer ) ) {
      $user_id = intval( $customer );
    }

    if ( !empty( $user_id ) ) {
      foreach ($activities as $activity_id) {
        Activities_User_Activity::insert( $user_id, $activity_id );
      }
    }
  }

  /**
   * Gets all activities from a order
   *
   * @param   WC_Order   $order Order object
   * @return  array      Array of activity ids
   */
  static function get_order_activities( $order ) {
    $activities = array();
    $items = $order->get_items();

    foreach ($items as $item) {
      $product = $item->get_product();
      if ( $product ) {
        $product_id = $product->get_id();
        $selected_activities = get_post_meta( $product_id, self::selected_acts_key );
        foreach ($selected_activities as $activity_id) {
          if ( !in_array( $activity_id, $activities ) ) {
            $activities[] = $activity_id;
          }
        }
      }
    }

    return $activities;
  }

  /**
   * Converts guest users from past orders, tries to print results to screen instantly
   */
  static function create_users_from_past_orders() {
    wp_ob_end_flush_all();
    $orders = wc_get_orders(
      array(
        'customer_id' => 0,
        'status' => 'completed',
        'return' => 'ids',
        'limit' => -1
      )
    );

    echo '<h1>' . esc_html__( 'Converting guest customers', 'activities' ) . '</h1></br>';
    Activities_Admin_Utility::echo_scroll_script();
    echo str_repeat( ' ', 1024*64 );
    flush();

    echo '<form method="post">';
    $created = array();

    foreach ($orders as $order_id) {
      echo '<ul class="acts-progress-row">';
      echo '<li>' . esc_html__( 'Order', 'activities' ) . ': ' . $order_id . '</li>';
      $order = wc_get_order( $order_id );
      $user = $order->get_user();
      if ( $user ) {
        echo '<li style="color: green">' . esc_html__( 'Guest already converted or user orders already updated.', 'activities' ) . '</li>';
      }
      else {
        $email = $order->get_billing_email();
        if ( !empty( $email ) ) {
          $user_id = self::handle_guest_customer( $email, $order );
          if ( is_numeric( $user_id ) && $user_id !== 0 && !isset( $created[$user_id] ) ) {
            echo '<li>' . sprintf( esc_html__( 'Created User: %s', 'activities' ), stripslashes( wp_filter_nohtml_kses( Activities_Utility::get_user_name( $user_id ) ) ) ) .  ' </li>';
            $created[$user_id] = $user_id; //Optimized for isset use, faster than in_array
          }
          elseif ( $user_id instanceof WP_User ) {
            echo '<li>' . sprintf( esc_html__( 'Updated Orders For: %s', 'activities' ), stripslashes( wp_filter_nohtml_kses( Activities_Utility::get_user_name( $user_id ) ) ) ) .  '</li>';
          }
        }
        else {
          echo '<li style="color: darkred">' . esc_html__( 'Could not create user (no email found)', 'activities' ) . '</li>';
        }
      }
      echo '</ul>';
      echo str_repeat( ' ', 1024*64 );
      flush();
    }

    echo '</br>' . sprintf( esc_html__( 'Created %s users.', 'activities' ), count( $created ) );
    echo get_submit_button( esc_html__( 'Return', 'activities' ), 'button', 'return' );
    echo '</form>';
  }

  /**
   * Handles a guest customer
   *
   * If the billing email does not exists on a user_login and user_email it creates a new user,
   * and adds all meta data to that user. Finally it adds the new user to past orders.
   * Otherwise it finds the user and adds the user to past orders
   * Adding a user to past orders also creates relations with activities on products ordered and user.
   *
   * @param   string              $email Email of the guest customer
   * @param   WC_Order            $order The order
   * @return  int|WP_User|bool    Returns the new user_id, WP_User if it where an existing user or false on error
   */
  static function handle_guest_customer( $email, $order ) {
    $email = sanitize_email( $email );
    if ( $email === '' ) {
      return false;
    }
    if ( email_exists( $email ) === false && username_exists( $email ) === false ) {
      $password = wp_generate_password();

      $user_id = wp_create_user( $email, $password, $email );

      if ( is_wp_error( $user_id ) ) {
        return false;
      }

      update_user_meta( $user_id, self::guest_key, 1 );

      $user = new WP_User( $user_id );
      $user->add_role( 'customer' );
    }
    else {
      $user = get_user_by( 'email', $email );
      if ( !empty( $user ) ) {
        $user_id = $user->ID;
      }

      if ( isset( $user_id ) ) {
        wc_update_new_customer_past_orders( $user_id );
      }

      return $user;
    }

    if ( !empty( $user_id ) ) {
      wp_update_user( array( 'ID' => $user_id, 'display_name' => ( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ) );

      update_user_meta( $user_id, 'first_name', $order->get_billing_first_name() );
      update_user_meta( $user_id, 'last_name', $order->get_billing_last_name() );

      update_user_meta( $user_id, 'billing_address_1', $order->get_billing_address_1() );
      update_user_meta( $user_id, 'billing_address_2', $order->get_billing_address_2() );
      update_user_meta( $user_id, 'billing_city', $order->get_billing_city() );
      update_user_meta( $user_id, 'billing_company', $order->get_billing_company() );
      update_user_meta( $user_id, 'billing_country', $order->get_billing_country() );
      update_user_meta( $user_id, 'billing_email', $order->get_billing_email() );
      update_user_meta( $user_id, 'billing_first_name', $order->get_billing_first_name() );
      update_user_meta( $user_id, 'billing_last_name', $order->get_billing_last_name() );
      update_user_meta( $user_id, 'billing_phone', $order->get_billing_phone() );
      update_user_meta( $user_id, 'billing_postcode', $order->get_billing_postcode() );
      update_user_meta( $user_id, 'billing_state', $order->get_billing_state() );

      update_user_meta( $user_id, 'shipping_address_1', $order->get_shipping_address_1() );
      update_user_meta( $user_id, 'shipping_address_2', $order->get_shipping_address_2() );
      update_user_meta( $user_id, 'shipping_city', $order->get_shipping_city() );
      update_user_meta( $user_id, 'shipping_company', $order->get_shipping_company() );
      update_user_meta( $user_id, 'shipping_country', $order->get_shipping_country() );
      update_user_meta( $user_id, 'shipping_first_name', $order->get_shipping_first_name() );
      update_user_meta( $user_id, 'shipping_last_name', $order->get_shipping_last_name() );
      update_user_meta( $user_id, 'shipping_method', $order->get_shipping_method() );
      update_user_meta( $user_id, 'shipping_postcode', $order->get_shipping_postcode() );
      update_user_meta( $user_id, 'shipping_state', $order->get_shipping_state() );

      $order_meta = get_post_meta( $order->get_id() );

      if ( is_array( $order_meta ) ) {
        foreach ($order_meta as $key => $value) {
          if ( !is_protected_meta( $key ) ) {
            if ( is_array($value) ) {
              update_user_meta( $user_id, $key, $value[0] );
            }
            else {
              update_user_meta( $user_id, $key, $value );
            }
          }
        }
      }

      wc_update_new_customer_past_orders( $user_id );

      return $user_id;
    }
    else {
      return false;
    }
  }

  /**
   * Deletes guest users, tries to print results to screen instantly
   */
  static function flush_created_users() {
    wp_ob_end_flush_all();
    if ( !current_user_can( 'delete_users' ) ) {
      echo esc_html__( 'You are not allowed to delete users!', 'activities' );
      return;
    }

    echo '<h1>' . esc_html__( 'Deleting guest users', 'activities' ) . '</h1></br>';
    Activities_Admin_Utility::echo_scroll_script();
    echo str_repeat( ' ', 1024*64 );
    flush();

    echo '<form method="post">';

    $args = array(
      'fields' => 'ID',
      'meta_key' => self::guest_key,
      'meta_value' => '1'
    );

    if ( is_multisite() ) {
      $args['blog_id'] = 0;
    }

    $users = get_users( $args );

    $count = 0;
    $i = 1;
    foreach ($users as $user_id) {
      echo '<ul class="acts-progress-row">';
      echo '<li>' . $i . '</li>';
      $name = Activities_Utility::get_user_name( $user_id );
      $del = false;
      if ( is_multisite() ) {
        $del = wpmu_delete_user( $user_id );
      }
      else {
        $del = wp_delete_user( $user_id );
      }
      if ( $del ) {
        $count++;
        echo '<li>' . esc_html__( 'Deleted user', 'activities' ) . ': ' . stripslashes( wp_filter_nohtml_kses( $name ) ) . '</li>';
      }
      else {
        echo '<li style="color: darkred">' . esc_html__( 'An error occured deleting', 'activities' ) . ': ' . stripslashes( wp_filter_nohtml_kses( $name ) ) . '</li>';
      }
      $i++;
      echo '</ul>';
      echo str_repeat( ' ', 1024*64 );
      flush();
    }

    echo '</br>' . sprintf( esc_html__( 'Deleted %d users.', 'activities' ), $count );
    echo get_submit_button( esc_html__( 'Return', 'activities' ), 'button', 'return' );
    echo '</form>';
  }

  /**
   *  Gets all products that is connected to an activity
   *
   * @param   int     $activity_id Activity id
   * @return  array   Array of product ids
   */
  static function get_products_with_activity( $activity_id ) {
    global $wpdb;

    return $wpdb->get_col( $wpdb->prepare(
      "SELECT post_id
      FROM {$wpdb->postmeta}
      WHERE meta_key = %s AND meta_value = %s
      ",
      array( self::selected_acts_key, $activity_id )
    ));
  }

  /**
   * Gets user order data for an activity nice display, initializes if needed
   *
   * @param   int     $activity_id Activity id
   * @param   array   $user_ids Activity memebers
   * @return  array   Array of user_id mapped to order_ids
   */
  static function get_activity_orders( $activity_id, $user_ids = array() ) {
    global $wpdb;

    if ( empty( $user_ids ) ) {
      $act = new Activities_Activity( $activity_id );
      if ( $act->members != '' ) {
        $user_ids = $act->members;
      }
    }
    $coupons_display = array();
    foreach ($user_ids as $id) {
      $coupons_display[$id] = array();
    }

    $orders = self::get_orders_by_product_ids( self::get_products_with_activity( $activity_id ) );
    foreach ($orders as $order_id) {
      $order = wc_get_order( $order_id );
      if ( !isset( $coupons_display[$order->get_user_id()] ) ) {
        continue;
      }
      $coupons_display[$order->get_user_id()][intval( $order_id )] = intval( $order_id );
    }

    return $coupons_display;
  }

  /**
   * Prevent admin access filter
   *
   * @param   bool  $prevent_admin_access Whether admin access is prevented or not
   * @return  bool  Prevent admin access or not
   */
  static function prevent_access_to_admin( $prevent_admin_access ) {
    if ( !current_user_can( ACTIVITIES_ACCESS_ACTIVITIES ) ) {
      return $prevent_admin_access;
    }

    return false;
  }

  /**
   * Callback for activity archiving
   *
   * Removes all activity with id $activity_id from products
   *
   * @param int $activity_id Id of the archived activity
   */
  static function remove_selected_activities( $activity_id ) {
    global $wpdb;

    $wpdb->delete(
      $wpdb->postmeta,
      array( 'meta_key' => self::selected_acts_key, 'meta_value' => $activity_id ),
      array( '%s', '%s' )
    );
  }
}
