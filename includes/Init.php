<?php

namespace Codemanas\UserActivationValidate;

/**
 * Class Init
 * @package Codemanas\UserActivationValidate
 */
class Init {

	/**
	 * Public variables
	 *
	 * @since 1.0.0
	 */
	public $user_id;
	public $posted_deadline;
	public $posted_warning;
	public $posted_enable_cron;
	public $posted_cron_schedule;

	private static $_instance = null;

	/**
	 * Create only one instance so that it may not Repeat
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		$plugin_file = plugin_basename( CODEMANAS_UAV_FILE_PATH );

		// add new column in the users table in admin
		add_filter( 'manage_users_columns', array( $this, 'uav_add_uav_status_user_column' ) );

		// add value to the newly created uav_user_status column in the user table
		add_filter( 'manage_users_custom_column', array( $this, 'uav_add_uav_status_user_column_value' ), 10, 3 );

		//Plugin Links
		add_filter( "plugin_action_links_" . $plugin_file, array( $this, 'plugin_action_links' ), 10, 4 );

		// create admin menu and register settings fields
		AdminInterface::instance();


	}

	/**
	 * Adds items to the plugin's action links on the Plugins listing screen.
	 *
	 * @param string[] $actions Array of action links.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array $plugin_data An array of plugin data.
	 * @param string $context The plugin context.
	 *
	 * @return string[] Array of action links.
	 */
	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions['uav-settings'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'users.php?page=uav' ) ),
			esc_html__( 'Settings', 'uav' )
		);

		return $actions;
	}

	/**
	 * Adds the new custom column in users table
	 *
	 * @param array $columns An array of column name ⇒ label
	 *
	 * @return array $columns An array of column name ⇒ label
	 * @since 1.0.0
	 */
	function uav_add_uav_status_user_column( $columns ) {
		return array_merge( $columns,
			array( 'uav_user_status' => __( 'Status' ) ) );
	}

	/**
	 * Adds the value to the uav status custom column in users table
	 *
	 * @param string $val
	 * @param string $column_name
	 * @param int $user_id
	 *
	 * @return string $val A string active/inactive
	 * @since 1.0.0
	 */
	function uav_add_uav_status_user_column_value( $val, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'uav_user_status':
				$this->user_id = $user_id;
				$val           = $this->uav_get_user_active_status();
				break;
			default:
				break;
		}

		return $val;
	}

	/**
	 * Returns the uav user status to the custom column function
	 *
	 * Uses the public variable $user_id
	 *
	 * @return string A calculated string active/inactive
	 * @since 1.0.0
	 */
	function uav_get_user_active_status() {

		global $wpdb;
		$uav_user_tbl   = $wpdb->users;
		$uav_user_query = $wpdb->prepare( "SELECT * FROM $uav_user_tbl WHERE ID = %d AND user_activation_key != ''", $this->user_id );
		$uav_row        = $wpdb->get_row( $uav_user_query );
		if ( $uav_row ) {
			return __( 'Inactive', 'uav' );
		} else {
			return __( 'Active', 'uav' );
		}
	}
}