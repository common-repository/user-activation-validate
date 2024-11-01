<?php
/**
 * UavTools class extending WP_List_Table class
 *
 * resides in /wp-admin/includes/class-wp-list-table.php
 */

namespace Codemanas\UserActivationValidate;

use \WP_List_Table;

class UavTable extends WP_List_Table {
	public $items;

	protected function get_table_classes() {
		return array( 'uav-tools', 'widefat', 'fixed', 'striped', $this->_args['plural'] );
	}

	public function prepare_items() {

		global $wpdb;
		$uav_user_tbl = $wpdb->users;
		$users = SignUpHandler::instance()->get_inactive_users( "SELECT * FROM $uav_user_tbl WHERE user_activation_key != ''", false, ARRAY_A );

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		usort( $users, array( &$this, 'usort_reorder' ) );

		// do some pagination
		// reference: https://gist.github.com/paulund/7659452
		$perPage     = 20;
		$currentPage = $this->get_pagenum();
		$totalItems  = count( $users );
		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page'    => $perPage
		) );
		$per_page_users        = array_slice( $users, ( ( $currentPage - 1 ) * $perPage ), $perPage );
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $per_page_users;

	}

	public function get_columns() {
		$columns = array(
			'ID'                   => __( 'ID', 'uav' ),
			'user_login'           => __( 'Username', 'uav' ),
			'user_email'           => __( 'Email Address', 'uav' ),
			'user_registered'      => __( 'Registered', 'uav' ),
			'status'               => __( 'Status', 'uav' ),
			'activation_link_sent' => __( 'Count', 'uav' ) . '<span class="uav-help"><span class="uav-help-cnt uav-help-cnt--position-left uav-help-cnt--size-large">' . __( 'Counts how many times an activation link is re-sent to user. Default is 1 - assuming WordPress sends the activation link in registration.', 'uav' ) . '</span></span>'
		);

		$columns = apply_filters( 'uav_users_table_columns', $columns );

		return $columns;
	}

	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'ID':
			case 'user_login':
				return $item[ $column_name ];
				break;
			case 'user_email':
			case 'user_registered':
				return $item[ $column_name ];
				break;
			case 'status':
				return __( 'Inactive', 'uav' );
				break;
			case 'activation_deadline':
				$registered_day = $item['user_registered'];

				$uav_val_deadline         = get_option( 'uav_opt_deadline' );
				$uav_val_deadline_warning = get_option( 'uav_opt_deadline_warning' );

				$registered_and_deadline_day = strtotime( $registered_day . ' + ' . $uav_val_deadline . ' days' );

				return date( 'Y-m-d H:i:s', $registered_and_deadline_day );
				break;
			case 'activation_link_sent':
				$uav_recent_count = get_user_meta( $item['ID'], 'uav_opt_resent_count', true );
				if ( "" == $uav_recent_count ) {
					$uav_recent_count = 1;
				}

				return $uav_recent_count;
				break;
			default:
				break;
		}
	}

	public function get_sortable_columns() {
		return array(
			'ID'              => array( 'ID', true ),
			'user_login'      => array( 'user_login', true ),
			'user_email'      => array( 'user_email', true ),
			'user_registered' => array( 'user_registered', true )
		);
	}

	public function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';

		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'asc';

		// Determine sort order
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}

	public function column_user_login( $item ) {

		$actions = array(
			'resend-activation-link' => sprintf( '<a href="javascript:void(0);" class="uav-user-resend-single" data-uav-user-id="%d">' . __( 'Resend Activation Link', 'uav' ) . '</a>', $item['ID'] ),
			'delete'                 => sprintf( '<a href="javascript:void(0);" class="uav-user-delete-single" data-uav-user-id="%d">' . __( 'Delete', 'uav' ) . '</a>', $item['ID'] ),
		);

		return sprintf( '%1$s %2$s', $item['user_login'], $this->row_actions( $actions ) );
	}
}