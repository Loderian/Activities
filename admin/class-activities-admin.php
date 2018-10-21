<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 		 1.0.0
 * @package    Activities
 * @subpackage Activities/admin
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Messages to display
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $messages    Messages to display
	 */
	private static $messages = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function register_styles() {
		wp_register_style( 'admin-page', plugin_dir_url( __FILE__ ) . 'css/activities-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'selectize-css', plugin_dir_url( __FILE__ ) . 'css/selectize/selectize.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueues the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'admin-page' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function register_scripts() {
		wp_register_script( 'activities-admin-js', plugin_dir_url( __FILE__ ) . 'js/activities-admin.js', array( 'jquery', 'wp-color-picker' ), $this->version, false );
		wp_localize_script( 'activities-admin-js', 'acts_i18n', array(
			'select_img_title' => esc_html__( 'Select a logo for the activity report', 'activities' )
		) );
		wp_enqueue_script( 'selectize-js', plugin_dir_url( __FILE__ ) . 'js/selectize/selectize.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Enqueues the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'activities-admin-js' );
		wp_enqueue_script( 'imagesloaded' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
		* Add menu pages to the wp-admin menu
		*/
	public function activities_admin_menu() {
		$pages = array();

		add_menu_page( esc_html__( 'Activities Admin Page', 'activities' ), esc_html__( 'Activities', 'activities' ), ACTIVITIES_ACCESS_ACTIVITIES, 'activities-admin', '', 'dashicons-groups', 30 );

		$pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities', 'activities' ), esc_html__( 'Activities', 'activities' ), ACTIVITIES_ACCESS_ACTIVITIES, 'activities-admin', array ($this, 'activities_admin_activities') );
		$pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Locations', 'activities' ), esc_html__( 'Locations', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-locations', array ($this, 'activities_admin_locations') );
		$pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Import', 'activities' ), esc_html__( 'Import', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-import', array ($this, 'activities_admin_import') );
		$pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Export', 'activities' ), esc_html__( 'Export', 'activities' ), ACTIVITIES_ACCESS_ACTIVITIES, 'activities-admin-export', array ($this, 'activities_admin_export') );
		$pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Options', 'activities' ), esc_html__( 'Options', 'activities' ), ACTIVITIES_ADMINISTER_OPTIONS, 'activities-admin-options', array ($this, 'activities_admin_options') );
		$pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Archive', 'activities' ), esc_html_x( 'Archive', 'Noun', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-archive', array ($this, 'activities_admin_archive') );

		foreach ($pages as $page) {
			add_action( 'admin_print_styles-' . $page, array( $this, 'enqueue_styles' ) );
			add_action( 'admin_print_scripts-' . $page, array( $this, 'enqueue_scripts' ) );
			add_action( 'load-' . $page, array( $this, 'show_help' ) );
		}
	}

	/**
		* Echoes activities page
		*/
	public function activities_admin_activities() {
		echo '<div class="wrap">' . activities_admin_activities_page() . '</div>';
	}

	/**
		* Echoes locations page
		*/
	public function activities_admin_locations() {
		echo '<div class="wrap">' . activities_admin_locations_page() . '</div>';
	}

	/**
		* Echoes options page
		*/
	public function activities_admin_options() {
		echo '<div class="wrap">';
		activities_admin_options_page();
		echo '</div>';
	}

	/**
		* Echoes export page
		*/
	public function activities_admin_export() {
		echo '<div class="wrap">';
		activities_export_page();
		echo '</div>';
	}

	/**
		* Echoes import page
		*/
	public function activities_admin_import() {
		echo '<div class="wrap">';
		activities_import_page();
		echo '</div>';
	}

	/**
		* Echoes archive page
		*/
	public function activities_admin_archive() {
		echo '<div class="wrap">' . activities_admin_archive_page() . '</div>';
	}

	/**
		* Echoes options to add/remove activities, and look at archived activities on user profiles
		*
		* @param WP_User $user User accessing the page
		*/
	public function activities_add_user_option( $user ) {
		echo '<h3>' . esc_html__( 'Activities', 'activities' ) . '</h3>';

		global $wpdb;

		$table_name_activity = Activities::get_table_name( 'activity' );

		$activity_names = $wpdb->get_results(
			"SELECT activity_id, name
			FROM $table_name_activity
			WHERE archive = 0
			",
			ARRAY_A
		);

		$selected_activities = Activities_User_Activity::get_user_activities( $user->ID );

		$value = implode(',', $selected_activities);

		$disabled = '';

		if ( !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
			$disabled = 'disabled';
		}

		echo '<input type="text" name="activities_select" value="' . esc_attr( $value ) . '" id="activities-select" ' . $disabled . ' />';

		$extra = array();
		if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
			$extra[] = 'plugins: ["remove_button"]';
		}

		$selectize = array();
		$selectize[] = array(
			'name' => 'activities-select',
			'value' => 'activity_id',
			'label' => 'name',
			'search' => array( 'name' ),
			'option_values' => $activity_names,
			'max_items' => 'null',
			'extra' => $extra
		);

		if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
			echo '<h4>' . esc_html__( 'Archived Activites', 'activities' ) . '</h4>';

			$selected_archived_activities = Activities_User_Activity::get_user_activities( $user->ID, 'archive');

			$a_value = implode(',', $selected_archived_activities);

			$activity_archive_names = array();
			if ( count( $selected_archived_activities ) > 0 ) {
				$activity_archive_names = $wpdb->get_results(
					"SELECT activity_id, name
					FROM $table_name_activity
					WHERE archive = 1 AND activity_id IN ( $a_value )
					",
					ARRAY_A
				);
			}

			echo '<input type="text" name="archived_activities" value="' . esc_attr( $a_value ) . '" id="archived-activities" disabled />';

			$selectize[] = array(
				'name' => 'archived-activities',
				'value' => 'activity_id',
				'label' => 'name',
				'search' => array( 'name' ),
				'option_values' => $activity_archive_names,
				'max_items' => 'null',
			);
		}

		echo activities_build_all_selectize( $selectize );
	}

	/**
		* Saves activities options on user edit page
		*
		* @param 	int 	$user_id User id of the user making the change
		* @return bool 	True if there where a change
		*/
	public function activities_save_user_activities ( $user_id ) {
		if ( !current_user_can( 'edit_user', $user_id ) || !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
			return false;
		}
		if ( isset( $_POST['activities_select'] ) ) {
			Activities_User_Activity::insert_delete( sanitize_text_field( $_POST['activities_select'] ) , $user_id, 'user_id' );
		}
	}

	/**
		* Adds admin message
		*
		* @param string $name Name of the created item
		*/
	public static function add_create_success_message( $name ) {
		self::add_success_message( sprintf( esc_html__( '%s has been created.', 'activities' ), $name ) );
	}

	/**
		* Adds admin message
		*
		* @param string $name Name of the updated item
		*/
	public static function add_update_success_message( $name ) {
		self::add_success_message( sprintf( esc_html__( '%s has been updated.', 'activities' ), $name ) );
	}

	/**
		* Adds admin message
		*
		* @param string $type Type of item
		*/
	public static function add_name_error_message( $type ) {
		self::add_error_message( sprintf( esc_html__( '%s must have a name.', 'activities' ), $type ) );
	}

	/**
		* Adds admin message
		*
		* @param string $name Name of the deleted item
		*/
	public static function add_delete_success_message( $name ) {
		self::add_success_message( sprintf( esc_html__( '%s has been deleted.', 'activities' ), $name ) );
	}

	/**
		* Adds admin message
		*
		* @param string $msg Text to display in message
		*/
	public static function add_success_message( $msg ) {
		self::$messages[] = array(
			'class' => 'notice-success',
			'msg' => $msg
		);
	}

	/**
		* Adds admin message
		*
		* @param string $msg Text to display in message
		*/
	public static function add_error_message( $msg ) {
		self::$messages[] = array(
			'class' => 'notice-error',
			'msg' => $msg
		);
	}

	/**
		* Get all messages
		*
		* @return string Div containing messages
		*/
	public static function get_messages() {
		$out = '<div class="activities-admin-messages-wrap">';

		foreach ( self::$messages as $msg ) {
			$out .= '<div class="notice ' . $msg["class"] . '">';
			$out .= '<p>' . stripslashes( wp_filter_nohtml_kses( $msg["msg"] ) ) . '</p>';
			$out .= '</div>';
		}

		$out .= '</div>';

		return $out;
	}

	/**
		* Echoes member info mapped to 'col1' and 'col2'
		* Expects these data in post:
		* 	- item_id: id of selected activity
		* 	- custom: custom fields to get for member data
		* 	- type: what type of information to display
		*/
	public function ajax_acts_get_member_info() {
		if ( !isset( $_POST['item_id'] ) || !isset( $_POST['custom'] ) || !isset( $_POST['type'] ) ) {
			wp_send_json_error();
		}

    global $wpdb;

		//Custom col sanitation is done by acts_get_member_info
		$id = acts_validate_id( $_POST['item_id'] );
		$type = sanitize_key( $_POST['type'] );
		if ( $id === 0 || ( !is_array( $_POST['custom'] ) && $_POST['custom'] !== 'none'  ) ) {
			wp_send_json_error();
		}
		if ( $id < 0 ) {
			$info = acts_get_member_info( array(-1, -2, -3, -4, -5), $type, $_POST['custom'] );
		}
		elseif ( $id > 0 ) {
			$table_name = Activities::get_table_name( 'user_activity' );
			$user_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT user_id
				FROM $table_name
				WHERE activity_id = %d
				",
				$id
			));

			$info = acts_get_member_info( $user_ids, $type, $_POST['custom'] );
		}


		wp_send_json_success( $info );
	}

	/**
	 * Gets the name of the page
	 *
	 * @param 	bool 		$table_pages Return only pages with table view
	 * @return 	string 	Page name
	 */
	public function get_page_name( $screen, $table_pages = false ) {
		$prefix = sanitize_title( esc_html__( 'Activities', 'activities' ) );
		if ( $screen->id == 'toplevel_page_activities-admin' ) {
			return 'activity';
		}
		elseif ( $screen->id == $prefix . '_page_activities-admin-locations' ) {
			return 'location';
		}
		elseif ( !$table_pages && $screen->id == $prefix . '_page_activities-admin-import' ) {
			return 'import';
		}
		elseif ( !$table_pages && $screen->id == $prefix . '_page_activities-admin-export'  ) {
			return 'export';
		}
		elseif ( !$table_pages && $screen->id == $prefix . '_page_activities-admin-options'  ) {
			return 'options';
		}
		elseif ( $screen->id == $prefix . '_page_activities-admin-archive'  ) {
			return 'activity_archive';
		}
		else {
			return false;
		}
	}

	/**
		* Builds screen help for all pages
		*
		* @param 		string 			$status Current screen options
		* @param 		WP_Screen 	$screen Current screen object
		* @return 	string 			New screen options
		*/
	public function show_help() {
		$screen = get_current_screen();

		$page = $this->get_page_name( $screen );

		$overview_text = '';
		switch ($page) {
			case 'activity':
			case 'location':
			case 'activity_archive':
				switch ($page) {
					case 'activity':
						$display = esc_html__( 'activities', 'activities' );
						break;

					case 'location':
						$display = esc_html__( 'locations', 'activities' );
						break;

					case 'activity_archive':
						$display = esc_html__( 'archived activities', 'activities' );
						break;
				}
				if ( isset( $_GET['action'] ) ) {
					if ( $_GET['action'] == 'edit' ) {
						$overview_text = sprintf( esc_html__( 'This screen allows you to edit your %s.', 'activities' ), $display );
					}
					elseif ( $_GET['action'] == 'view' && $page != 'location'  ) {
						$overview_text = sprintf( esc_html__( 'This is the activity report screen where you customize and print/save your reports.', 'activities' ), $display );
					}
				}
				else {
					$overview_text = sprintf( esc_html__( 'This screen gives you access to all your %s.', 'activities' ), $display );
				}
				break;

			case 'import':
				$overview_text = esc_html__( 'This screen gives you the options to either import activities or participants.', 'activities' );
				break;

			case 'export':
				$overview_text = esc_html__( 'Export activity participant data to send email, sms and more.', 'activities' );
				break;

			case 'options':
				$tab = 'general';
				if ( isset( $_GET['tab'] ) ) {
					$tab = sanitize_key( $_GET['tab'] );
				}
				if ( !array_key_exists( $tab, acts_get_options_tabs() ) ) {
					break;
				}
				switch ($tab) {
					case 'nice':
						$overview_text = esc_html__( 'This is where you can edit the standard settings for activity reports.', 'activities' );
						break;

					case 'woocommerce':
						$overview_text = esc_html__( 'Settings related to the WooCommerce plugin.', 'activities' );
						break;

					default:
						$overview_text =
							esc_html__( 'This general settings page for this plugin.', 'activities' ) . '</br>'
							. esc_html__( 'If you are using a mulitisite, the settings here is only set for the current blog.', 'activities' ) . '</br>'
							. esc_html__( 'The WooCommerce tab will only show if the plugin is active.', 'activities' );
						break;
				}
				break;
		}

		if ( isset( $overview_text ) ) {
			$screen->add_help_tab( array(
				'id'	=> 'acts_overview',
				'title'	=> __( 'Overview', 'activities' ),
				'content'	=> '<p>' . $overview_text . '</br></br>' . esc_html__( 'A documentation page will be available soon!', 'activities' ) . '</p>'
			) );
		}
	}

	/**
		* Builds screen options for activities, locations and archive pages
		*
		* @param 		string 			$status Current screen options
		* @param 		WP_Screen 	$screen Current screen object
		* @return 	string 			New screen options
		*/
	public function show_screen_options( $status, $screen ) {
    $return = $status;
		if ( $this->hide_screen_options() ) {
			return $return;
		}

		$page = $this->get_page_name( $screen, true );

		if ( $page ) {
			$columns = Activities_Options::get_user_option( $page, 'show_columns' );
      $return .= '
      <fieldset>
      <legend>' . esc_html__( 'Show Columns', 'activities' ) . '</legend>
      <div class="metabox-prefs">
			<input type="hidden" name="wp_screen_options[option]" value="acts_page_settings" />
      <input type="hidden" name="wp_screen_options[value]" value="' . esc_attr( $page ) . '" />
      <div>';
			$return .= '<label for="acts_name"><input type="checkbox" name="acts_columns[]" id="acts_name" checked disabled />' . esc_html__( 'Name', 'activities' ) . '</label>';
			foreach ($columns as $name => $show) {
				$checked = $show ? 'checked' : '';
				$return .=
				'<label for="acts_' . esc_attr( $name ) . '">
				<input type="checkbox" name="acts_columns[' . esc_attr( $name ) . ']" id="acts_' . esc_attr( $name ) . '" ' . $checked . ' />'
				. wp_filter_nohtml_kses( Activities_Admin_Utility::get_column_display( $name ) )
				. '</label>';
			}
      $return .= '</div>';
			$items_per_page = Activities_Options::get_user_option( $page, 'items_per_page' );
			$return .= '<label for="activities-items-num">' . esc_html__( 'Results Per Page', 'activities' ) . '</label>';
			$return .= '<input type="number" name="items_num" id="activities-items-num" min="1" max="500" value="' . esc_html( $items_per_page ) . '"/>';

			$return .= '</div>
      </fieldset>
      <br class="clear">'
			. get_submit_button( esc_html__( 'Save', 'activities' ), 'button', 'screen-options-apply', false );
    }
    return $return;
	}

	/**
		* @return bool True if screen options should be hidden, false if not
		*/
	private function hide_screen_options() {
		$hide = isset( $_GET['action'] ) && sanitize_key( $_GET['action'] != 'activate' ) && sanitize_key( $_GET['action'] ) != 'archive';

		$hide = $hide || isset( $_POST['apply_bulk'] );

		$hide = $hide || isset( $_POST['confirm_bulk'] ) && isset( $_POST['bulk'] ) && sanitize_key( $_POST['bulk'] ) === 'change_members' && isset( $_POST['save_method'] ) && sanitize_key( $_POST['save_method'] ) == 'null';

		return $hide;
	}

	/**
		* Saves screen options
		*
		* @param 	bool 		$status unused
		* @param 	string 	$option Option name
		* @param 	string 	$page Page the setting where set on
		* @return bool 		False
		*/
	public function set_screen_options( $status, $option, $page ) {
		if ( $option === 'acts_page_settings' && isset( $_POST['acts_columns'] ) ) {
			if ( isset( $_POST['acts_columns'] ) && is_array( $_POST['acts_columns'] ) ) {
				$columns = Activities_Options::get_user_option( $page, 'show_columns' );
				foreach (array_keys( $columns ) as $key) {
					$columns[$key] = isset( $_POST['acts_columns'][$key] );
				}
				Activities_Options::update_user_option( $page, 'show_columns', $columns );
			}

			if ( isset( $_POST['items_num'] ) ) {
				$items_per_page = acts_validate_id( $_POST['items_num'] );
				if ( $items_per_page > 500 ) {
					$items_per_page = 500;
				}
				elseif ( $items_per_page <= 0 ) {
					$items_per_page = 1;
				}
				Activities_Options::update_user_option( $page, 'items_per_page', $items_per_page);
			}
		}

		return false;
	}

	/**
		* Initializes WooCommerce actions and filters
		*/
	public function init_woocommerce() {
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			Activities_WooCommerce::init();
		}
	}

	/**
		* Adds searching for first_name and last_name to user page
		*
		* @param  WP_User_Query $uqi Current search
		*/
	public function better_user_search( $uqi ) {
    global $wpdb;

		if ( !Activities_Options::get_option( ACTIVITIES_USER_SEARCH_KEY ) ) {
			return;
		}

    $search = '';
    if ( isset( $uqi->query_vars['search'] ) ) {
      $search = trim( $uqi->query_vars['search'] );
    }
    if ( $search ) {
      $search = trim($search, '*');
      $the_search = '%'.$search.'%';

      $search_meta = $wpdb->prepare(
        "CONCAT(
					(SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'first_name' AND user_id = ID ),
          ' ',
          (SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'last_name' AND user_id = ID )
				) LIKE %s
				",
        $the_search
      );

      $uqi->query_where = str_replace(
      	'user_login',
      	"$search_meta OR user_login",
      	$uqi->query_where
			);
  	}
	}

	/**
		* Removes guest flag from a 'guest cusotmer user' when they login to their account
		*
		* @param string 	$user_login unused
		* @param WP_User 	$user User that is logged in
		*/
	public function remove_guest_flag( $user_login, $user ) {
		if ( get_user_meta( $user->ID, Activities_WooCommerce::guest_key, true) == 1 ) {
			delete_user_meta( $user->ID, Activities_WooCommerce::guest_key );
		}
	}

	/**
	 * Sets title on activity report pages
	 *
	 * @param 	string 	$admin_title Extra context title
	 * @param 	string 	$title Default title
	 * @return 	string 	Report title
	 */
	public function set_activity_nice_title( $admin_title, $title ) {
		if ( !isset( $_GET['page'] ) || !isset( $_GET['action'] ) || !isset( $_GET['item_id'] ) ) {
			return $admin_title;
		}
		if (( sanitize_key( $_GET['page'] ) === 'activities-admin' || sanitize_key( $_GET['page'] ) === 'activities-admin-archive' ) && sanitize_key( $_GET['action'] ) === 'view' ) {
			$id = acts_validate_id( $_GET['item_id'] );
			if ( $id ) {
				$act = new Activities_Activity( $id );
				if ( $act->name === '' ) {
					return $admin_title;
				}
				return $act->name . ' ' . esc_html__( 'Report', 'activities' );
			}
			else {
				return $admin_title;
			}
		}
	}

	/**
	 * Activities 'acts' shortcode
	 *
	 * @param 	array $attr Shortcode input.
	 *    - name => name of activity to get
   *    - data => data to get, prefix with 'loc_' for location or 'res_' for the responsible user
	 * @return 	string Data, otherwise defaults to name if no data is found/selected. Defaults to '' if no activity is found.
	 */
	public function acts_shortcode( $attr ) {
		$act = null;
		if ( isset( $attr['name'] ) ) {
			$act = Activities_Activity::load_by_name( sanitize_text_field( $attr['name'] ) );
		}
		elseif ( isset( $_REQUEST['item_id'] ) ) {
			$id = acts_validate_id( $_REQUEST['item_id'] );
			if ( $id ) {
				$act = new Activities_Activity( $id );
				if ( $act->ID === '' ) {
					$act = null;
				}
			}
		}

		if ( $act ) {
			if ( isset( $attr['data'] ) ) {
				$input = explode( '_', $attr['data'], 2 );
				if ( isset( $input[1] ) ) {
					$get = $input[1];
				}
				switch ($input[0]) {
					case 'location':
					case 'loc':
						$obj = $act->location;
						break;

					case 'responsible':
					case 'res':
						$obj = $act->responsible;
						break;

					default:
						$obj = $act;
						$get = $attr['data'];
						break;
				}
				if ( $obj && isset( $get ) ) {
					if ( $obj instanceof WP_User && ( $get === 'name' || $get === '' ) ) {
						return Activities_Utility::get_user_name( $obj->ID, false );
					}
					elseif ( $obj instanceof WP_User && $get === 'name_email' ) {
						return Activities_Utility::get_user_name( $obj->ID, true );
					}
					elseif ( $obj instanceof Activities_Activity && $get === 'archive' ) {
						if ( $obj->get ) {
							return esc_html__( 'Archived', 'activities' );
						}
						else {
							return esc_html__( 'Active', 'activities' );
						}
					}
					elseif ( $obj instanceof Activities_Activity && ( $get === 'start' || $get === 'end' ) ) {
						return Activities_Utility::format_date( $obj->$get );
					}
					elseif ( $obj instanceof Activities_Activity && $get === 'members' ) {
						if ( !is_admin() ) {
							$text = '<span class="acts-member-count-' . esc_attr( $obj->ID ) . '">%d</span>';
						}
						else {
							$text = '%d';
						}
						return sprintf( $text, count( $obj->members ) );
					}
					elseif ( $obj instanceof Activities_Activity && $get === 'button' ) {
						if ( is_admin() ) {
							return '';
						}
						if ( !is_user_logged_in() ) {
							return '<i>' . esc_html__( 'You have to login to join.', 'activities' )  . '</i>';
						}
						if ( $obj->archive || ( $obj->end != '0000-00-00 00:00:00' && date( 'U' ) > strtotime( $obj->end ) ) ) {
							return '<i>' . esc_html__( 'You can no longer join this activity.', 'activities' )  . '</i>';
						}
						$button_filter = apply_filters(
							'activities_join_button',
							array(
								'allowed' => true,
								'response' => ''
							),
							$obj->ID
						);
						if ( $button_filter['allowed'] ) {
							$text = esc_html__( 'Join', 'activities' );
							if ( Activities_User_Activity::exists( get_current_user_id(), $obj->ID ) ) {
								$text = esc_html__( 'Unjoin', 'activities' );
							}
							return '<form class="acts-join-form" action="' . admin_url( 'admin-ajax.php' ) . '" method="post">
											<input type="hidden" name="item_id" value="' . esc_attr( $obj->ID ) . '" />
											<input type="hidden" name="action" value="acts_join" />
											<button class="acts-join-button">' . $text . '</button>
											</form>';
						}
						else {
							return '<i>' . esc_html( $button_filter['response'] )  . '</i>';
						}
					}
					elseif ( $obj instanceof Activities_Location && $get === 'country' ) {
						$code = $obj->$get;
						if ( $code != '' ) {
							return Activities_Utility::get_countries()[$code];
						}
						else {
							return '';
						}
					}
					elseif ( $get === '' ) {
						return $obj->name;
					}

					if ( is_protected_meta( $get ) ) {
						return '***';
					}

					return $obj->$get;
				}
				elseif ( $obj ) {
					if ( $obj instanceof WP_User ) {
						return Activities_Utility::get_user_name( $obj->ID, false );
					}

					return $obj->name;
				}
			}
			else {
				return $act->name;
			}
		}

		return '';
	}
}
