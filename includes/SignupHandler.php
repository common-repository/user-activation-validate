<?php

namespace Codemanas\UserActivationValidate;
/**
 * This class adds the custom flags, time stamp and notification emails hooking into wp system
 * This class also check the validations (Deadline, Warning)
 * Deletes the user
 */
class SignUpHandler {

	private static $_instance = null;
	protected $check_deadlines = false;

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

		// action resend activation link, delete user
		add_action( 'wp_ajax_uav_user_resend_single', array( $this, 'user_resend_single' ) );
		add_action( 'wp_ajax_uav_user_delete_single', array( $this, 'user_delete_single' ) );
	}

	/**
	 * Queries the database and returns the unactivated users
	 *
	 * @param $query
	 * @param $ids
	 * @param $output
	 *
	 * @return array Returns the user ids that are not activated within the site.
	 * @since 1.0.0
	 */
	public function get_inactive_users( $query = '', $ids = true, $output = 'OBJECT' ) {
		global $wpdb;
		$uav_user_tbl = $wpdb->users;

		if ( $query != '' ) {
			$uav_user_query = $query;
		} else {
			$uav_user_query = "SELECT * FROM $uav_user_tbl WHERE user_activation_key != ''";
		}
		$users = $wpdb->get_results( $uav_user_query, $output );

		if ( true === $ids ) { // if $ids = true, make sure the output param is object
			$user_ids = array_map( function ( $users ) {
				return $users->ID;
			}, $users );

			return $user_ids;
		} else {
			return $users;
		}
	}

	/**
	 * Checks the user capability and nonce
	 *
	 * @param $nonce_val
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function check_security( $nonce_val ) {
		if ( current_user_can( 'manage_options' ) ) {
			if ( wp_verify_nonce( $nonce_val, 'uav_check_ajax_nonce' ) ) {
				return true;
			}

			return false;
		}

		return false;
	}

	/**
	 * Re-sends the activation link to the specific user
	 *
	 * Mixed $uav_single_return json array contains execution status and message.
	 * @since 1.0.0
	 */
	public function user_resend_single() {

		if ( $this->check_security( $_POST['nonce'] ) ) {

			// this is the single ajax resend call
			if ( isset( $_POST['uav_user_id'] ) && $_POST['uav_user_id'] != '' ) {
				$user_id           = absint( sanitize_text_field( $_POST['uav_user_id'] ) );
				$uav_single_return = $this->resend_activation_link( $user_id );
				wp_send_json( $uav_single_return );
			}
		} else {
			$uav_single_return['uav_status'] = false;
			$uav_single_return['uav_msg']    = __( 'Something went wrong. Please try again later', 'uav' );
			wp_send_json( $uav_single_return );
		}
	}

	/**
	 * All the mechanism and logic is here to resend activation link
	 *
	 * @param $user_id $user_id to resend activation link
	 *
	 * @return array $uav_single_return Array contains execution status and message.
	 * @since 1.0.0
	 */
	public function resend_activation_link( $user_id ) {

		if ( get_user_by( 'ID', $user_id ) ) { // check if the posted value is actual user id before re-sending notifications

			// This is same as first time registering
			// At this moment, WordPress thinks the user is just registered, so it crates a new activation link, updates in db and
			// Sends notification emails to both admin and email.
			wp_send_new_user_notifications( $user_id, $notify = 'user' );

			// update the count, user has been sent reactivation link in user meta before sending success message
			$uav_resent_count = get_user_meta( $user_id, 'uav_opt_resent_count', true );
			if ( "" == $uav_resent_count ) {
				$uav_resent_count = 2;
			} else {
				$uav_resent_count = (int) $uav_resent_count + 1;
			}
			update_user_meta( $user_id, 'uav_opt_resent_count', $uav_resent_count );

			$uav_single_return['uav_status']       = true;
			$uav_single_return['uav_resend_count'] = $uav_resent_count;
			$uav_single_return['uav_msg']          = "Activation link has been resent successfully.";

		} else {
			$uav_single_return['uav_status'] = false;
			$uav_single_return['uav_msg']    = "Something went wrong. No user found. Please try again later.";
		}

		return $uav_single_return;
	}

	/**
	 * Commands to delete the user. If this function is called, probably due to ajax
	 * Sends json $uav_single_return Returns the exact array returned by the uav_user_delete function.
	 *
	 * @since 1.0.0
	 */
	public function user_delete_single() {

		if ( $this->check_security( $_POST['nonce'] ) ) {

			// this is the single ajax delete call
			if ( isset( $_POST['uav_user_id'] ) && $_POST['uav_user_id'] != '' ) {
				$user_id           = absint( sanitize_text_field( $_POST['uav_user_id'] ) );
				$uav_single_return = $this->user_delete( $user_id );
				wp_send_json( $uav_single_return );
			}
		} else {
			$uav_single_return['uav_status'] = false;
			$uav_single_return['uav_msg']    = __( 'Something went wrong. Please try again later', 'uav' );
			wp_send_json( $uav_single_return );
		}
	}

	/**
	 * Deletes the user
	 * Also checks the user deadline before deleting.
	 * If deadline is meet - deletes the user
	 * If deadline is not meet - leaves the user
	 *
	 * @param $user_id $user_id to delete
	 *
	 * @return array $uav_single_return Array contains execution status and message.
	 * @since 1.0.0
	 */
	public function user_delete( $user_id ) {

		// get user info like username
		if( $user_info = get_userdata( $user_id ) ) {

			// before deleting the user, check if the deadline day has crossed or not?
			$registered_day = get_userdata( $user_id )->data->user_registered;

			$uav_val_deadline         = get_option( 'uav_opt_deadline' );
			$uav_val_deadline_warning = get_option( 'uav_opt_deadline_warning' );

			$registered_and_deadline_day = strtotime( $registered_day . ' + ' . $uav_val_deadline . ' days' );
			$now                         = strtotime( date( 'Y-m-d H:i:s' ) );

			$uav_single_return = array(
				'uav_status'              => true,
				'uav_msg'                 => 'This is uav message.',
				'uav_default_delete_text' => 'Delete'
			);

			if ( $this->check_deadlines ) { // check the deadlines

				if ( $now > $registered_and_deadline_day ) {

					// deadline is crossed, delete the user.
					if ( ! function_exists( 'wp_delete_user' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/user.php' );
					}

					wp_delete_user( $user_id );

					$uav_single_return['uav_status'] = true;
					$uav_single_return['uav_msg']    = sprintf( __( "User %s - Deleted successfully.", 'uav' ), $user_info->user_login );


				} else {

					// so deadline is not crossed hence now check deadline warning and send message.
					$registered_and_warning_day = strtotime( $registered_day . ' + ' . $uav_val_deadline_warning . ' days' );
					$now                        = strtotime( date( 'Y-m-d H:i:s' ) );
					if ( $now > $registered_and_warning_day ) {
						$user_email = get_userdata( $user_id )->data->user_email;
						$subject    = 'Notice of user Activation.';
						$body       = 'Greetings ' . $user_email . "\r\n";
						$body       .= 'You have not activated your account in the site ' . get_option( 'blogname' ) . '. Hence you need to activate it. Otherwise your account will be deleted.';
						$this->send_email( $user_email, $subject, $body );

						// To do: Right here better would be to send to admin notifying the inactive user has been sent an email

						$uav_single_return['uav_status'] = false;
						$uav_single_return['uav_msg']    = sprintf( __( "User %s - Deadline is not crossed. Warning days is crossed - therefore a warning email has been sent.", 'uav' ), $user_info->user_login );
					} else {
						$uav_single_return['uav_status'] = false;
						$uav_single_return['uav_msg']    = sprintf( __( "User %s - Deadline is not crossed. Warning days is also not crossed. Therefore user is not deleted and nor warning email is sent.", 'uav' ), $user_info->user_login );
					}

				}
			} else { // no need to check the deadlines and warnings, delete the user

				if ( ! function_exists( 'wp_delete_user' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/user.php' );
				}

				wp_delete_user( $user_id );

				$uav_single_return['uav_status'] = true;
				$uav_single_return['uav_msg']    = sprintf( __( "User %s - Deleted successfully.", 'uav' ), $user_info->user_login );

			}
		} else {
			$uav_single_return['uav_status'] = false;
			$uav_single_return['uav_msg']    = __( "Something went wrong. No user found. Please try again later", 'uav' );
		}

		return $uav_single_return;
	}

	/**
	 * Sends email
	 *
	 * @param $to
	 * @param $subject
	 * @param $body
	 *
	 * @since 1.0.0
	 */
	public function send_email( $to, $subject, $body ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $body, $headers );
	}
}