<?php

if ( !defined( 'WPINC' ) ) {
	die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @since 		 1.0.0
 * @package    Activities
 * @subpackage Activities/public
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name . '-public-css', plugin_dir_url( __FILE__ ) . 'css/activities-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name . '-public-js', plugin_dir_url( __FILE__ ) . 'js/activities-public.js', array( 'jquery' ), $this->version, false );
	}

	public function ajax_join() {
		if ( isset( $_POST['item_id'] ) && is_user_logged_in() ) {
			$id = acts_validate_id( $_POST['item_id'] );
			if ( !$id ) {
				wp_send_json_error();
			}
			if ( !Activities_User_Activity::exists( get_current_user_id(), $id ) ) {
				Activities_User_Activity::insert( get_current_user_id(), $id );
				$text = esc_html__( 'Unjoin', 'activities' );
			}
			else {
				Activities_User_Activity::delete( get_current_user_id(), $id );
				$text = esc_html__( 'Join', 'activities' );
			}
			$act = new Activities_Activity( $id );
			$count = count( $act->members );
			wp_send_json_success( array( 'text' => $text, 'count' => $count, 'id' => $id ) );
		}
		else {
			wp_send_json_error();
		}
	}
}
