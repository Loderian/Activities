<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @since         1.0.0
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
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Messages to display
     *
     * @since    1.0.0
     * @access   private
     * @var      array $messages Messages to display
     */
    private static $messages = array();

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     *
     * @since    1.0.0
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function register_styles() {
        wp_register_style( $this->plugin_name . '-admin-css', plugin_dir_url( __FILE__ ) . 'css/activities-admin.css', array(), $this->version, 'all' );
        wp_register_style( $this->plugin_name . '-admin-report-css', plugin_dir_url( __FILE__ ) . 'css/report/activities-admin-report.css', array(), $this->version, 'all' );

        //Enqueue such that selectize works on WooCommerce pages
        wp_enqueue_style( $this->plugin_name . '-selectize-css', plugin_dir_url( __FILE__ ) . 'css/selectize/selectize.css', array(), $this->version, 'all' );
    }

    /**
     * Enqueues the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name . '-admin-css' );
        wp_enqueue_style( $this->plugin_name . '-admin-report-css' );
        wp_enqueue_style( 'wp-color-picker' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function register_scripts() {
        wp_register_script( $this->plugin_name . '-admin-js', plugin_dir_url( __FILE__ ) . 'js/activities-admin.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( $this->plugin_name . '-admin-js', 'acts_i18n_admin', array(
            'session' => esc_html__( 'Session', 'activities' )
        ) );

        wp_register_script( $this->plugin_name . '-admin-report-js', plugin_dir_url( __FILE__ ) . 'js/report/activities-admin-report.js', array(
            'jquery',
            'wp-color-picker'
        ), $this->version, false );
        wp_localize_script( $this->plugin_name . '-admin-report-js', 'acts_i18n_nice', array(
            'select_img_title' => esc_html__( 'Select a logo for the activity report', 'activities' )
        ) );

        wp_register_script( $this->plugin_name . '-admin-report-plan-js', plugin_dir_url( __FILE__ ) . 'js/report/activities-admin-report-plan.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( $this->plugin_name . '-admin-report-plan-js', 'acts_i18n_nice', array(
            'empty'            => esc_html__( 'Empty', 'activities' ),
            'create_plan'      => __( 'Create plan', 'activities' ),
            'update_plan'      => __( 'Update plan', 'activities' ),
            'unnamed_plan'      => __( 'Unnamed plan', 'activities' )
        ) );

        //Enqueue such that selectize works on WooCommerce pages
        wp_enqueue_script( $this->plugin_name . '-selectize-js', plugin_dir_url( __FILE__ ) . 'js/selectize/selectize.min.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Enqueues the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name . '-admin-js' );
        wp_enqueue_script( $this->plugin_name . '-admin-report-js' );
        wp_enqueue_script( $this->plugin_name . '-admin-report-plan-js' );
        wp_enqueue_script( 'imagesloaded' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    /**
     * Add menu pages to the wp-admin menu
     */
    public function activities_admin_menu() {
        $pages = array();

        add_menu_page( esc_html__( 'Activities Admin Page', 'activities' ), esc_html__( 'Activities', 'activities' ), ACTIVITIES_ACCESS_ACTIVITIES, 'activities-admin', '', 'dashicons-groups', 30 );

        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities', 'activities' ), esc_html__( 'Activities', 'activities' ), ACTIVITIES_ACCESS_ACTIVITIES, 'activities-admin', array(
            $this,
            'activities_admin_activities'
        ) );
        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Locations', 'activities' ), esc_html__( 'Locations', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-locations', array(
            $this,
            'activities_admin_locations'
        ) );
        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Plans', 'activities' ), esc_html__( 'Plans', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-plans', array(
            $this,
            'activities_admin_plans'
        ) );
        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Import', 'activities' ), esc_html__( 'Import', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-import', array(
            $this,
            'activities_admin_import'
        ) );
        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Export', 'activities' ), esc_html__( 'Export', 'activities' ), ACTIVITIES_ACCESS_ACTIVITIES, 'activities-admin-export', array(
            $this,
            'activities_admin_export'
        ) );
        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Options', 'activities' ), esc_html__( 'Options', 'activities' ), ACTIVITIES_ADMINISTER_OPTIONS, 'activities-admin-options', array(
            $this,
            'activities_admin_options'
        ) );
        $pages[] = add_submenu_page( 'activities-admin', esc_html__( 'Activities Archive', 'activities' ), esc_html_x( 'Archive', 'Noun', 'activities' ), ACTIVITIES_ADMINISTER_ACTIVITIES, 'activities-admin-archive', array(
            $this,
            'activities_admin_archive'
        ) );

        foreach ( $pages as $page ) {
            add_action( 'admin_print_styles-' . $page, array( $this, 'enqueue_styles' ) );
            add_action( 'admin_print_scripts-' . $page, array( $this, 'enqueue_scripts' ) );
            add_action( 'load-' . $page, array( $this, 'show_help' ) );
        }
    }

    /**
     * Echoes activities page
     */
    public function activities_admin_activities() {
        echo '<div class="wrap acts-wrap">';
        echo activities_admin_activities_page();
        echo '</div>';
    }

    /**
     * Echoes locations page
     */
    public function activities_admin_locations() {
        echo '<div class="wrap acts-wrap">';
        echo activities_admin_locations_page();
        echo '</div>';
    }

    /**
     * Echoes locations page
     */
    public function activities_admin_plans() {
        echo '<div class="wrap acts-wrap">';
        echo activities_admin_plans_page();
        echo '</div>';
    }

    /**
     * Echoes options page
     */
    public function activities_admin_options() {
        echo '<div class="wrap acts-wrap">';
        activities_admin_options_page();
        echo '</div>';
    }

    /**
     * Echoes export page
     */
    public function activities_admin_export() {
        echo '<div class="wrap acts-wrap">';
        activities_export_page();
        echo '</div>';
    }

    /**
     * Echoes import page
     */
    public function activities_admin_import() {
        echo '<div class="wrap acts-wrap">';
        activities_import_page();
        echo '</div>';
    }

    /**
     * Echoes archive page
     */
    public function activities_admin_archive() {
        echo '<div class="wrap acts-wrap">' . activities_admin_archive_page() . '</div>';
    }

    /**
     * Echoes options to add/remove activities, and look at archived activities on user profiles
     *
     * @param WP_User $user User accessing the page
     */
    public function activities_add_user_option( $user ) {
        echo '<h3>' . esc_html__( 'Activities', 'activities' ) . '</h3>';

        echo acts_build_select_items(
            'activity',
            array(
                'name'     => 'activities_selected[]',
                'id'       => 'acts_user_acts',
                'selected' => Activities_User_Activity::get_user_activities( $user->ID ),
                'multiple' => true,
                'disabled' => !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES )
            )
        );

        if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
            echo '<h4>' . esc_html__( 'Archived Activites', 'activities' ) . '</h4>';
            echo acts_build_select_items(
                'activity_archive',
                array(
                    'id'       => 'acts_user_archived_acts',
                    'selected' => Activities_User_Activity::get_user_activities( $user->ID, 'archive' ),
                    'multiple' => true,
                    'disabled' => true
                )
            );
        }

        echo '
    <script>
      jQuery("#acts_user_acts").selectize({';
        if ( current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
            echo 'plugins: ["remove_button"]';
        }
        echo '
      });
      jQuery("#acts_user_archived_acts").selectize({});
    </script>';
    }

    /**
     * Saves activities options on user edit page
     *
     * @param int $user_id User id of the user making the change
     *
     * @return bool    True if there where a change
     */
    public function activities_save_user_activities( $user_id ) {
        if ( !current_user_can( 'edit_user', $user_id ) || !current_user_can( ACTIVITIES_ADMINISTER_ACTIVITIES ) ) {
            return false;
        }
        $acts = array();
        if ( isset( $_POST['activities_selected'] ) && is_array( $_POST['activities_selected'] ) ) {
            foreach ( $_POST['activities_selected'] as $key => $id ) {
                if ( acts_validate_int( $id ) ) {
                    $acts[] = $id;
                }
            }
        }

        return Activities_User_Activity::delete_insert( $acts, $user_id, 'user_id' ) > 0;
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
            'msg'   => $msg
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
            'msg'   => $msg
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
     * Gets the name of the page
     *
     * @param WP_Screen $screen Current screen object
     * @param bool $table_pages Return only pages with table view
     *
     * @return    string    Page name
     */
    static function get_page_name( $screen, $table_pages = false ) {
        $prefix = sanitize_title( esc_html__( 'Activities', 'activities' ) );
        if ( $screen->id == 'toplevel_page_activities-admin' ) {
            return 'activity';
        } elseif ( $screen->id == $prefix . '_page_activities-admin-locations' ) {
            return 'location';
        } elseif ( $screen->id == $prefix . '_page_activities-admin-plans' ) {
            return 'plan';
        } elseif ( !$table_pages && $screen->id == $prefix . '_page_activities-admin-import' ) {
            return 'import';
        } elseif ( !$table_pages && $screen->id == $prefix . '_page_activities-admin-export' ) {
            return 'export';
        } elseif ( !$table_pages && $screen->id == $prefix . '_page_activities-admin-options' ) {
            return 'options';
        } elseif ( $screen->id == $prefix . '_page_activities-admin-archive' ) {
            return 'activity_archive';
        } else {
            return false;
        }
    }

    /**
     * Builds screen help for all pages
     */
    public function show_help() {
        $screen = get_current_screen();

        $page = self::get_page_name( $screen );

        $overview_text = '';
        switch ( $page ) {
            case 'activity':
            case 'location':
            case 'activity_archive':
                switch ( $page ) {
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
                    } elseif ( $_GET['action'] == 'view' && $page != 'location' ) {
                        $overview_text = sprintf( esc_html__( 'This is the activity report screen where you customize and print/save your reports.', 'activities' ), $display );
                    }
                } else {
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
                switch ( $tab ) {
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
                'id'      => 'acts_overview',
                'title'   => __( 'Overview', 'activities' ),
                'content' => '<p>' . $overview_text . '</br></br>' . esc_html__( 'A documentation page will be available sometime!', 'activities' ) . '</p>'
            ) );
        }
    }

    /**
     * Builds screen options for activities, locations and archive pages
     *
     * @param string $status Current screen options
     * @param WP_Screen $screen Current screen object
     *
     * @return    string            New screen options
     */
    public function show_screen_options( $status, $screen ) {
        $return = $status;
        if ( $this->hide_screen_options() ) {
            return $return;
        }

        $page = $this->get_page_name( $screen, true );

        if ( $page ) {
            $columns = Activities_Options::get_user_option( $page, 'show_columns' );
            $return  .= '
      <fieldset>
      <legend>' . esc_html__( 'Show Columns', 'activities' ) . '</legend>
      <div class="metabox-prefs">
			<input type="hidden" name="wp_screen_options[option]" value="acts_settings_for_page" />
      <input type="hidden" name="wp_screen_options[value]" value="' . esc_attr( $page ) . '" />
      <div>';
            $return  .= '<label for="acts_name"><input type="checkbox" name="acts_columns[]" id="acts_name" checked disabled />' . esc_html__( 'Name', 'activities' ) . '</label>';
            foreach ( $columns as $name => $show ) {
                $checked = $show ? 'checked' : '';
                $return  .=
                    '<label for="acts_' . esc_attr( $name ) . '">
				<input type="checkbox" name="acts_columns[' . esc_attr( $name ) . ']" id="acts_' . esc_attr( $name ) . '" ' . $checked . ' key="' . esc_attr( $name ) . '" />'
                    . wp_filter_nohtml_kses( Activities_Admin_Utility::get_column_display( $name ) )
                    . '</label>';
            }
            $return         .= '</div>';
            $items_per_page = Activities_Options::get_user_option( $page, 'items_per_page' );
            $return         .= '<label for="activities-items-num">' . esc_html__( 'Results Per Page', 'activities' ) . '</label>';
            $return         .= '<input type="number" name="items_num" id="activities-items-num" min="1" max="500" value="' . esc_html( $items_per_page ) . '"/>';

            $return .= '</div>
      </fieldset>
      <br class="clear">'
                       . get_submit_button( esc_html__( 'Apply', 'activities' ), 'button-primary', 'screen-options-apply', false );
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
     * @param bool $status unused
     * @param string $option Option name
     * @param string $page Page the setting where set on
     *
     * @return bool        False
     */
    public function set_screen_options( $status, $option, $page ) {
        if ( $option === 'acts_settings_for_page' && isset( $_POST['acts_columns'] ) ) {
            if ( isset( $_POST['acts_columns'] ) && is_array( $_POST['acts_columns'] ) ) {
                $columns = Activities_Options::get_user_option( $page, 'show_columns' );
                foreach ( array_keys( $columns ) as $key ) {
                    $columns[$key] = isset( $_POST['acts_columns'][$key] );
                }
                Activities_Options::update_user_option( $page, 'show_columns', $columns );
            }

            if ( isset( $_POST['items_num'] ) ) {
                $items_per_page = acts_validate_int( $_POST['items_num'] );
                if ( $items_per_page > 500 ) {
                    $items_per_page = 500;
                } elseif ( $items_per_page <= 0 ) {
                    $items_per_page = 1;
                }
                Activities_Options::update_user_option( $page, 'items_per_page', $items_per_page );
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
     * @param WP_User_Query $uqi Current search
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
            $search     = trim( $search, '*' );
            $the_search = '%' . $search . '%';

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
     * @param string $user_login unused
     * @param WP_User $user User that is logged in
     */
    public function remove_guest_flag( $user_login, $user ) {
        if ( get_user_meta( $user->ID, Activities_WooCommerce::guest_key, true ) == 1 ) {
            delete_user_meta( $user->ID, Activities_WooCommerce::guest_key );
        }
    }

    /**
     * Sets title on activity report pages
     *
     * @param string $admin_title Extra context title
     * @param string $title Default title
     *
     * @return    string    Report title
     */
    public function set_activity_nice_title( $admin_title, $title ) {
        if ( !isset( $_GET['page'] ) || !isset( $_GET['action'] ) || !isset( $_GET['item_id'] ) ) {
            return $admin_title;
        }
        if ( ( sanitize_key( $_GET['page'] ) === 'activities-admin' || sanitize_key( $_GET['page'] ) === 'activities-admin-archive' ) && sanitize_key( $_GET['action'] ) === 'view' ) {
            $id = acts_validate_int( $_GET['item_id'] );
            if ( $id ) {
                $act = new Activities_Activity( $id );
                if ( $act->name === '' ) {
                    return $admin_title;
                }

                return stripslashes( wp_filter_nohtml_kses( $act->name ) ) . ' ' . esc_html__( 'Report', 'activities' );
            } else {
                return $admin_title;
            }
        }

        return $admin_title;
    }
}
