<?php
/*
Plugin Name: User Activation Validate
Plugin URI: https://www.codemanas.com/
Description: This plugin checks and provides a user interface to Admins giving the option to either delete the user or resend activation link.
Author: codemanas
Version: 1.1.3
Author URI: https://www.codemanas.com/
License: GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  uav
Domain Path: /language
*/

/**
 * Prevent loading this file directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( - 1 );
}

/**
 * Define Constants
 */
if ( ! defined( 'CODEMANAS_UAV_FILE_PATH' ) ) {
	define( 'CODEMANAS_UAV_FILE_PATH', __FILE__ );
}
if ( ! defined( 'CODEMANAS_UAV_DIR_PATH' ) ) {
	define( 'CODEMANAS_UAV_DIR_PATH', dirname( __FILE__ ) );
}
if ( ! defined( 'CODEMANAS_UAV_DIR_URL' ) ) {
	define( 'CODEMANAS_UAV_DIR_URL', plugin_dir_url( __FILE__ ) );
}

function codemanas_uav_load_text_domain() {
	$domain = 'uav';
	load_plugin_textdomain( $domain, false, trailingslashit( basename( dirname( __FILE__ ) ) ) . 'language/' );
}
add_action('init', 'codemanas_uav_load_text_domain');

/**
 * Requiring Files
 */
require_once CODEMANAS_UAV_DIR_PATH.'/vendor/autoload.php';
require_once( CODEMANAS_UAV_DIR_PATH . '/includes/bootstrap.php' );